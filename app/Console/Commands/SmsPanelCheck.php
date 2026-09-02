<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Sms\UserSmsGateway;
use Illuminate\Console\Command;

/**
 * Quick health check for a customer's Melipayamak panel credentials
 * (docs/starter.md §12). Run it right after setting sms_username / sms_password
 * to see the real reason a connection fails.
 *
 *   php artisan sms:check 09121234567
 *   php artisan sms:check 42            # by user id
 */
class SmsPanelCheck extends Command
{
    protected $signature = 'sms:check {user : User id or mobile number}';

    protected $description = 'اعتبار پنل ملی‌پیامک یک کاربر را بررسی می‌کند و خطای واقعی را نشان می‌دهد';

    public function handle(): int
    {
        $key = (string) $this->argument('user');

        $user = User::query()
            ->where('id', $key)
            ->orWhere('mobile', $key)
            ->first();

        if (! $user) {
            $this->error("کاربری با شناسه/موبایل «{$key}» پیدا نشد.");

            return self::FAILURE;
        }

        $this->line("کاربر: {$user->full_name} ({$user->mobile})");
        $this->line('نام کاربری پنل: '.($user->sms_username ?: '—'));
        $this->line('خط فرستنده: '.($user->sms_sender ?: '—'));

        if (! $user->hasSmsPanel()) {
            $this->error('اعتبار پنل ست نشده است (sms_username / sms_password).');

            return self::FAILURE;
        }

        try {
            $gateway = UserSmsGateway::for($user);
            $credit = $gateway->credit();
            $rial = $gateway->creditRial();

            $this->info('اتصال موفق.');
            $this->line('اعتبار (تعداد پیامک): '.number_format((int) $credit));
            $this->line('اعتبار ریالی: '.($rial === null ? 'در دسترس نیست' : number_format($rial).' ریال'));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('اتصال ناموفق: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
