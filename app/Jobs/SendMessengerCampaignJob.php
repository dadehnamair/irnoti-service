<?php

namespace App\Jobs;

use App\Models\MessengerCampaign;
use App\Models\MessengerRecipient;
use App\Services\Messenger\MessengerChannelNotConfiguredException;
use App\Services\Messenger\MessengerManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued bulk send to a messenger network (docs/starter.md §91). The web request
 * only records the {@see MessengerCampaign} + one {@see MessengerRecipient}
 * per recipient as "queued" and debits the wallet; this job hands the batch to
 * the channel driver, writes each recipient's outcome, moves the campaign to
 * sent/partial/failed and refunds the wallet for whatever did not go out.
 * Mirrors {@see SendContactGroupSmsJob}.
 */
class SendMessengerCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $campaignId) {}

    public function handle(MessengerManager $messenger): void
    {
        $campaign = MessengerCampaign::with(['recipients', 'user'])->find($this->campaignId);

        if (! $campaign || in_array($campaign->status, MessengerCampaign::FINAL_STATUSES, true)) {
            return;
        }

        if (! $campaign->user) {
            $campaign->recipients()->where('status', '!=', 'sent')->update(['status' => 'failed', 'error' => 'کاربر یافت نشد.']);
            $this->finalize($campaign, $messenger, error: 'کاربر یافت نشد.');

            return;
        }

        $campaign->forceFill(['status' => 'sending'])->save();

        $pending = $campaign->recipients->where('status', '!=', 'sent');

        if ($pending->isEmpty()) {
            $this->finalize($campaign, $messenger);

            return;
        }

        try {
            $channel = $messenger->channel($campaign->channel);
        } catch (MessengerChannelNotConfiguredException $e) {
            // Config problem, not a transient one — terminal, don't burn retries.
            $pending->each->update(['status' => 'failed', 'error' => $e->getMessage()]);
            $this->finalize($campaign, $messenger, error: $e->getMessage());

            return;
        }

        $result = $channel->sendBulk(
            $pending->pluck('to')->all(),
            $campaign->body,
            ['schedule_at' => $campaign->scheduled_at?->format('Y-m-d H:i:s')],
        );

        $byRecipient = collect($result->results)->keyBy('to');

        foreach ($pending as $recipient) {
            $line = $byRecipient->get($recipient->to);

            $recipient->update($line
                ? ['status' => $line['ok'] ? 'sent' : 'failed', 'provider_ref' => $line['ref'], 'error' => $line['error']]
                : ['status' => 'failed', 'error' => 'پاسخی از کانال دریافت نشد.']);
        }

        if ($result->batchId) {
            $campaign->forceFill(['batch_id' => $result->batchId])->save();
        }

        $this->finalize($campaign, $messenger);
    }

    public function failed(Throwable $e): void
    {
        $campaign = MessengerCampaign::with('recipients')->find($this->campaignId);

        if (! $campaign || in_array($campaign->status, MessengerCampaign::FINAL_STATUSES, true)) {
            return;
        }

        $campaign->recipients()->where('status', '!=', 'sent')->update([
            'status' => 'failed',
            'error' => mb_substr($e->getMessage(), 0, 250),
        ]);

        $this->finalize($campaign, app(MessengerManager::class), error: mb_substr($e->getMessage(), 0, 250));

        Log::error('[messenger] campaign send failed', ['campaign' => $this->campaignId, 'error' => $e->getMessage()]);
    }

    /**
     * Recompute counts from the recipient rows, settle the campaign status, and
     * refund the wallet for every recipient that did not go out. The refund is
     * idempotent (one ledger row per campaign) so it is safe on retry / failure.
     */
    private function finalize(MessengerCampaign $campaign, MessengerManager $messenger, ?string $error = null): void
    {
        $campaign->loadMissing('recipients');

        $success = $campaign->recipients->where('status', 'sent')->count();
        $failed = $campaign->recipients->count() - $success;

        $status = match (true) {
            $failed === 0 => 'sent',
            $success === 0 => 'failed',
            default => 'partial',
        };

        $campaign->forceFill([
            'status' => $status,
            'success_count' => $success,
            'failed_count' => $failed,
            'sent_at' => $campaign->sent_at ?? now(),
            'error' => $error ?? $campaign->error,
        ])->save();

        // One refund per campaign (single idempotency key). Only settle it once,
        // once the recipient rows have stopped moving.
        $refundDue = min(
            $failed * $messenger->tariffFor($campaign->channel),
            (int) $campaign->cost,
        );

        if ($refundDue > 0 && (int) $campaign->refunded === 0 && $campaign->user) {
            $campaign->user->wallet()->credit(
                $refundDue,
                'messenger_refund',
                $campaign,
                "برگشت هزینهٔ ارسال ناموفق — {$campaign->channel_label}",
                "messenger:{$campaign->id}:refund",
            );

            $campaign->forceFill(['refunded' => (int) $campaign->refunded + $refundDue])->save();
        }
    }
}
