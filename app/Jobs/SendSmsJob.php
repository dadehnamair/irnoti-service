<?php

namespace App\Jobs;

use App\Services\Sms\SmsManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued SMS delivery so web requests never block on the gateway. All
 * app SMS — OTP codes and operation notifications (docs/starter.md §44) — is
 * dispatched through here. Fails soft: a gateway error is logged, not surfaced.
 */
class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /** @param  array<int, string>  $variables */
    private function __construct(
        public string $to,
        public ?string $message = null,
        public ?string $bodyId = null,
        public array $variables = [],
    ) {}

    public static function text(string $to, string $message): self
    {
        return new self(to: $to, message: $message);
    }

    /** @param  array<int, string>  $variables */
    public static function pattern(string $to, string $bodyId, array $variables): self
    {
        return new self(to: $to, bodyId: $bodyId, variables: $variables);
    }

    public function handle(SmsManager $sms): void
    {
        if (blank($this->to)) {
            return;
        }

        if ($this->bodyId !== null) {
            $sms->sendPattern($this->to, $this->bodyId, $this->variables);

            return;
        }

        $sms->send($this->to, (string) $this->message);
    }

    public function failed(Throwable $e): void
    {
        Log::error('[sms] delivery failed', ['to' => $this->to, 'error' => $e->getMessage()]);
    }
}
