<?php

namespace Tests\Feature\Dashboard;

use App\Models\OtpCode;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Plan selection & purchase (docs/starter.md §8 / §24).
 */
class PlanSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function plan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'name' => 'پلن تست',
            'slug' => 'test-plan',
            'price_monthly' => 990000,
            'duration_days' => 30,
            'is_active' => true,
        ], $overrides));
    }

    private function setPlanOnlinePayment(bool $on): void
    {
        Setting::updateOrCreate(['key' => 'plan_payment_online'], [
            'value' => $on ? '1' : '0',
            'type' => 'bool',
            'group' => 'commerce',
        ]);
        Cache::forget(Setting::CACHE_KEY);
    }

    public function test_free_plan_activates_instantly(): void
    {
        $plan = $this->plan(['slug' => 'free', 'name' => 'رایگان', 'price_monthly' => 0, 'duration_days' => null]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.plan.checkout', ['plan' => 'free']))
            ->assertOk()
            ->assertSee('رایگان');

        $response = $this->actingAs($user)->post(route('subscriptions.order'), [
            'plan' => 'free',
            'period' => 'monthly',
        ]);

        $subscription = Subscription::firstOrFail();
        $response->assertRedirect(route('subscriptions.show', $subscription));

        $this->assertSame('active', $subscription->status);
        $this->assertNotNull($subscription->starts_at);
        $this->assertSame($plan->id, $user->fresh()->plan_id);
    }

    public function test_paid_plan_without_online_payment_awaits_admin(): void
    {
        $this->setPlanOnlinePayment(false);
        $plan = $this->plan();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('subscriptions.order'), [
            'plan' => $plan->slug,
            'period' => 'monthly',
        ]);

        $subscription = Subscription::firstOrFail();
        $response->assertRedirect(route('subscriptions.show', $subscription));
        $this->assertSame('awaiting_payment', $subscription->status);
        $this->assertNull($user->fresh()->plan_id);
    }

    public function test_paid_plan_goes_through_the_gateway_and_activates_on_callback(): void
    {
        $this->setPlanOnlinePayment(true);
        $plan = $this->plan();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('subscriptions.order'), [
            'plan' => $plan->slug,
            'period' => 'yearly',
        ])->assertRedirect();

        $subscription = Subscription::firstOrFail();
        $this->assertSame('awaiting_payment', $subscription->status);
        $this->assertSame(9900000, $subscription->price);

        $this->actingAs($user)->get(route('subscriptions.pay', $subscription))
            ->assertOk()
            ->assertSee('درگاه پرداخت تست');

        $txId = $subscription->fresh()->transaction_id;
        $this->assertNotNull($txId);

        $this->get("/subscriptions/payment/callback?transactionId={$txId}")
            ->assertRedirect(route('subscriptions.show', $subscription));

        $subscription->refresh();
        $this->assertSame('active', $subscription->status);
        $this->assertNotNull($subscription->paid_at);
        $this->assertSame($plan->id, $user->fresh()->plan_id);
        $this->assertNotNull($user->fresh()->plan_expires_at);
    }

    public function test_plan_chosen_on_pricing_survives_registration(): void
    {
        $this->plan(['slug' => 'basic', 'name' => 'پایه']);

        $this->get(route('register', ['plan' => 'basic', 'period' => 'monthly']))
            ->assertOk()
            ->assertSessionHas('intended_plan', ['slug' => 'basic', 'period' => 'monthly']);

        $user = User::factory()->pending()->create(['mobile' => '09120000900']);
        [$code] = OtpCode::issue('09120000900', 'register');

        $this->withSession([
            'otp' => ['mobile' => '09120000900', 'purpose' => 'register'],
            'intended_plan' => ['slug' => 'basic', 'period' => 'monthly'],
        ])->post('/verify', ['code' => $code])
            ->assertRedirect(route('dashboard.plan.checkout', ['plan' => 'basic']));
    }

    public function test_cannot_view_another_users_subscription(): void
    {
        $plan = $this->plan();
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $subscription = Subscription::create([
            'user_id' => $owner->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'billing_period' => 'monthly',
            'price' => $plan->price_monthly,
            'status' => 'awaiting_payment',
        ]);

        $this->actingAs($other)->get(route('subscriptions.show', $subscription))->assertForbidden();
    }
}
