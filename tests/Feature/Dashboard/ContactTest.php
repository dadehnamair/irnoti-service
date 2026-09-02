<?php

namespace Tests\Feature\Dashboard;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phonebook contacts CRUD from the customer panel (docs/starter.md §17).
 */
class ContactTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['status' => 'active', 'approved_at' => now()]);
    }

    public function test_phonebook_pages_render(): void
    {
        $user = $this->user();
        $group = $user->contactGroups()->create(['name' => 'گروه']);
        $contact = $user->contacts()->create(['mobile' => '09121234567']);
        $contact->groups()->attach($group);

        $this->actingAs($user)->get(route('dashboard.contacts'))->assertOk()->assertSee('دفترچه تلفن');
        $this->actingAs($user)->get(route('dashboard.contacts.groups'))->assertOk()->assertSee('گروه‌های دفترچه تلفن');
        $this->actingAs($user)->get(route('dashboard.contacts.groups.edit', $group))->assertOk()->assertSee('ویرایش گروه');
        $this->actingAs($user)->get(route('dashboard.contacts.send'))->assertOk()->assertSee('ارسال گروهی');
        $this->actingAs($user)->get(route('dashboard.contacts.edit', $contact))->assertOk()->assertSee('ویرایش مخاطب');
    }

    public function test_customer_adds_a_contact_with_groups_and_normalised_mobile(): void
    {
        $user = $this->user();
        $group = $user->contactGroups()->create(['name' => 'خانواده']);

        $this->actingAs($user)->post(route('dashboard.contacts.store'), [
            'first_name' => 'علی',
            'last_name' => 'رضایی',
            'mobile' => '+989121234567',
            'groups' => [$group->id],
        ])->assertRedirect();

        $contact = Contact::firstWhere('user_id', $user->id);
        $this->assertSame('09121234567', $contact->mobile);
        $this->assertTrue($contact->groups->contains($group));
        $this->assertSame('local', $contact->sync_status);
    }

    public function test_duplicate_mobile_for_the_same_user_is_rejected(): void
    {
        $user = $this->user();
        $user->contacts()->create(['mobile' => '09121234567']);

        $this->actingAs($user)->post(route('dashboard.contacts.store'), [
            'mobile' => '09121234567',
        ])->assertSessionHasErrors('mobile');

        $this->assertSame(1, $user->contacts()->count());
    }

    public function test_invalid_mobile_is_rejected(): void
    {
        $this->actingAs($this->user())->post(route('dashboard.contacts.store'), [
            'mobile' => '12345',
        ])->assertSessionHasErrors('mobile');
    }

    public function test_customer_updates_and_deletes_a_contact(): void
    {
        $user = $this->user();
        $contact = $user->contacts()->create(['mobile' => '09120000000', 'first_name' => 'قبل']);

        $this->actingAs($user)->put(route('dashboard.contacts.update', $contact), [
            'mobile' => '09120000000',
            'first_name' => 'بعد',
        ])->assertRedirect(route('dashboard.contacts'));

        $this->assertSame('بعد', $contact->fresh()->first_name);

        $this->actingAs($user)->delete(route('dashboard.contacts.destroy', $contact))
            ->assertRedirect(route('dashboard.contacts'));
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_contacts_are_scoped_to_the_owner(): void
    {
        $contact = $this->user()->contacts()->create(['mobile' => '09121112222']);
        $intruder = $this->user();

        $this->actingAs($intruder)->get(route('dashboard.contacts.edit', $contact))->assertForbidden();
        $this->actingAs($intruder)->delete(route('dashboard.contacts.destroy', $contact))->assertForbidden();
    }
}
