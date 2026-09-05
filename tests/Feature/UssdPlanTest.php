<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UssdPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_ussd_page_lists_only_ussd_plans(): void
    {
        Plan::create([
            'slug' => 'ussd-basic', 'name' => 'کد دستوری پایه', 'type' => 'ussd',
            'price_monthly' => 40000000, 'is_active' => true,
        ]);
        Plan::create([
            'slug' => 'sub-basic', 'name' => 'پلن پایه', 'type' => 'subscription',
            'price_monthly' => 990000, 'is_active' => true,
        ]);

        $response = $this->get('/ussd')->assertOk();
        $response->assertSee('کد دستوری پایه');
        $response->assertDontSee('پلن پایه');
    }

    public function test_ussd_plan_checkout_reuses_subscription_flow(): void
    {
        $plan = Plan::create([
            'slug' => 'ussd-basic', 'name' => 'کد دستوری پایه', 'type' => 'ussd',
            'price_monthly' => 0, 'is_active' => true,
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('subscriptions.order'), [
            'plan' => $plan->slug,
            'period' => 'monthly',
        ]);

        $subscription = $user->subscriptions()->firstOrFail();
        $this->assertSame('active', $subscription->status);
        $response->assertRedirect(route('subscriptions.show', $subscription));
    }
}
