<?php

namespace App\Jobs;

use App\Models\ProviderMessage;
use App\Models\User;
use App\Services\Sms\UserSmsGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Refreshes the local mirror of a customer's provider-side message archive
 * (docs/starter.md §14) — the «پیام‌ها» menu. Dispatched (debounced) whenever the
 * دریافتی / ارسالی pages are opened, plus on the manual «بروزرسانی» button, so the
 * pages themselves only ever read {@see ProviderMessage} rows and never wait on
 * the gateway.
 *
 * Only the first page of each box is pulled — enough to keep the recent history
 * current; older rows already stored stay put.
 */
class SyncProviderMessagesJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 30;

    /** Collapse repeated dispatches for the same user within this window (seconds). */
    public int $uniqueFor = 90;

    /** How many rows to ask the provider for per box. */
    private const WINDOW = 200;

    public function __construct(public int $userId) {}

    public function uniqueId(): string
    {
        return (string) $this->userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user || ! $user->hasSmsPanel()) {
            return;
        }

        $gateway = UserSmsGateway::for($user);

        foreach (['inbox' => 1, 'sent' => 2] as $direction => $location) {
            try {
                $rows = $gateway->messages($location, 0, self::WINDOW);
            } catch (\Throwable $e) {
                Log::warning('Provider message sync failed', [
                    'user' => $user->id, 'direction' => $direction, 'error' => $e->getMessage(),
                ]);

                continue;
            }

            foreach ($rows as $row) {
                if (($row['msg_id'] ?? '') === '') {
                    continue; // no stable key to de-dupe on
                }

                ProviderMessage::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'direction' => $direction,
                        'provider_msg_id' => (string) $row['msg_id'],
                    ],
                    [
                        'sender' => $row['sender'] ?: null,
                        'receiver' => $row['receiver'] ?: null,
                        'body' => $row['body'],
                        'parts' => max(1, (int) $row['parts']),
                        'rec_count' => (int) $row['rec_count'],
                        'rec_success' => (int) $row['rec_success'],
                        'rec_failed' => (int) $row['rec_failed'],
                        'sent_at' => $this->parseDate($row['date'] ?? null),
                    ],
                );
            }
        }

        Cache::put("provider_msgs_synced_at:{$user->id}", now(), now()->addDay());
    }

    /** The provider's date strings vary by panel; fall back to null on anything unparseable. */
    private function parseDate(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
