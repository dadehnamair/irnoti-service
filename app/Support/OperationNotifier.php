<?php

namespace App\Support;

use App\Jobs\SendSmsJob;
use App\Models\BankReceipt;
use App\Models\Invoice;
use App\Models\LineOrder;
use App\Models\MarketplaceInstallation;
use App\Models\PackageOrder;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;

/**
 * Central place that turns an app event into SMS notifications for the customer
 * and/or the admin (docs/starter.md §44). Every operation that matters — sign-up,
 * profile completion, plan purchase, line order lifecycle — calls one method
 * here. All sends are queued ({@see SendSmsJob}) and gated by the
 * "sms_notifications_enabled" admin setting.
 */
class OperationNotifier
{
    /** Sign-up finished: welcome the user, tell the admin (docs/starter.md §26). */
    public function userRegistered(User $user): void
    {
        $this->toUser($user->mobile, sprintf(
            'به %s خوش آمدید. حساب شما با شماره %s فعال شد.',
            $this->brand(),
            $user->mobile,
        ));

        $this->toAdmin(sprintf('ثبت‌نام جدید در %s — موبایل: %s', $this->brand(), $user->mobile));
    }

    /** Identity profile completed (docs/starter.md §26 fields). */
    public function profileCompleted(User $user): void
    {
        $this->toAdmin(sprintf(
            'کاربر %s (%s) اطلاعات هویتی خود را تکمیل کرد.',
            $user->full_name,
            $user->mobile,
        ));
    }

    /** Profile complete + plan bought → account is waiting for the admin (docs/starter.md §39). */
    public function awaitingApproval(User $user): void
    {
        $this->toAdmin(sprintf(
            'حساب %s (%s) آمادهٔ بررسی و تأیید است.',
            $user->full_name,
            $user->mobile,
        ));
    }

    /** Admin approved the account — panel features are now open. */
    public function accountApproved(User $user): void
    {
        $this->toUser($user->mobile, sprintf(
            'حساب شما در %s تأیید شد. اکنون می‌توانید از امکانات پنل استفاده کنید.',
            $this->brand(),
        ));
    }

    /** Admin approved the uploaded identity documents. */
    public function documentsApproved(User $user): void
    {
        $this->toUser($user->mobile, sprintf('مدارک هویتی شما در %s تأیید شد.', $this->brand()));
    }

    /** Admin rejected the documents — the customer needs to re-upload. */
    public function documentsRejected(User $user, ?string $reason = null): void
    {
        $this->toUser($user->mobile, trim(sprintf(
            'مدارک هویتی شما در %s تأیید نشد. %s لطفاً دوباره بارگذاری کنید.',
            $this->brand(),
            $reason ? 'دلیل: '.$reason.'.' : '',
        )));
    }

    /** A plan became active — free (instant) or paid (after the gateway). */
    public function subscriptionActivated(Subscription $subscription): void
    {
        $user = $subscription->user;
        $price = (int) $subscription->price === 0
            ? 'رایگان'
            : number_format((int) $subscription->price).' تومان';

        if ($user?->mobile) {
            $this->toUser($user->mobile, sprintf(
                'پلن «%s» برای حساب شما در %s فعال شد. مبلغ: %s',
                $subscription->plan_name,
                $this->brand(),
                $price,
            ));
        }

        $this->toAdmin(sprintf(
            'خرید پلن در %s — %s → «%s» (%s)',
            $this->brand(),
            $user?->mobile ?? '—',
            $subscription->plan_name,
            $price,
        ));
    }

    /** A line purchase request was captured (docs/starter.md §11). */
    public function lineOrderCreated(LineOrder $order): void
    {
        $this->toUser($order->customer_phone, sprintf(
            'سفارش خط %s در %s ثبت شد. کد پیگیری: %s',
            $order->line_label,
            $this->brand(),
            $order->token,
        ));

        $this->toAdmin(sprintf('سفارش خط جدید: %s — %s', $order->line_label, $order->customer_phone));
    }

    /** A line order was paid online (docs/starter.md §11 gateway callback). */
    public function lineOrderPaid(LineOrder $order): void
    {
        $this->toUser($order->customer_phone, sprintf(
            'پرداخت سفارش خط %s با موفقیت انجام شد. کد پیگیری: %s',
            $order->line_label,
            $order->token,
        ));

        $this->toAdmin(sprintf('پرداخت خط: %s — %s', $order->line_label, $order->customer_phone));
    }

    /** Admin moved a line order along the status workflow (docs/starter.md §11). */
    public function lineOrderStatusChanged(LineOrder $order): void
    {
        $this->toUser($order->customer_phone, sprintf(
            'وضعیت سفارش خط شما (%s) به «%s» تغییر کرد. کد پیگیری: %s',
            $order->line_label,
            $order->status_label,
            $order->token,
        ));
    }

    /** Wallet was topped up — online, or an approved receipt (docs/starter.md §23). */
    public function walletToppedUp(User $user, int $amount): void
    {
        $this->toUser($user->mobile, sprintf(
            'کیف پول شما در %s به مبلغ %s تومان شارژ شد.',
            $this->brand(),
            number_format($amount),
        ));

        $this->toAdmin(sprintf('شارژ کیف پول — %s: %s تومان', $user->mobile, number_format($amount)));
    }

    /** Customer submitted a bank receipt for review (docs/starter.md §22). */
    public function bankReceiptSubmitted(BankReceipt $receipt): void
    {
        $this->toAdmin(sprintf(
            'فیش بانکی جدید در %s — %s: %s تومان، پیگیری %s. نیازمند بررسی.',
            $this->brand(),
            $receipt->user?->mobile ?? '—',
            number_format((int) $receipt->amount),
            $receipt->tracking_code,
        ));
    }

    public function bankReceiptApproved(BankReceipt $receipt): void
    {
        $this->toUser($receipt->user?->mobile, sprintf(
            'فیش بانکی شما در %s تأیید شد. مبلغ: %s تومان.',
            $this->brand(),
            number_format((int) $receipt->amount),
        ));
    }

    public function bankReceiptRejected(BankReceipt $receipt): void
    {
        $this->toUser($receipt->user?->mobile, trim(sprintf(
            'فیش بانکی شما در %s تأیید نشد. %s',
            $this->brand(),
            $receipt->admin_note ? 'توضیح: '.$receipt->admin_note : 'لطفاً با پشتیبانی تماس بگیرید.',
        )));
    }

    /** An SMS package purchase settled — credit added (docs/starter.md §12). */
    public function packageActivated(PackageOrder $order): void
    {
        $this->toUser($order->user?->mobile, sprintf(
            'بسته «%s» (%s پیامک) برای حساب شما در %s فعال شد.',
            $order->package_name,
            number_format((int) $order->sms_count),
            $this->brand(),
        ));

        $this->toAdmin(sprintf(
            'خرید بسته پیامکی در %s — %s → «%s» (%s تومان)',
            $this->brand(),
            $order->user?->mobile ?? '—',
            $order->package_name,
            number_format((int) $order->price),
        ));
    }

    /** A «بازارچه» add-on became active — free (instant) or paid (docs/starter.md §15). */
    public function marketplaceAppActivated(MarketplaceInstallation $installation): void
    {
        $user = $installation->user;
        $name = $installation->app?->name ?? 'افزونه';
        $price = (int) $installation->price === 0
            ? 'رایگان'
            : number_format((int) $installation->price).' تومان';

        if ($user?->mobile) {
            $this->toUser($user->mobile, sprintf(
                'افزونه «%s» برای حساب شما در %s فعال شد. مبلغ: %s',
                $name,
                $this->brand(),
                $price,
            ));
        }

        $this->toAdmin(sprintf(
            'خرید افزونه در %s — %s → «%s» (%s)',
            $this->brand(),
            $user?->mobile ?? '—',
            $name,
            $price,
        ));
    }

    /** Admin issued an invoice (docs/starter.md §22). */
    public function invoiceIssued(Invoice $invoice): void
    {
        $this->toUser($invoice->user?->mobile, sprintf(
            'صورت‌حساب %s به مبلغ %s تومان برای شما در %s صادر شد. برای پرداخت به پنل کاربری مراجعه کنید.',
            $invoice->number,
            number_format((int) $invoice->total),
            $this->brand(),
        ));
    }

    public function invoicePaid(Invoice $invoice): void
    {
        $this->toUser($invoice->user?->mobile, sprintf(
            'پرداخت صورت‌حساب %s در %s با موفقیت ثبت شد.',
            $invoice->number,
            $this->brand(),
        ));

        $this->toAdmin(sprintf(
            'پرداخت صورت‌حساب %s — %s: %s تومان',
            $invoice->number,
            $invoice->user?->mobile ?? '—',
            number_format((int) $invoice->total),
        ));
    }

    private function toUser(?string $mobile, string $message): void
    {
        if ($this->enabled() && filled($mobile)) {
            dispatch(SendSmsJob::text($mobile, $message));
        }
    }

    private function toAdmin(string $message): void
    {
        $admin = Setting::get('admin_mobile') ?: config('sms.admin_mobile');

        if ($this->enabled() && filled($admin)) {
            dispatch(SendSmsJob::text($admin, $message));
        }
    }

    private function enabled(): bool
    {
        return (bool) Setting::get('sms_notifications_enabled', true);
    }

    private function brand(): string
    {
        return (string) config('theme.brand', 'irnoti');
    }
}
