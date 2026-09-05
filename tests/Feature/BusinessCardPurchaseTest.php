<?php

namespace Tests\Feature;

use App\Models\BusinessCard;
use App\Models\Domain;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BusinessCardPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private function domain(array $overrides = []): Domain
    {
        // Matches the host Laravel's test client uses for relative URLs
        // (config('app.url') = http://localhost) so RedirectForeignDomain
        // doesn't bounce dashboard requests made in tests that don't care
        // about host resolution themselves.
        return Domain::create(array_merge([
            'host' => 'localhost',
            'is_active' => true,
            'is_default' => true,
        ], $overrides));
    }

    private function setSetting(string $key, string $value, string $type = 'bool'): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value, 'type' => $type, 'group' => 'commerce']);
        Cache::forget(Setting::CACHE_KEY);
    }

    public function test_standard_card_activates_instantly_when_free(): void
    {
        $this->setSetting('business_card_standard_price', '0', 'string');
        $domain = $this->domain();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/dashboard/cards', [
            'tier' => 'standard',
            'domain_id' => $domain->id,
            'code' => 'ali',
        ]);

        $card = BusinessCard::firstOrFail();
        $response->assertRedirect(route('dashboard.cards.edit', $card));
        $this->assertSame('active', $card->status);
        $this->assertSame(0, $card->price);
        $this->assertSame($user->id, $card->user_id);
    }

    public function test_standard_card_awaits_payment_when_priced(): void
    {
        $this->setSetting('business_card_standard_price', '600000', 'string');
        $domain = $this->domain();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/cards', [
            'tier' => 'standard',
            'domain_id' => $domain->id,
            'code' => 'ali',
        ]);

        $card = BusinessCard::firstOrFail();
        $this->assertSame('awaiting_payment', $card->status);
        $this->assertSame(600000, $card->price);
    }

    public function test_vip_card_price_comes_from_matching_domain_tier(): void
    {
        $domain = $this->domain([
            'code_price_tiers' => [
                ['type' => 'numeric', 'min_length' => 1, 'max_length' => 1, 'price' => 25000000],
                ['type' => 'alpha', 'min_length' => 2, 'max_length' => 10, 'price' => 8000000],
            ],
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/cards', [
            'tier' => 'vip',
            'domain_id' => $domain->id,
            'code' => 'ali',
        ])->assertRedirect();

        $card = BusinessCard::firstOrFail();
        $this->assertSame(8000000, $card->price);
        $this->assertSame('awaiting_payment', $card->status);
    }

    public function test_vip_code_without_a_matching_tier_is_rejected(): void
    {
        $domain = $this->domain([
            'code_price_tiers' => [
                ['type' => 'numeric', 'min_length' => 1, 'max_length' => 1, 'price' => 25000000],
            ],
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/cards', [
            'tier' => 'vip',
            'domain_id' => $domain->id,
            'code' => 'toolongofacode',
        ])->assertSessionHasErrors('code');

        $this->assertSame(0, BusinessCard::count());
    }

    public function test_code_must_be_unique_per_domain(): void
    {
        $domain = $this->domain();
        $other = User::factory()->create();
        BusinessCard::create([
            'user_id' => $other->id,
            'domain_id' => $domain->id,
            'tier' => 'standard',
            'code' => 'ali',
            'price' => 0,
            'status' => 'active',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/cards', [
            'tier' => 'standard',
            'domain_id' => $domain->id,
            'code' => 'ali',
        ])->assertSessionHasErrors('code');
    }

    public function test_gateway_callback_marks_card_active(): void
    {
        $domain = $this->domain();
        $user = User::factory()->create();
        $card = BusinessCard::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'tier' => 'standard',
            'code' => 'ali',
            'price' => 600000,
            'status' => 'awaiting_payment',
            'transaction_id' => '1234567',
        ]);

        $this->get('/cards/payment/callback?transactionId=1234567')
            ->assertRedirect(route('dashboard.cards.edit', $card));

        $card->refresh();
        $this->assertSame('active', $card->status);
        $this->assertNotNull($card->paid_at);
        $this->assertNotNull($card->reference_id);
    }

    public function test_public_card_page_resolves_by_host(): void
    {
        $domain = $this->domain(['host' => 'irnoti.test']);
        $user = User::factory()->create();
        BusinessCard::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'tier' => 'standard',
            'code' => 'ali',
            'title' => 'Ali Test',
            'price' => 0,
            'status' => 'active',
        ]);

        $this->get('http://irnoti.test/ali')->assertOk()->assertSee('Ali Test');
    }

    public function test_public_card_page_404s_on_unknown_domain(): void
    {
        $domain = $this->domain(['host' => 'irnoti.test']);
        $user = User::factory()->create();
        BusinessCard::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'tier' => 'standard',
            'code' => 'ali',
            'price' => 0,
            'status' => 'active',
        ]);

        // Not the default domain and not a registered vanity domain either —
        // RedirectForeignDomain bounces it to the main site before routing.
        $this->get('http://unknown.test/ali')->assertRedirect('http://irnoti.test/ali');
    }

    public function test_vanity_domain_bare_index_redirects_to_main_site(): void
    {
        $this->domain(['host' => 'irnoti.test']);
        $this->domain(['host' => '11v.ir', 'is_default' => false]);

        $this->get('http://11v.ir/')->assertRedirect('http://irnoti.test/');
    }

    public function test_vanity_domain_with_code_path_is_not_redirected(): void
    {
        $domain = $this->domain(['host' => 'irnoti.test']);
        $vanity = $this->domain(['host' => '11v.ir', 'is_default' => false]);
        $user = User::factory()->create();
        BusinessCard::create([
            'user_id' => $user->id,
            'domain_id' => $vanity->id,
            'tier' => 'vip',
            'code' => 'ali',
            'title' => 'Ali Vanity',
            'price' => 0,
            'status' => 'active',
        ]);

        $this->get('http://11v.ir/ali')->assertOk()->assertSee('Ali Vanity');
    }
}
