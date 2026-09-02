<?php

namespace Tests\Feature\Dashboard;

use App\Jobs\SendSmsJob;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Stepped identity-profile completion (docs/starter.md §26).
 */
class ProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_reach_the_profile_wizard(): void
    {
        $this->get('/dashboard/profile')->assertRedirect(route('login'));
    }

    public function test_profile_index_redirects_to_the_first_incomplete_step(): void
    {
        $user = User::factory()->create(['first_name' => null, 'last_name' => null]);

        $this->actingAs($user)->get('/dashboard/profile')
            ->assertRedirect(route('dashboard.profile.step', ['step' => 1]));
    }

    public function test_step_one_requires_names_and_persists(): void
    {
        $user = User::factory()->create(['first_name' => null, 'last_name' => null]);

        $this->actingAs($user)
            ->put(route('dashboard.profile.update', ['step' => 1]), ['first_name' => ''])
            ->assertSessionHasErrors(['first_name', 'last_name']);

        $this->actingAs($user)
            ->put(route('dashboard.profile.update', ['step' => 1]), [
                'first_name' => 'علی',
                'last_name' => 'رضایی',
                'company' => 'شرکت نمونه',
                'password' => 'secret12',
                'password_confirmation' => 'secret12',
            ])
            ->assertRedirect(route('dashboard.profile.step', ['step' => 2]));

        $user->refresh();
        $this->assertSame('علی', $user->first_name);
        $this->assertSame('علی رضایی', $user->name);
        $this->assertNotNull($user->password);
    }

    public function test_step_two_persists_location(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('dashboard.profile.update', ['step' => 2]), [
                'country' => 'ایران',
                'province' => 'تهران',
                'city' => 'تهران',
                'address' => 'خیابان نمونه، پلاک ۱',
                'postal_code' => '1234567890',
            ])
            ->assertRedirect(route('dashboard.profile.step', ['step' => 3]));

        $user->refresh();
        $this->assertSame('تهران', $user->province);
        $this->assertSame('1234567890', $user->postal_code);
    }

    public function test_step_two_rejects_a_bad_postal_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('dashboard.profile.update', ['step' => 2]), [
                'country' => 'ایران',
                'postal_code' => '12',
            ])
            ->assertSessionHasErrors('postal_code');
    }

    public function test_step_three_stores_documents_completes_and_notifies_admin(): void
    {
        Bus::fake();
        Storage::fake('local');
        config(['sms.admin_mobile' => '09000000000']);

        $user = User::factory()->create(['status' => 'pending', 'profile_completed_at' => null]);

        $this->actingAs($user)
            ->put(route('dashboard.profile.update', ['step' => 3]), [
                'national_code' => '0012345678',
                'birth_cert_number' => '123',
                'national_card_image' => UploadedFile::fake()->image('front.jpg'),
                'national_card_back_image' => UploadedFile::fake()->image('back.jpg'),
                'identity_doc_image' => UploadedFile::fake()->image('selfie.jpg'),
            ])
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertNotNull($user->profile_completed_at);
        // No plan yet → account is not ready for approval, stays pending (docs/starter.md §39).
        $this->assertSame('pending', $user->status);
        $this->assertSame('pending', $user->documents_status);
        $this->assertNotNull($user->national_card_image);
        Storage::disk('local')->assertExists($user->national_card_image);

        Bus::assertDispatched(SendSmsJob::class);
    }

    public function test_profile_plus_plan_moves_account_to_awaiting_approval(): void
    {
        $plan = Plan::create([
            'name' => 'پلن تست', 'slug' => 'test-plan', 'price_monthly' => 990000, 'duration_days' => 30, 'is_active' => true,
        ]);
        $user = User::factory()->create([
            'status' => 'pending',
            'profile_completed_at' => null,
            'plan_id' => $plan->id,
            'plan_expires_at' => now()->addYear(),
        ]);

        Storage::fake('local');

        $this->actingAs($user)
            ->put(route('dashboard.profile.update', ['step' => 3]), [
                'national_code' => '0012345678',
                'national_card_image' => UploadedFile::fake()->image('front.jpg'),
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('awaiting_approval', $user->fresh()->status);
    }

    public function test_legal_account_step_one_requires_company_registration_data(): void
    {
        $user = User::factory()->create(['first_name' => null, 'last_name' => null]);

        $this->actingAs($user)
            ->put(route('dashboard.profile.update', ['step' => 1]), [
                'account_type' => 'legal',
                'first_name' => 'علی',
                'last_name' => 'رضایی',
            ])
            ->assertSessionHasErrors([
                'company', 'company_type', 'company_national_id', 'company_registration_number',
            ]);

        $this->actingAs($user)
            ->put(route('dashboard.profile.update', ['step' => 1]), [
                'account_type' => 'legal',
                'first_name' => 'علی',
                'last_name' => 'رضایی',
                'rep_role' => 'مدیرعامل',
                'company' => 'داده‌نگار آسمان',
                'company_type' => 'سهامی خاص',
                'company_national_id' => '10102345678',
                'company_registration_number' => '456789',
                'company_registered_at' => '۱۳۹۵/۰۳/۱۲',
                'company_economic_code' => '411111111111',
            ])
            ->assertRedirect(route('dashboard.profile.step', ['step' => 2]));

        $user->refresh();
        $this->assertSame('legal', $user->account_type);
        $this->assertTrue($user->isLegal());
        $this->assertSame('داده‌نگار آسمان', $user->company);
        $this->assertSame('داده‌نگار آسمان', $user->name); // legal → name is the company
        $this->assertSame('10102345678', $user->company_national_id);
        $this->assertSame('مدیرعامل', $user->rep_role);
        $this->assertSame('علی', $user->first_name); // representative
    }

    public function test_legal_account_step_three_requires_and_stores_company_documents(): void
    {
        Bus::fake();
        Storage::fake('local');

        $user = User::factory()->create([
            'account_type' => 'legal',
            'company' => 'داده‌نگار آسمان',
            'status' => 'pending',
            'profile_completed_at' => null,
        ]);

        // آگهی تأسیس الزامی است تا وقتی آپلود نشده باشد.
        $this->actingAs($user)
            ->put(route('dashboard.profile.update', ['step' => 3]), [
                'national_code' => '0012345678',
                'national_card_image' => UploadedFile::fake()->image('front.jpg'),
            ])
            ->assertSessionHasErrors('company_registration_doc');

        $this->actingAs($user)
            ->put(route('dashboard.profile.update', ['step' => 3]), [
                'national_code' => '0012345678',
                'national_card_image' => UploadedFile::fake()->image('front.jpg'),
                'company_registration_doc' => UploadedFile::fake()->create('tasis.pdf', 200, 'application/pdf'),
                'company_changes_doc' => UploadedFile::fake()->image('changes.jpg'),
                'company_extra_docs' => [
                    UploadedFile::fake()->image('permit1.jpg'),
                    UploadedFile::fake()->create('permit2.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertNotNull($user->company_registration_doc);
        $this->assertNotNull($user->company_changes_doc);
        $this->assertCount(2, $user->company_extra_docs);
        Storage::disk('local')->assertExists($user->company_registration_doc);
        Storage::disk('local')->assertExists($user->company_extra_docs[0]);
        $this->assertNotNull($user->profile_completed_at);
        $this->assertSame('pending', $user->documents_status);
    }

    public function test_identity_fields_are_locked_after_approval(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'approved_at' => now(),
            'first_name' => 'علی',
            'last_name' => 'رضایی',
        ]);

        $this->actingAs($user)
            ->put(route('dashboard.profile.update', ['step' => 1]), [
                'first_name' => 'حسن',
                'last_name' => 'کریمی',
                'company' => 'شرکت تازه',
            ])
            ->assertRedirect(route('dashboard.profile.step', ['step' => 2]));

        $user->refresh();
        $this->assertSame('علی', $user->first_name);
        $this->assertSame('رضایی', $user->last_name);
        $this->assertSame('شرکت تازه', $user->company);
    }
}
