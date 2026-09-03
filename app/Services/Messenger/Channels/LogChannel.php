<?php

namespace App\Services\Messenger\Channels;

use App\Services\Messenger\ChannelSendResult;
use App\Services\Messenger\MessengerChannelInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Credential-free channel for local dev & staging (MESSENGER_DRIVER=log). Writes
 * each recipient to the application log instead of hitting a messenger API — the
 * same idea as App\Services\Sms\LogProvider and the bundled "local" payment
 * driver. Never use in production.
 */
class LogChannel implements MessengerChannelInterface
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
        $batchId = (string) Str::uuid();

        $results = [];

        foreach ($recipients as $to) {
            $ref = $batchId.':'.Str::random(8);

            Log::channel(config('logging.default'))->info('[messenger:log] send', [
                'channel' => $this->key,
                'batch' => $batchId,
                'ref' => $ref,
                'to' => $to,
                'body' => $body,
                'schedule_at' => $options['schedule_at'] ?? null,
            ]);

            $results[] = ChannelSendResult::line($to, true, $ref);
        }

        return new ChannelSendResult($results, $batchId);
    }
}
