<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_plan_has_its_own_indexable_detail_page(): void
    {
        $plan = Plan::create([
            'slug' => 'pro', 'name' => 'حرفه‌ای', 'type' => 'subscription',
            'price_monthly' => 990000, 'is_active' => true,
        ]);

        $response = $this->get(route('pricing.show', $plan->slug))->assertOk();

        $response->assertSee('حرفه‌ای');
        $response->assertSee(route('pricing.show', $plan->slug), false);
        $response->assertSee('"@type":"Product"', false);
    }

    public function test_inactive_plan_detail_page_is_not_found(): void
    {
        $plan = Plan::create([
            'slug' => 'retired', 'name' => 'بازنشسته', 'type' => 'subscription',
            'price_monthly' => 990000, 'is_active' => false,
        ]);

        $this->get(route('pricing.show', $plan->slug))->assertNotFound();
    }

    public function test_pricing_catalogue_links_to_each_plan_detail_page(): void
    {
        $plan = Plan::create([
            'slug' => 'pro', 'name' => 'حرفه‌ای', 'type' => 'subscription',
            'price_monthly' => 990000, 'is_active' => true,
        ]);

        $this->get(route('pricing'))->assertOk()
            ->assertSee(route('pricing.show', $plan->slug), false);
    }

    public function test_sitemap_lists_every_active_plan(): void
    {
        $plan = Plan::create([
            'slug' => 'pro', 'name' => 'حرفه‌ای', 'type' => 'subscription',
            'price_monthly' => 990000, 'is_active' => true,
        ]);

        $this->get(route('sitemap'))->assertOk()
            ->assertSee(route('pricing.show', $plan->slug), false);
    }
}
