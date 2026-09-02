<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Setting;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use App\Support\HandlesGatewayPayment;
use App\Support\PayableSettlement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;

/**
 * Customer wallet (docs/starter.md §22 / §23): balance, "شارژ حساب" to an
 * arbitrary amount, and the full immutable financial history. Top-up is settled
 * once through {@see PayableSettlement} — online now, or a bank receipt later.
 */
class WalletController extends Controller
{
    use HandlesGatewayPayment;

    public function show(Request $request): View
    {
        $user = $request->user();
        $wallet = $user->wallet();

        return view('dashboard.wallet', [
            'user' => $user,
            'wallet' => $wallet,
            'minTopup' => $this->minTopup(),
            'onlinePayment' => $this->onlinePaymentEnabled(),
            'receiptEnabled' => $this->receiptEnabled(),
            'bankAccounts' => BankAccount::query()->active()->ordered()->get(),
            'transactions' => $wallet->transactions()->limit(10)->get(),
            'pendingTopups' => $user->walletTopups()->where('status', 'awaiting_payment')->latest()->get(),
        ]);
    }

    public function transactions(Request $request): View
    {
        $user = $request->user();

        $query = $user->walletTransactions();

        if (($type = $request->query('type')) && array_key_exists($type, WalletTransaction::TYPES)) {
            $query->where('type', $type);
        }

        return view('dashboard.transactions', [
            'user' => $user,
            'transactions' => $query->paginate(20)->withQueryString(),
            'types' => WalletTransaction::TYPES,
            'activeType' => $type,
        ]);
    }

    public function topup(Request $request): RedirectResponse
    {
        $user = $request->user();
        $min = $this->minTopup();

        $data = $request->validate([
            'amount' => ['required', 'integer', "min:$min", 'max:500000000'],
            'method' => ['required', 'in:online,receipt'],
        ], [
            'amount.min' => "حداقل مبلغ شارژ {$this->minTopupLabel()} تومان است.",
        ], ['amount' => 'مبلغ', 'method' => 'روش پرداخت']);

        $topup = WalletTopup::create([
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'status' => 'awaiting_payment',
            'method' => $data['method'],
        ]);

        if ($data['method'] === 'online') {
            if (! $this->onlinePaymentEnabled()) {
                return back()->with('payment_error', 'پرداخت آنلاین در حال حاضر فعال نیست.');
            }

            return redirect()->route('wallet.topup.pay', $topup);
        }

        // Bank receipt: the customer submits the transfer details next.
        return redirect()->route('dashboard.receipts.create', ['for' => 'topup', 'ref' => $topup->token]);
    }

    public function pay(Request $request, WalletTopup $topup)
    {
        abort_unless($topup->user_id === $request->user()->id, 403);

        if (! $this->onlinePaymentEnabled() || ! $topup->isPayable()) {
            return redirect()->route('dashboard.wallet');
        }

        try {
            return $this->purchaseViaGateway(
                (int) $topup->amount,
                route('wallet.topup.callback'),
                fn ($transactionId) => $topup->update([
                    'transaction_id' => $transactionId,
                    'payment_driver' => config('payment.default'),
                ]),
            );
        } catch (\Throwable $e) {
            Log::error('Wallet topup purchase failed', ['topup' => $topup->id, 'error' => $e->getMessage()]);

            return redirect()->route('dashboard.wallet')
                ->with('payment_error', 'اتصال به درگاه پرداخت ممکن نشد. لطفاً بعداً دوباره تلاش کنید.');
        }
    }

    public function callback(Request $request, PayableSettlement $settlement): RedirectResponse
    {
        $transactionId = $this->gatewayTransactionId($request);

        $topup = WalletTopup::query()
            ->when($transactionId, fn ($q) => $q->where('transaction_id', $transactionId))
            ->latest('id')
            ->first();

        if (! $topup) {
            return redirect()->route('dashboard.wallet')->with('payment_error', 'تراکنش شارژ پیدا نشد.');
        }

        if ($this->gatewayPaymentCancelled($request)) {
            return redirect()->route('dashboard.wallet')
                ->with('payment_error', 'پرداخت لغو شد. می‌توانید دوباره تلاش کنید.');
        }

        try {
            $receipt = $this->verifyViaGateway((int) $topup->amount, $topup->transaction_id);

            $settlement->settle($topup, [
                'method' => 'online',
                'reference_id' => $receipt->getReferenceId(),
                'payment_driver' => $topup->payment_driver ?? config('payment.default'),
            ]);

            return redirect()->route('dashboard.wallet')->with('payment_success', true);
        } catch (InvalidPaymentException $e) {
            return redirect()->route('dashboard.wallet')
                ->with('payment_error', $e->getMessage() ?: 'پرداخت ناموفق بود یا لغو شد.');
        } catch (\Throwable $e) {
            Log::error('Wallet topup verify failed', ['topup' => $topup->id, 'error' => $e->getMessage()]);

            return redirect()->route('dashboard.wallet')
                ->with('payment_error', 'تأیید پرداخت با خطا مواجه شد. اگر مبلغ کسر شده با پشتیبانی تماس بگیرید.');
        }
    }

    private function onlinePaymentEnabled(): bool
    {
        return (bool) Setting::get('wallet_enabled', true) && filled(config('payment.default'));
    }

    private function receiptEnabled(): bool
    {
        return (bool) Setting::get('receipt_payment_enabled', true)
            && (bool) Setting::get('receipt_for_topup', true);
    }

    private function minTopup(): int
    {
        return max(1000, (int) Setting::get('wallet_min_topup', 10000));
    }

    private function minTopupLabel(): string
    {
        return number_format($this->minTopup());
    }
}
