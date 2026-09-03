<?php

namespace App\Jobs;

use App\Models\SmsMessage;
use App\Services\Sms\Phonebook\UserPhonebook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued group send (docs/starter.md §17/§18): hands the whole job to the
 * provider's group-send API from the worker so the customer request returns
 * straight away. The {@see SmsMessage} row it references carries the recipient
 * summary, body and sender; this job supplies the remote group ids + schedule.
 *
 * @param  array<int, int|string>  $remoteGroupIds
 */
class SendContactGroupSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /** @param  array<int, int|string>  $remoteGroupIds */
    public function __construct(
        public int $messageId,
        public array $remoteGroupIds,
        public ?string $scheduleAt = null,
    ) {}

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

        $bulkId = UserPhonebook::for($user)->sendToGroups(
            $this->remoteGroupIds,
            $message->body,
            $message->from,
            null,
            $this->scheduleAt,
        );

        $message->update(['status' => 'sent', 'rec_id' => $bulkId]);
        Cache::forget("sms_credit:{$user->id}");
    }

    public function failed(Throwable $e): void
    {
        SmsMessage::whereKey($this->messageId)->where('status', '!=', 'sent')->update([
            'status' => 'failed',
            'error' => mb_substr($e->getMessage(), 0, 250),
        ]);

        Log::error('[phonebook] group send failed', ['message' => $this->messageId, 'error' => $e->getMessage()]);
    }
}
