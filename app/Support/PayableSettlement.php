<?php

namespace App\Support;

use App\Models\BusinessCard;
use App\Models\Invoice;
use App\Models\LineOrder;
use App\Models\MarketplaceInstallation;
use App\Models\PackageOrder;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\WalletTopup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The single place that turns "this thing has been paid" into its domain effect,
 * no matter how it was paid — online gateway, wallet balance, or an approved bank
 * receipt (docs/starter.md §25). Every branch is idempotent: calling it twice for
 * the same record is a no-op the second time.
 */
class PayableSettlement
{
    public function __construct(private OperationNotifier $notifier) {}

    /**
     * @param  array{method?:string,transaction_id?:?string,reference_id?:?string,payment_driver?:?string}  $payment
     */
    public function settle(Model $payable, array $payment = []): void
    {
        match (true) {
            $payable instanceof WalletTopup => $this->settleTopup($payable, $payment),
            $payable instanceof Subscription => $this->settleSubscription($payable, $payment),
            $payable instanceof LineOrder => $this->settleLineOrder($payable, $payment),
            $payable instanceof BusinessCard => $this->settleBusinessCard($payable, $payment),
            $payable instanceof PackageOrder => $this->settlePackageOrder($payable, $payment),
            $payable instanceof MarketplaceInstallation => $this->settleMarketplaceInstallation($payable, $payment),
            $payable instanceof Invoice => $this->settleInvoice($payable, $payment),
            default => throw new \InvalidArgumentException('Unsupported payable: '.$payable::class),
        };
    }

    private function settleTopup(WalletTopup $topup, array $payment): void
    {
        if ($topup->status === 'paid') {
            return;
        }

        $topup->forceFill([
            'status' => 'paid',
            'method' => $payment['method'] ?? $topup->method,
            'transaction_id' => $payment['transaction_id'] ?? $topup->transaction_id,
            'reference_id' => $payment['reference_id'] ?? $topup->reference_id,
            'payment_driver' => $payment['payment_driver'] ?? $topup->payment_driver,
            'paid_at' => $topup->paid_at ?? now(),
        ])->save();

        $topup->user->wallet()->credit(
            (int) $topup->amount,
            'topup',
            $topup,
            'شارژ کیف پول',
            "topup:{$topup->id}",
            array_filter(['method' => $payment['method'] ?? $topup->method]),
        );

        $this->notifier->walletToppedUp($topup->user, (int) $topup->amount);
    }

    /** Mirrors the old SubscriptionController::activate() (docs/starter.md §8 / §44). */
    private function settleSubscription(Subscription $subscription, array $payment): void
    {
        if (in_array($subscription->status, ['active', 'paid'], true)) {
            return;
        }

        $subscription->forceFill([
            'status' => 'active',
            'transaction_id' => $payment['transaction_id'] ?? $subscription->transaction_id,
            'reference_id' => $payment['reference_id'] ?? $subscription->reference_id,
            'payment_driver' => $payment['payment_driver'] ?? $subscription->payment_driver,
            'paid_at' => $subscription->paid_at ?? ((int) $subscription->price > 0 ? now() : null),
            'starts_at' => $subscription->starts_at ?? now(),
            'expires_at' => $subscription->expires_at ?? $this->expiryFrom($subscription->plan),
        ])->save();

        if ($user = $subscription->user) {
            $user->forceFill([
                'plan_id' => $subscription->plan_id,
                'plan_expires_at' => $subscription->expires_at,
            ])->save();

            $user->refreshApprovalState();

            if ((int) ($subscription->plan?->sms_count ?? 0) > 0) {
                $user->increment('sms_credit', (int) $subscription->plan->sms_count);
            }
        }

        $this->notifier->subscriptionActivated($subscription);
    }

    private function settleLineOrder(LineOrder $order, array $payment): void
    {
        if (in_array($order->status, ['paid', 'processing', 'completed'], true)) {
            return;
        }

        $order->forceFill([
            'status' => 'paid',
            'transaction_id' => $payment['transaction_id'] ?? $order->transaction_id,
            'reference_id' => $payment['reference_id'] ?? $order->reference_id,
            'payment_driver' => $payment['payment_driver'] ?? $order->payment_driver,
            'paid_at' => $order->paid_at ?? now(),
        ])->save();

        // «باندل اختصاصی خط» (docs/lines-landing.md): grant the bundled SMS credit
        // to the buyer's account — mirrors settlePackageOrder(). Guests get it
        // added by the admin during the status workflow.
        if ($order->line_bundle_id && $order->user && (int) $order->sms_credit > 0) {
            $order->user->increment('sms_credit', (int) $order->sms_credit);
        }

        $this->notifier->lineOrderPaid($order);
    }

    private function settleBusinessCard(BusinessCard $card, array $payment): void
    {
        if ($card->status === 'active') {
            return;
        }

        $card->forceFill([
            'status' => 'active',
            'transaction_id' => $payment['transaction_id'] ?? $card->transaction_id,
            'reference_id' => $payment['reference_id'] ?? $card->reference_id,
            'payment_driver' => $payment['payment_driver'] ?? $card->payment_driver,
            'paid_at' => $card->paid_at ?? now(),
        ])->save();

        $this->notifier->businessCardPaid($card);
    }

    private function settlePackageOrder(PackageOrder $order, array $payment): void
    {
        if (in_array($order->status, ['paid', 'completed'], true)) {
            return;
        }

        $order->forceFill([
            'status' => 'completed',
            'transaction_id' => $payment['transaction_id'] ?? $order->transaction_id,
            'reference_id' => $payment['reference_id'] ?? $order->reference_id,
            'payment_driver' => $payment['payment_driver'] ?? $order->payment_driver,
            'paid_at' => $order->paid_at ?? now(),
        ])->save();

        if ($user = $order->user) {
            $user->increment('sms_credit', (int) $order->sms_count);
        }

        $this->notifier->packageActivated($order);
    }

    /**
     * A «بازارچه» add-on purchase settled (docs/starter.md §15). Runs the handler's
     * onActivate() (create groups, grant capability features) and pushes the
     * subscription expiry forward. Idempotent while still active & unexpired.
     */
    private function settleMarketplaceInstallation(MarketplaceInstallation $installation, array $payment): void
    {
        if ($installation->status === 'active' && ! $installation->isExpired()) {
            return;
        }

        $installation->forceFill([
            'status' => 'active',
            'transaction_id' => $payment['transaction_id'] ?? $installation->transaction_id,
            'reference_id' => $payment['reference_id'] ?? $installation->reference_id,
            'payment_driver' => $payment['payment_driver'] ?? $installation->payment_driver,
            'paid_at' => (int) $installation->price > 0 ? now() : $installation->paid_at,
            'activated_at' => $installation->activated_at ?? now(),
            'expires_at' => $this->marketplaceExpiryFrom($installation),
        ])->save();

        $installation->handler()->onActivate($installation);

        $this->notifier->marketplaceAppActivated($installation);
    }

    private function marketplaceExpiryFrom(MarketplaceInstallation $installation): ?Carbon
    {
        if ($installation->billing_type !== 'subscription') {
            return null;
        }

        // Renewals extend from the current expiry when it is still in the future.
        $from = $installation->expires_at && $installation->expires_at->isFuture()
            ? $installation->expires_at->copy()
            : now();

        return $installation->billing_period === 'yearly'
            ? $from->addYear()
            : $from->addMonth();
    }

    private function settleInvoice(Invoice $invoice, array $payment): void
    {
        if ($invoice->status === 'paid') {
            return;
        }

        $invoice->forceFill([
            'status' => 'paid',
            'payment_method' => $payment['method'] ?? $invoice->payment_method,
            'paid_at' => $invoice->paid_at ?? now(),
        ])->save();

        $this->notifier->invoicePaid($invoice);
    }

    private function expiryFrom(?Plan $plan): ?Carbon
    {
        $days = $plan?->duration_days;

        return $days ? now()->addDays($days) : null;
    }
}
