<?php

namespace App\Services\Messenger;

/**
 * Outcome of one {@see MessengerChannelInterface::sendBulk()} call
 * (docs/starter.md §91). `$batchId` is the channel's own reference for the whole
 * dispatch when it has one; `$results` carries the per-recipient outcome so
 * SendMessengerCampaignJob can write one messenger_recipients row each and refund
 * the failed portion.
 */
class ChannelSendResult
{
    /**
     * @param  array<int, array{to: string, ok: bool, ref: ?string, error: ?string}>  $results
     */
    public function __construct(
        public readonly array $results,
        public readonly ?string $batchId = null,
    ) {}

    /** @return array<int, array{to: string, ok: bool, ref: ?string, error: ?string}> */
    public static function line(string $to, bool $ok, ?string $ref = null, ?string $error = null): array
    {
        return ['to' => $to, 'ok' => $ok, 'ref' => $ref, 'error' => $error];
    }

    public function successCount(): int
    {
        return count(array_filter($this->results, fn ($r) => $r['ok']));
    }

    public function failedCount(): int
    {
        return count($this->results) - $this->successCount();
    }
}
