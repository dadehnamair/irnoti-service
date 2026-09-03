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
     * A page of the account's message archive as the provider reports it — the
     * «پیام‌ها» menu (docs/starter.md §14). `$location`: 1 = received / inbox,
     * 2 = sent, -1 = both. `$index` is the zero-based row offset and `$count`
     * the window size; `$from` filters to one sender line (سرشماره) when given.
     *
     * Each row: msg_id, body, sender, receiver, date (raw provider string),
     * parts, rec_count, rec_success, rec_failed. Empty list when the driver
     * can't report an archive (api_key / log / test drivers).
     *
     * @return array<int, array{msg_id: string, body: string, sender: string, receiver: string, date: string, parts: int, rec_count: int, rec_success: int, rec_failed: int}>
     */
    public function messages(int $location, int $index = 0, int $count = 100, ?string $from = null): array;

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
