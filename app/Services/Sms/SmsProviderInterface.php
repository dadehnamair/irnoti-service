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
     * Send a template ("pattern") message — provider "shared" bodyId + ordered
     * variables (docs/starter.md §14 "Pattern").
     *
     * @param  array<int, string>  $variables
     */
    public function sendPattern(string $to, string $bodyId, array $variables): ?string;

    /** Delivery status for a previously sent message id (docs/starter.md §14 "Delivery"). */
    public function deliveryStatus(string $recId): ?string;

    /**
     * The dedicated sender numbers (سرشماره) this account owns, as digit strings.
     * Empty when the driver can't report them (docs/starter.md §12).
     *
     * @return array<int, string>
     */
    public function numbers(): array;

    /**
     * Remaining panel credit as a number of SMS, or null when the driver can't
     * report it (docs/starter.md §12). Shown in the customer panel.
     */
    public function credit(): ?int;

    /**
     * Remaining panel credit as a Rial amount, or null when the driver can't
     * report it (docs/starter.md §12).
     */
    public function creditRial(): ?int;
}
