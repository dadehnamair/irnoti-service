<?php

namespace Tests\Feature\Marketplace;

use App\Models\MarketplaceApp;
use App\Models\MarketplaceInstallation;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\FeaturesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/** «بازارچه افزونه‌ها» — catalogue, install, pay, uninstall (docs/starter.md §15). */
class MarketplaceInstallTest extends TestCase
{
    use RefreshDatabase;

    private function app(array $overrides = []): MarketplaceApp
    {
        return MarketplaceApp::create(array_merge([
            'name' => 'افزونه تست',
            'slug' => 'test-app',
            'category' => 'other',
            'handler' => 'feature_unlock',
            'billing_type' => 'free',
            'price' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function setSetting(string $key, string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value, 'type' => 'bool', 'group' => 'commerce']);
        Cache::forget(Setting::CACHE_KEY);
    }

    public function test_catalogue_lists_only_active_apps(): void
    {
        $this->app(['slug' => 'live', 'name' => 'زنده', 'is_active' => true]);
        $this->app(['slug' => 'hidden', 'name' => 'مخفی', 'is_active' => false]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard.marketplace'))
            ->assertOk()
            ->assertSee('زنده')
            ->assertDontSee('مخفی');
    }

    public function test_marketplace_hidden_when_disabled(): void
    {
        $this->setSetting('marketplace_enabled', '0');

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard.marketplace'))
            ->assertNotFound();
    }

    public function test_free_app_installs_and_activates_instantly(): void
    {
        $app = $this->app(['billing_type' => 'free', 'price' => 0]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('marketplace.install', $app), ['method' => 'wallet'])
            ->assertRedirect();

        $installation = MarketplaceInstallation::firstOrFail();
        $this->assertSame('active', $installation->status);
        $this->assertNotNull($installation->activated_at);
        $this->assertNull($installation->expires_at);
    }

    public function test_one_time_app_awaits_payment_then_settles_from_wallet(): void
    {
        $this->setSetting('marketplace_payment_online', '0');
        $app = $this->app(['billing_type' => 'one_time', 'price' => 90000]);
        $user = User::factory()->create();
        $user->wallet()->credit(200000, 'topup', null, 'تست');

        $this->actingAs($user)
            ->post(route('marketplace.install', $app), ['method' => 'wallet'])
            ->assertRedirect();

        $installation = MarketplaceInstallation::firstOrFail();
        $this->assertSame('active', $installation->status);
        $this->assertSame(110000, $user->wallet()->fresh()->balance);
    }

    public function test_subscription_app_sets_expiry_on_gateway_callback(): void
    {
        $this->setSetting('marketplace_payment_online', '1');
        $app = $this->app(['billing_type' => 'subscription', 'price' => 150000, 'billing_period' => 'monthly']);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('marketplace.install', $app), ['method' => 'online'])->assertRedirect();

        $installation = MarketplaceInstallation::firstOrFail();
        $this->assertSame('awaiting_payment', $installation->status);

        $this->actingAs($user)->get(route('marketplace.pay', $installation))->assertOk();
        $txId = $installation->fresh()->transaction_id;
        $this->assertNotNull($txId);

        $this->get("/marketplace/payment/callback?transactionId={$txId}")
            ->assertRedirect(route('marketplace.manage', $installation));

        $installation->refresh();
        $this->assertSame('active', $installation->status);
        $this->assertNotNull($installation->expires_at);
        $this->assertTrue($installation->expires_at->isFuture());
    }

    public function test_cannot_install_the_same_app_twice(): void
    {
        $app = $this->app();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('marketplace.install', $app), ['method' => 'wallet'])->assertRedirect();
        $this->actingAs($user)->post(route('marketplace.install', $app), ['method' => 'wallet'])->assertRedirect();

        $this->assertSame(1, MarketplaceInstallation::where('user_id', $user->id)->count());
    }

    public function test_feature_unlock_app_grants_and_revokes_capability(): void
    {
        $this->seed(FeaturesSeeder::class);

        $app = $this->app([
            'slug' => 'business-card',
            'handler' => 'feature_unlock',
            'capabilities' => ['pro.business_card'],
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('marketplace.install', $app), ['method' => 'wallet'])->assertRedirect();

        $this->assertContains('pro.business_card', $user->grantedFeatureKeys());

        $installation = MarketplaceInstallation::firstOrFail();
        $this->actingAs($user)->delete(route('marketplace.uninstall', $installation))->assertRedirect();

        $this->assertSame('cancelled', $installation->fresh()->status);
        $this->assertNotContains('pro.business_card', $user->fresh()->grantedFeatureKeys());
    }

    public function test_cannot_manage_another_users_installation(): void
    {
        $app = $this->app();
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $installation = $owner->marketplaceInstallations()->create([
            'marketplace_app_id' => $app->id,
            'status' => 'active',
            'price' => 0,
            'billing_type' => 'free',
        ]);

        $this->actingAs($other)->get(route('marketplace.manage', $installation))->assertForbidden();
    }
}
