<?php

namespace Tests\Feature\Marketplace;

use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\MarketplaceApp;
use App\Models\MarketplaceInstallation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** ایرپلاس integration — pull passengers into a scoped phonebook (docs/starter.md §15/§17). */
class IrPlusSyncTest extends TestCase
{
    use RefreshDatabase;

    private function installIrPlus(User $user): MarketplaceInstallation
    {
        $app = MarketplaceApp::create([
            'name' => 'ایرپلاس',
            'slug' => 'irplus',
            'category' => 'integration',
            'handler' => 'irplus',
            'billing_type' => 'free',
            'price' => 0,
            'is_active' => true,
            'config_schema' => [
                ['key' => 'api_key', 'label' => 'کلید', 'required' => true, 'secret' => true],
                ['key' => 'agency_code', 'label' => 'کد آژانس', 'required' => true],
            ],
        ]);

        $this->actingAs($user)->post(route('marketplace.install', $app), [
            'method' => 'wallet',
            'config' => ['api_key' => 'test-api-key-123', 'agency_code' => 'AG-1'],
        ])->assertRedirect();

        return $user->marketplaceInstallations()->firstOrFail();
    }

    public function test_install_requires_valid_config(): void
    {
        $app = MarketplaceApp::create([
            'name' => 'ایرپلاس', 'slug' => 'irplus', 'category' => 'integration',
            'handler' => 'irplus', 'billing_type' => 'free', 'price' => 0, 'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('marketplace.install', $app), ['method' => 'wallet', 'config' => ['api_key' => 'x']])
            ->assertSessionHasErrors('api_key');
    }

    public function test_sync_creates_a_scoped_phonebook(): void
    {
        $user = User::factory()->create();
        $installation = $this->installIrPlus($user);

        $this->actingAs($user)->post(route('marketplace.sync', $installation))
            ->assertRedirect(route('marketplace.manage', $installation));

        // Root group + one per remote group, all owned by the installation.
        $this->assertGreaterThanOrEqual(4, ContactGroup::where('marketplace_installation_id', $installation->id)->count());
        $this->assertSame(5, Contact::where('marketplace_installation_id', $installation->id)->count());

        $contact = Contact::where('mobile', '09121110002')->firstOrFail();
        $this->assertSame($user->id, $contact->user_id);
        $this->assertSame('irplus', $contact->source);
        // vip + europe + the root "ایرپلاس" group.
        $this->assertGreaterThanOrEqual(3, $contact->groups()->count());

        $installation->refresh();
        $this->assertNotNull($installation->last_synced_at);
    }

    public function test_re_sync_is_idempotent(): void
    {
        $user = User::factory()->create();
        $installation = $this->installIrPlus($user);

        $this->actingAs($user)->post(route('marketplace.sync', $installation));
        $this->actingAs($user)->post(route('marketplace.sync', $installation));

        $this->assertSame(5, Contact::where('marketplace_installation_id', $installation->id)->count());
    }
}
