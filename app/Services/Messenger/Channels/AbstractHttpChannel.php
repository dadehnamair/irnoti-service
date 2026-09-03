<?php

namespace App\Services\Messenger\Channels;

use App\Services\Messenger\ChannelSendResult;
use App\Services\Messenger\MessengerChannelInterface;
use App\Services\Messenger\MessengerChannelNotConfiguredException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Shared plumbing for the real HTTP messenger drivers (docs/starter.md §91):
 * config access, the bulk loop, per-recipient error capture. A concrete channel
 * only implements {@see sendOne()} for its own API shape. The name of the
 * upstream aggregator that carries the message never appears here or in logs —
 * only the destination network key.
 */
abstract class AbstractHttpChannel implements MessengerChannelInterface
{
    /** @param  array<string, mixed>  $config */
    public function __construct(
        protected readonly string $key,
        protected readonly array $config = [],
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
        $this->assertConfigured();

        $results = [];

        foreach ($recipients as $to) {
            try {
                $ref = $this->sendOne((string) $to, $body, $options);
                $results[] = ChannelSendResult::line((string) $to, true, $ref);
            } catch (MessengerChannelNotConfiguredException $e) {
                throw $e; // whole-channel problem — let the job treat it as terminal
            } catch (Throwable $e) {
                Log::warning('[messenger] recipient send failed', [
                    'channel' => $this->key,
                    'to' => $to,
                    'error' => $e->getMessage(),
                ]);
                $results[] = ChannelSendResult::line((string) $to, false, null, mb_substr($e->getMessage(), 0, 250));
            }
        }

        return new ChannelSendResult($results);
    }

    /**
     * Deliver one message. Returns the channel's message reference, or throws on
     * failure (a per-recipient failure is caught above; throw
     * {@see MessengerChannelNotConfiguredException} for an auth/config problem
     * that should stop the whole batch).
     *
     * @param  array<string, mixed>  $options
     */
    abstract protected function sendOne(string $to, string $body, array $options): ?string;

    protected function token(): ?string
    {
        return $this->config['token'] ?? null;
    }

    protected function baseUrl(): string
    {
        return rtrim((string) ($this->config['base_url'] ?? ''), '/');
    }

    protected function assertConfigured(): void
    {
        if (blank($this->token())) {
            throw new MessengerChannelNotConfiguredException(
                "کانال «{$this->label()}» پیکربندی نشده است (توکن تنظیم نشده).",
            );
        }
    }
}
