<?php

namespace App\Support;

use Closure;
use Illuminate\Http\Request;
use Shetabit\Multipay\Invoice;
use Shetabit\Multipay\Receipt;
use Shetabit\Payment\Facade\Payment;

/**
 * Shared shetabit/multipay plumbing for the two things that get paid online:
 * dedicated-line orders (docs/starter.md §11) and plan subscriptions
 * (docs/starter.md §8/§24). Controllers keep their own redirect/flash flow;
 * this only wraps the gateway calls and the driver-specific request parsing.
 */
trait HandlesGatewayPayment
{
    /**
     * Send an amount (Toman) to the gateway and return the rendered redirect
     * form. `$onTransactionId` receives the gateway transaction id to persist.
     */
    protected function purchaseViaGateway(int $amount, string $callbackUrl, Closure $onTransactionId): mixed
    {
        $invoice = (new Invoice)->amount($amount);

        return Payment::callbackUrl($callbackUrl)
            ->purchase($invoice, function ($driver, $transactionId) use ($onTransactionId) {
                $onTransactionId($transactionId);
            })
            ->pay()
            ->render();
    }

    /** Verify a returned payment; throws Shetabit\Multipay\Exceptions\InvalidPaymentException on failure. */
    protected function verifyViaGateway(int $amount, string $transactionId): Receipt
    {
        return Payment::amount($amount)
            ->transactionId($transactionId)
            ->verify();
    }

    /** The gateway transaction id, however this driver spells it in the callback. */
    protected function gatewayTransactionId(Request $request): ?string
    {
        return $request->input('transactionId')
            ?? $request->input('transaction_id')
            ?? $request->input('Authority')
            ?? $request->input('authority');
    }

    /** Did the buyer cancel on the gateway? (local: ?cancel=true, zarinpal: Status=NOK) */
    protected function gatewayPaymentCancelled(Request $request): bool
    {
        return $request->boolean('cancel') || $request->input('Status') === 'NOK';
    }
}
