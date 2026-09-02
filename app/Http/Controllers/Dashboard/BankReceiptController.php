<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankReceipt;
use App\Models\Invoice;
use App\Models\LineOrder;
use App\Models\PackageOrder;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\WalletTopup;
use App\Support\OperationNotifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\CalendarUtils;

/**
 * Bank-receipt ("فیش بانکی") submission as a payment method beside the online
 * gateway (docs/starter.md §22). Which purchases accept a receipt is controlled
 * per-purpose from the admin settings (receipt_for_*). Approval happens in the
 * Filament admin panel via {@see App\Support\BankReceiptService}.
 */
class BankReceiptController extends Controller
{
    /** `for` key → [settingKey, model, ownership resolver]. */
    private const PURPOSES = [
        'topup' => ['receipt_for_topup', WalletTopup::class],
        'plan' => ['receipt_for_plans', Subscription::class],
        'line' => ['receipt_for_lines', LineOrder::class],
        'package' => ['receipt_for_packages', PackageOrder::class],
        'invoice' => ['receipt_for_invoices', Invoice::class],
    ];

    public function index(Request $request): View
    {
        return view('dashboard.receipts', [
            'receipts' => $request->user()->bankReceipts()->with('bankAccount')->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        $for = (string) $request->query('for', 'topup');
        abort_unless(isset(self::PURPOSES[$for]), 404);

        [$settingKey, $model] = self::PURPOSES[$for];
        abort_unless($this->receiptAllowed($settingKey), 403, 'ثبت فیش برای این مورد فعال نیست.');

        $payable = null;
        $amount = (int) $request->query('amount', 0);

        if ($ref = $request->query('ref')) {
            $payable = $model::where('token', $ref)->firstOrFail();
            abort_unless($payable->user_id === $request->user()->id, 403);
            $amount = (int) ($payable->amount ?? $payable->price ?? $payable->total ?? 0);
        }

        return view('dashboard.receipt-form', [
            'for' => $for,
            'ref' => $ref,
            'payable' => $payable,
            'amount' => $amount,
            'transferTypes' => BankReceipt::TRANSFER_TYPES,
            'bankAccounts' => BankAccount::query()->active()->ordered()->get(),
        ]);
    }

    public function store(Request $request, OperationNotifier $notifier): RedirectResponse
    {
        $for = (string) $request->input('for', 'topup');
        abort_unless(isset(self::PURPOSES[$for]), 404);

        [$settingKey, $model] = self::PURPOSES[$for];
        abort_unless($this->receiptAllowed($settingKey), 403);

        $data = $request->validate([
            'ref' => ['nullable', 'string'],
            'amount' => ['required', 'integer', 'min:1000'],
            'tracking_code' => ['required', 'string', 'max:60'],
            'transfer_type' => ['required', Rule::in(array_keys(BankReceipt::TRANSFER_TYPES))],
            'paid_at' => ['required', 'string'],
            'bank_account_id' => ['nullable', Rule::exists('bank_accounts', 'id')],
            'image' => ['required', 'image', 'max:4096'],
        ], [], [
            'amount' => 'مبلغ',
            'tracking_code' => 'شماره پیگیری',
            'transfer_type' => 'نوع انتقال',
            'paid_at' => 'تاریخ واریز',
            'image' => 'تصویر فیش',
        ]);

        $payable = null;
        if (! empty($data['ref'])) {
            $payable = $model::where('token', $data['ref'])->firstOrFail();
            abort_unless($payable->user_id === $request->user()->id, 403);
        }

        $receipt = new BankReceipt([
            'amount' => $data['amount'],
            'tracking_code' => $data['tracking_code'],
            'transfer_type' => $data['transfer_type'],
            'paid_at' => $this->parseJalali($data['paid_at']),
            'bank_account_id' => $data['bank_account_id'] ?? null,
            'status' => 'pending',
        ]);
        $receipt->user()->associate($request->user());

        if ($payable) {
            $receipt->receiptable()->associate($payable);
        }

        $receipt->image_path = $request->file('image')->store("receipts/{$request->user()->id}", 'local');
        $receipt->save();

        $notifier->bankReceiptSubmitted($receipt);

        return redirect()->route('dashboard.receipts')
            ->with('auth_status', 'فیش بانکی ثبت شد و پس از بررسی کارشناسان اعمال می‌شود.');
    }

    private function receiptAllowed(string $settingKey): bool
    {
        return (bool) Setting::get('receipt_payment_enabled', true)
            && (bool) Setting::get($settingKey, true);
    }

    /** "1403/07/12" (Jalali, digits fa or en) → Carbon (Gregorian). */
    private function parseJalali(string $value): Carbon
    {
        $normalized = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '-' => '/', '.' => '/',
        ]);

        try {
            $carbon = CalendarUtils::createCarbonFromFormat('Y/m/d', $normalized);

            return Carbon::instance($carbon)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'paid_at' => 'تاریخ واریز معتبر نیست. نمونه درست: 1403/07/12',
            ]);
        }
    }
}
