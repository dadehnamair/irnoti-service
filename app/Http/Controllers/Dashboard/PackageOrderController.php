<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SubscriptionController;
use App\Models\PackageOrder;
use App\Models\Setting;
use App\Models\SmsPackage;
use App\Support\HandlesGatewayPayment;
use App\Support\PayableSettlement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;

/**
 * SMS credit bundles for logged-in customers (docs/starter.md §12). Mirrors
 * {@see SubscriptionController}: online gateway (gated by
 * the "package_payment_online" setting), wallet balance, or a bank receipt.
 * Settling adds the bundle's `sms_count` to users.sms_credit.
 */
class PackageOrderController extends Controller
{
    use HandlesGatewayPayment;

    public function index(Request $request): View
    {
        return view('dashboard.packages', [
            'user' => $request->user(),
            'packages' => SmsPackage::query()->active()->ordered()->get(),
            'walletBalance' => $request->user()->wallet()->balance,
        ]);
    }

    public function checkout(Request $request, SmsPackage $package): View
    {
        abort_unless($package->is_active, 404);

        return view('dashboard.package-checkout', [
            'package' => $package,
            'onlinePayment' => $package->price > 0 && $this->onlinePaymentEnabled(),
            'walletBalance' => $request->user()->wallet()->balance,
            'receiptEnabled' => $this->receiptEnabled(),
        ]);
    }

    public function order(Request $request, PayableSettlement $settlement): RedirectResponse
    {
        $data = $request->validate([
            'package' => ['required', Rule::exists('sms_packages', 'slug')->where('is_active', true)],
            'method' => ['required', 'in:online,wallet,receipt'],
        ], [], ['package' => 'بسته', 'method' => 'روش پرداخت']);

        $package = SmsPackage::where('slug', $data['package'])->firstOrFail();
        $user = $request->user();

        $order = PackageOrder::create([
            'user_id' => $user->id,
            'sms_package_id' => $package->id,
            'package_name' => $package->name,
            'sms_count' => $package->sms_count,
            'price' => $package->price,
            'status' => $package->price > 0 ? 'awaiting_payment' : 'completed',
            'method' => $data['method'],
        ]);

        if ((int) $package->price === 0) {
            $settlement->settle($order, ['method' => 'free']);

            return redirect()->route('package-orders.show', $order)
                ->with('auth_status', 'بسته رایگان فعال شد.');
        }

        return match ($data['method']) {
            'wallet' => $this->settleFromWallet($order, $settlement),
            'receipt' => redirect()->route('dashboard.receipts.create', ['for' => 'package', 'ref' => $order->token]),
            default => $this->onlinePaymentEnabled()
                ? redirect()->route('package-orders.pay', $order)
                : redirect()->route('package-orders.show', $order)
                    ->with('auth_status', 'درخواست ثبت شد. برای هماهنگی پرداخت با شما تماس می‌گیریم.'),
        };
    }

    public function show(Request $request, PackageOrder $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return view('dashboard.package-order', [
            'order' => $order,
            'canPayOnline' => $this->onlinePaymentEnabled() && $order->isPayable(),
            'walletBalance' => $request->user()->wallet()->balance,
            'receiptEnabled' => $this->receiptEnabled(),
        ]);
    }

    public function payFromWallet(Request $request, PackageOrder $order, PayableSettlement $settlement): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if (! $order->isPayable()) {
            return redirect()->route('package-orders.show', $order);
        }

        return $this->settleFromWallet($order, $settlement);
    }

    private function settleFromWallet(PackageOrder $order, PayableSettlement $settlement): RedirectResponse
    {
        $wallet = $order->user->wallet();

        if (! $wallet->hasSufficient((int) $order->price)) {
            return redirect()->route('package-orders.show', $order)
                ->with('payment_error', 'موجودی کیف پول کافی نیست. ابتدا حساب خود را شارژ کنید.');
        }

        $wallet->debit((int) $order->price, 'package_purchase', $order, 'خرید بسته پیامکی «'.$order->package_name.'»', "package:{$order->id}");
        $settlement->settle($order, ['method' => 'wallet']);

        return redirect()->route('package-orders.show', $order)->with('payment_success', true);
    }

    public function pay(Request $request, PackageOrder $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if (! $this->onlinePaymentEnabled() || ! $order->isPayable()) {
            return redirect()->route('package-orders.show', $order);
        }

        try {
            return $this->purchaseViaGateway(
                (int) $order->price,
                route('package-orders.callback'),
                fn ($transactionId) => $order->update([
                    'transaction_id' => $transactionId,
                    'payment_driver' => config('payment.default'),
                ]),
            );
        } catch (\Throwable $e) {
            Log::error('Package payment purchase failed', ['order' => $order->id, 'error' => $e->getMessage()]);

            return redirect()->route('package-orders.show', $order)
                ->with('payment_error', 'اتصال به درگاه پرداخت ممکن نشد. لطفاً بعداً تلاش کنید.');
        }
    }

    public function callback(Request $request, PayableSettlement $settlement): RedirectResponse
    {
        $transactionId = $this->gatewayTransactionId($request);

        $order = PackageOrder::query()
            ->when($transactionId, fn ($q) => $q->where('transaction_id', $transactionId))
            ->latest('id')
            ->first();

        if (! $order) {
            return redirect()->route('dashboard.packages')->with('payment_error', 'سفارش مربوط به این پرداخت پیدا نشد.');
        }

        if ($this->gatewayPaymentCancelled($request)) {
            return redirect()->route('package-orders.show', $order)
                ->with('payment_error', 'پرداخت لغو شد. می‌توانید دوباره تلاش کنید.');
        }

        try {
            $receipt = $this->verifyViaGateway((int) $order->price, $order->transaction_id);

            $settlement->settle($order, [
                'method' => 'online',
                'reference_id' => $receipt->getReferenceId(),
                'payment_driver' => $order->payment_driver ?? config('payment.default'),
            ]);

            return redirect()->route('package-orders.show', $order)->with('payment_success', true);
        } catch (InvalidPaymentException $e) {
            return redirect()->route('package-orders.show', $order)
                ->with('payment_error', $e->getMessage() ?: 'پرداخت ناموفق بود یا لغو شد.');
        } catch (\Throwable $e) {
            Log::error('Package payment verify failed', ['order' => $order->id, 'error' => $e->getMessage()]);

            return redirect()->route('package-orders.show', $order)
                ->with('payment_error', 'تأیید پرداخت با خطا مواجه شد. اگر مبلغ کسر شده با پشتیبانی تماس بگیرید.');
        }
    }

    private function onlinePaymentEnabled(): bool
    {
        return (bool) Setting::get('package_payment_online', false) && filled(config('payment.default'));
    }

    private function receiptEnabled(): bool
    {
        return (bool) Setting::get('receipt_payment_enabled', true)
            && (bool) Setting::get('receipt_for_packages', true);
    }
}
