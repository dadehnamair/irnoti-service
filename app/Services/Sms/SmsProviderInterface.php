<?php

namespace App\Services\Sms;

/**
 * SMS Provider Layer (docs/starter.md §12). No controller / service may call a
 * vendor API directly — everything goes through this contract so a second
 * provider can be dropped in without touching callers.
 */
interface SmsProviderInterface
{
    /**
     * Send a plain text message. Returns the provider message/record id (or null
     * when the driver has none, e.g. the log driver).
     */
    public function send(string $to, string $message, ?string $from = null): ?string;

    /**
     * Send a template ("pattern") message — Melipayamak "shared" bodyId + ordered
     * variables (docs/starter.md §14 "Pattern").
     *
     * @param  array<int, string>  $variables
     */
    public function sendPattern(string $to, string $bodyId, array $variables): ?string;

    /** Delivery status for a previously sent message id (docs/starter.md §14 "Delivery"). */
    public function deliveryStatus(string $recId): ?string;
}
