<?php

namespace App\Services\Messenger;

/**
 * Messenger channel contract (docs/starter.md §91). No controller / job may call
 * a channel API directly — everything goes through this so a new destination
 * network can be dropped in without touching callers. Mirrors
 * App\Services\Sms\SmsProviderInterface.
 */
interface MessengerChannelInterface
{
    /** Destination network codename: `bale` | `eitaa` | `whatsapp`. */
    public function key(): string;

    /** Brand-neutral display name shown to the customer (cascade-resolved). */
    public function label(): string;

    /** Whether this channel may be used for a group/list (bulk) send at all. */
    public function supportsBulk(): bool;

    /**
     * Send one body to many recipients. `$recipients` are already normalised
     * (mobiles in local 09xxxxxxxxx form, chat ids / @usernames left as-is).
     * `$options` may carry `schedule_at` (Y-m-d H:i:s). Always returns a
     * per-recipient breakdown — never throws for a single bad recipient, only
     * for a whole-channel failure (auth, network) so the job can retry.
     *
     * @param  array<int, string>  $recipients
     * @param  array<string, mixed>  $options
     */
    public function sendBulk(array $recipients, string $body, array $options = []): ChannelSendResult;
}
