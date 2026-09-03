<?php

namespace App\Services\Messenger\Channels;

use App\Services\Messenger\ChannelSendResult;
use App\Services\Messenger\MessengerChannelInterface;

/**
 * No-op channel bound in the test environment (MESSENGER_DRIVER=null) so the
 * suite never touches a messenger API. Every recipient comes back failed, which
 * also exercises SendMessengerCampaignJob's refund path; a test that needs a
 * happy path binds LogChannel or a spy instead. Mirrors
 * App\Services\Sms\NullProvider.
 */
class NullChannel implements MessengerChannelInterface
{
    /** @param  array<string, mixed>  $config */
    public function __construct(
        private readonly string $key,
        private readonly array $config = [],
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return (string) ($this->config['label'] ?? $this->key);
    }

    public function supportsBulk(): bool
    {
        return (bool) ($this->config['bulk'] ?? true);
    }

    public function sendBulk(array $recipients, string $body, array $options = []): ChannelSendResult
    {
        $results = array_map(
            fn (string $to) => ChannelSendResult::line($to, false, null, 'درایور آزمایشی (null) — ارسال انجام نشد.'),
            array_values($recipients),
        );

        return new ChannelSendResult($results);
    }
}
