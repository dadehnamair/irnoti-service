<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Support\HandlesGatewayPayment;
use App\Support\OperationNotifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;

/**
 * Plan selection & purchase for logged-in customers (docs/starter.md §8 / §24).
 * A free plan (price 0) activates immediately; a paid one goes through the same
 * shetabit/multipay flow as line orders, gated by the "plan_payment_online"
 * admin setting.
 */
class SubscriptionController extends Controller
{
    use HandlesGatewayPayment;

    private const PERIODS = ['monthly', 'yearly'];

    /** Plan catalogue for the account panel. */
    public function plans(Request $request): View
    {
        return view('dashboard.plans', [
            'user' => $request->user(),
            'plans' => Plan::query()->active()->ordered()->get(),
        ]);
    }

    public function checkout(Request $request, Plan $plan): View
    {
        abort_unless($plan->is_active, 404);

        $period = $this->period($request);
        $price = $plan->priceFor($period);

        return view('dashboard.plan-checkout', [
            'plan' => $plan,
            'period' => $period,
            'price' => $price,
            'onlinePayment' => $price > 0 && $this->onlinePaymentEnabled(),
        ]);
    }

    public function order(Request $request, OperationNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', Rule::exists('plans', 'slug')->where('is_active', true)],
            'period' => ['required', Rule::in(self::PERIODS)],
        ], [], ['plan' => 'پلن', 'period' => 'دوره']);

        $plan = Plan::where('slug', $data['plan'])->firstOrFail();
        $period = $data['period'];
        $price = $plan->priceFor($period);
        $user = $request->user();

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'billing_period' => $period,
            'price' => $price,
            'status' => $price > 0 ? 'awaiting_payment' : 'active',
            'starts_at' => $price > 0 ? null : now(),
            'expires_at' => $price > 0 ? null : $this->expiryFrom($plan),
        ]);

        session()->forget('intended_plan');

        if ($price > 0 && $this->onlinePaymentEnabled()) {
            return redirect()->route('subscriptions.pay', $subscription);
        }

        if ($price > 0) {
            // Online payment disabled — leave it for the admin to process.
            return redirect()->route('subscriptions.show', $subscription)
                ->with('auth_status', 'درخواست پلن ثبت شد. کارشناسان برای هماهنگی پرداخت با شما تماس می‌گیرند.');
        }

        $this->activate($subscription, $notifier);

        return redirect()->route('subscriptions.show', $subscription)
            ->with('auth_status', 'پلن رایگان با موفقیت فعال شد.');
    }

    public function pay(Subscription $subscription)
    {
        abort_unless($subscription->user_id === request()->user()->id, 403);

        if (! $this->onlinePaymentEnabled() || ! $subscription->isPayable()) {
            return redirect()->route('subscriptions.show', $subscription);
        }

        try {
            return $this->purchaseViaGateway(
                (int) $subscription->price,
                route('subscriptions.payment.callback'),
                fn($transactionId) => $subscription->update([
                    'transaction_id' => $transactionId,
                    'payment_driver' => config('payment.default'),
                ]),
            );
        } catch (\Throwable $e) {
            Log::error('Subscription payment purchase failed', ['subscription' => $subscription->id, 'error' => $e->getMessage()]);

            return redirect()->route('subscriptions.show', $subscription)
                ->with('payment_error', 'اتصال به درگاه پرداخت ممکن نشد. لطفاً بعداً دوباره تلاش کنید.');
        }
    }

    public function paymentCallback(Request $request, OperationNotifier $notifier): RedirectResponse
    {
        $transactionId = $this->gatewayTransactionId($request);

        $subscription = Subscription::query()
            ->when($transactionId, fn($q) => $q->where('transaction_id', $transactionId))
            ->latest('id')
            ->first();

        if (! $subscription) {
            return redirect()->route('dashboard.plans')->with('payment_error', 'اشتراک مربوط به این پرداخت پیدا نشد.');
        }

        if ($this->gatewayPaymentCancelled($request)) {
            return redirect()->route('subscriptions.show', $subscription)
                ->with('payment_error', 'پرداخت توسط شما لغو شد. می‌توانید دوباره تلاش کنید.');
        }

        try {
            $receipt = $this->verifyViaGateway((int) $subscription->price, $subscription->transaction_id);

            $subscription->update([
                'reference_id' => $receipt->getReferenceId(),
                'paid_at' => now(),
            ]);

            $this->activate($subscription, $notifier);

            return redirect()->route('subscriptions.show', $subscription)->with('payment_success', true);
        } catch (InvalidPaymentException $e) {
            return redirect()->route('subscriptions.show', $subscription)
                ->with('payment_error', $e->getMessage() ?: 'پرداخت ناموفق بود یا لغو شد.');
        } catch (\Throwable $e) {
            Log::error('Subscription payment verify failed', ['subscription' => $subscription->id, 'error' => $e->getMessage()]);

            return redirect()->route('subscriptions.show', $subscription)
                ->with('payment_error', 'تأیید پرداخت با خطا مواجه شد. اگر مبلغ کسر شده با پشتیبانی تماس بگیرید.');
        }
    }

    public function show(Request $request, Subscription $subscription): View
    {
        abort_unless($subscription->user_id === $request->user()->id, 403);

        return view('dashboard.subscription', [
            'subscription' => $subscription,
            'canPayOnline' => $this->onlinePaymentEnabled() && $subscription->isPayable(),
        ]);
    }

    /** Mark active, roll the plan onto the user, notify (docs/starter.md §44). */
    private function activate(Subscription $subscription, OperationNotifier $notifier): void
    {
        $subscription->forceFill([
            'status' => 'active',
            'starts_at' => $subscription->starts_at ?? now(),
            'expires_at' => $subscription->expires_at ?? $this->expiryFrom($subscription->plan),
        ])->save();

        if ($user = $subscription->user) {
            $user->forceFill([
                'plan_id' => $subscription->plan_id,
                'plan_expires_at' => $subscription->expires_at,
            ])->save();

            // Buying a plan doesn't activate the account — an admin still has to
            // approve it (docs/starter.md §39). This only moves pending accounts
            // to "awaiting_approval" once the profile is also complete.
            $user->refreshApprovalState();
        }

        $notifier->subscriptionActivated($subscription);
    }

    private function expiryFrom(?Plan $plan): ?Carbon
    {
        $days = $plan?->duration_days;

        return $days ? now()->addDays($days) : null;
    }

    private function period(Request $request): string
    {
        $period = (string) $request->query('period', session('intended_plan.period', 'monthly'));

        return in_array($period, self::PERIODS, true) ? $period : 'monthly';
    }

    private function onlinePaymentEnabled(): bool
    {
        return (bool) Setting::get('plan_payment_online', false);
    }
}
