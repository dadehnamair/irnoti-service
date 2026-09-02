<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\Sms\SmsManager;
use App\Services\Sms\SmsPanelNotConfiguredException;
use App\Services\Sms\UserSmsGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Poll the SMS provider for the carrier delivery receipt of messages sent from
 * the customer panel (docs/starter.md §12/§14). Uses GetDelivery2 through each
 * customer's own panel credentials. A message whose receipt is already final
 * ({@see SmsMessage::DELIVERY_FINAL}) is skipped, so the job is cheap to run
 * often — it is scheduled every ten minutes in routes/console.php.
 *
 *   php artisan sms:delivery-sync
 *   php artisan sms:delivery-sync --user=09121234567 --limit=50
 */
class SmsDeliverySync extends Command
{
    protected $signature = 'sms:delivery-sync
        {--limit=300 : حداکثر تعداد پیام بررسی‌شده در هر اجرا}
        {--max-age=5 : نادیده‌گرفتن پیام‌های قدیمی‌تر از این تعداد روز}
        {--user= : محدود کردن به یک کاربر (شناسه یا موبایل)}';

    protected $description = 'وضعیت تحویل پیامک‌های ارسالی را از سامانه پیامک می‌گیرد و پیام‌های نهایی‌شده را دیگر بررسی نمی‌کند';

    public function handle(): int
    {
        if (! (bool) Setting::get('sms_delivery_sync_enabled', true)) {
            $this->info('پیگیری وضعیت تحویل غیرفعال است (sms_delivery_sync_enabled).');

            return self::SUCCESS;
        }

        $maxAge = max(1, (int) $this->option('max-age'));
        $limit = max(1, (int) $this->option('limit'));

        $query = SmsMessage::query()
            ->awaitingDelivery()
            ->where('created_at', '>=', now()->subDays($maxAge))
            ->orderByRaw('delivery_checked_at is null desc')
            ->orderBy('delivery_checked_at')
            ->orderBy('id')
            ->with('user')
            ->limit($limit);

        if ($userKey = $this->option('user')) {
            $user = User::query()->where('id', $userKey)->orWhere('mobile', $userKey)->first();

            if (! $user) {
                $this->error("کاربری با شناسه/موبایل «{$userKey}» پیدا نشد.");

                return self::FAILURE;
            }

            $query->where('user_id', $user->id);
        }

        $messages = $query->get();

        $tally = ['checked' => 0, 'delivered' => 0, 'undelivered' => 0, 'failed' => 0, 'pending' => 0, 'skipped' => 0];

        foreach ($messages->groupBy('user_id') as $group) {
            /** @var User|null $user */
            $user = $group->first()->user;

            if (! $user || ! $user->hasSmsPanel()) {
                $tally['skipped'] += $group->count();

                continue;
            }

            try {
                $gateway = UserSmsGateway::for($user);
            } catch (SmsPanelNotConfiguredException) {
                $tally['skipped'] += $group->count();

                continue;
            }

            foreach ($group as $message) {
                $this->poll($gateway, $message, $tally);
            }
        }

        // Aged-out messages that never got a receipt: settle them as "unknown"
        // so the panel shows something and they leave the poll queue for good.
        $stale = SmsMessage::query()
            ->awaitingDelivery()
            ->whereNull('delivery_status')
            ->where('created_at', '<', now()->subDays($maxAge))
            ->update(['delivery_status' => 'unknown', 'delivery_checked_at' => now()]);

        $this->info(sprintf(
            'بررسی‌شده: %d | تحویل‌شده: %d | تحویل‌نشده: %d | خطای مخابراتی: %d | هنوز در انتظار: %d | رد‌شده: %d | منقضی: %d',
            $tally['checked'], $tally['delivered'], $tally['undelivered'],
            $tally['failed'], $tally['pending'], $tally['skipped'], $stale,
        ));

        return self::SUCCESS;
    }

    /** @param  array<string, int>  $tally */
    private function poll(SmsManager $gateway, SmsMessage $message, array &$tally): void
    {
        try {
            $raw = $gateway->deliveryStatus((string) $message->rec_id);
        } catch (\Throwable $e) {
            Log::warning('[sms:delivery-sync] read failed', [
                'message' => $message->id,
                'rec_id' => $message->rec_id,
                'error' => $e->getMessage(),
            ]);

            return; // leave it in the queue, retry next run
        }

        $slug = SmsMessage::mapDeliveryStatus($raw);
        $tally['checked']++;

        $message->forceFill([
            'delivery_status' => $slug,
            'delivery_code' => ($raw === null || $raw === '') ? null : mb_substr((string) $raw, 0, 8),
            'delivery_checked_at' => now(),
        ])->save();

        if ($slug !== null && array_key_exists($slug, $tally)) {
            $tally[$slug]++;
        }
    }
}
