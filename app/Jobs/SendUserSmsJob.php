<?php

namespace App\Jobs;

use App\Models\SmsMessage;
use App\Services\Sms\SmsPanelNotConfiguredException;
use App\Services\Sms\UserSmsGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued single-SMS send from a customer's own panel (docs/starter.md §12). The
 * web request only records the {@see SmsMessage} row as "queued" and dispatches
 * this — the customer never waits on the gateway. The row moves to "sent"/"failed"
 * here, and the cached panel credit is dropped so the next read is fresh.
 */
class SendUserSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $messageId) {}

    public function handle(): void
    {
        $message = SmsMessage::with('user')->find($this->messageId);

        if (! $message || $message->status === 'sent') {
            return;
        }

        $user = $message->user;

        if (! $user) {
            $message->update(['status' => 'failed', 'error' => 'کاربر یافت نشد.']);

            return;
        }

        try {
            $recId = UserSmsGateway::for($user)->send($message->to, $message->body, $message->from);
            $message->update(['status' => 'sent', 'rec_id' => $recId]);
        } catch (SmsPanelNotConfiguredException $e) {
            // Config problem, not a transient one — don't burn retries on it.
            $message->update(['status' => 'failed', 'error' => $e->getMessage()]);
        } finally {
            Cache::forget("sms_credit:{$user->id}");
        }
    }

    public function failed(Throwable $e): void
    {
        SmsMessage::whereKey($this->messageId)->where('status', '!=', 'sent')->update([
            'status' => 'failed',
            'error' => mb_substr($e->getMessage(), 0, 250),
        ]);

        Log::error('Customer SMS send failed', ['message' => $this->messageId, 'error' => $e->getMessage()]);
    }
}
