<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Marketplace\Contracts\SyncsContacts;
use App\Models\MarketplaceApp;
use App\Models\MarketplaceInstallation;
use App\Models\Setting;
use App\Support\HandlesGatewayPayment;
use App\Support\PayableSettlement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;

/**
 * «بازارچه افزونه‌ها» for logged-in customers (docs/starter.md §15). Browse the
 * catalogue, install/connect an add-on, pay for it (mirrors
 * {@see PackageOrderController}: online gateway / wallet / bank receipt), then
 * manage it from its own page. Behaviour per add-on lives in its handler class.
 */
class MarketplaceController extends Controller
{
    use HandlesGatewayPayment;

    public function index(Request $request): View
    {
        $this->ensureEnabled();

        $apps = MarketplaceApp::query()->active()->ordered()->get();
        $installed = $request->user()->marketplaceInstallations()
            ->whereIn('status', ['pending', 'awaiting_payment', 'active'])
            ->get()
            ->keyBy('marketplace_app_id');

        return view('dashboard.marketplace.index', [
            'groups' => $apps->groupBy('category'),
            'categories' => MarketplaceApp::CATEGORIES,
            'installed' => $installed,
        ]);
    }

    public function show(Request $request, MarketplaceApp $app): View
    {
        $this->ensureEnabled();
        abort_unless($app->is_active, 404);

        return view('dashboard.marketplace.show', [
            'app' => $app,
            'installation' => $request->user()->marketplaceInstallations()
                ->where('marketplace_app_id', $app->id)->first(),
            'onlinePayment' => ! $app->isFree() && $this->onlinePaymentEnabled(),
            'walletBalance' => $request->user()->wallet()->balance,
            'receiptEnabled' => $this->receiptEnabled(),
        ]);
    }

    public function install(Request $request, MarketplaceApp $app, PayableSettlement $settlement): RedirectResponse
    {
        $this->ensureEnabled();
        abort_unless($app->is_active, 404);

        $user = $request->user();

        if ($existing = $user->marketplaceInstallations()->where('marketplace_app_id', $app->id)->first()) {
            return $existing->isPayable()
                ? redirect()->route('marketplace.manage', $existing)
                : redirect()->route('marketplace.manage', $existing)
                    ->with('auth_status', 'این افزونه قبلاً برای حساب شما نصب شده است.');
        }

        $handler = app(\App\Marketplace\AppRegistry::class)->for($app);

        try {
            $config = $handler->validateConfig($request->input('config', []));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $method = (string) $request->input('method', 'online');

        $installation = $user->marketplaceInstallations()->create([
            'marketplace_app_id' => $app->id,
            'status' => $app->isFree() ? 'active' : 'awaiting_payment',
            'config' => $config,
            'settings' => [],
            'price' => $app->isFree() ? 0 : (int) $app->price,
            'billing_type' => $app->billing_type,
            'billing_period' => $app->billing_period,
        ]);

        if ($app->isFree()) {
            $settlement->settle($installation, ['method' => 'free']);

            return redirect()->route('marketplace.manage', $installation)
                ->with('auth_status', 'افزونه رایگان فعال شد.');
        }

        return match ($method) {
            'wallet' => $this->settleFromWallet($installation, $settlement),
            'receipt' => redirect()->route('dashboard.receipts.create', ['for' => 'marketplace', 'ref' => $installation->token]),
            default => $this->onlinePaymentEnabled()
                ? redirect()->route('marketplace.pay', $installation)
                : redirect()->route('marketplace.manage', $installation)
                    ->with('auth_status', 'درخواست ثبت شد. برای هماهنگی پرداخت با شما تماس می‌گیریم.'),
        };
    }

    public function manage(Request $request, MarketplaceInstallation $installation): View
    {
        $this->ensureEnabled();
        $this->authorizeOwner($request, $installation);

        $installation->load('app');
        $handler = $installation->handler();

        return view('dashboard.marketplace.manage', [
            'installation' => $installation,
            'app' => $installation->app,
            'handlerView' => $installation->isActive() ? $handler->panelView($installation) : null,
            'canPayOnline' => $this->onlinePaymentEnabled() && $installation->isPayable(),
            'walletBalance' => $request->user()->wallet()->balance,
            'receiptEnabled' => $this->receiptEnabled(),
            'syncable' => $handler instanceof SyncsContacts,
        ]);
    }

    public function sync(Request $request, MarketplaceInstallation $installation): RedirectResponse
    {
        $this->ensureEnabled();
        $this->authorizeOwner($request, $installation);

        $handler = $installation->handler();

        if (! $installation->isActive() || ! $handler instanceof SyncsContacts) {
            return redirect()->route('marketplace.manage', $installation);
        }

        try {
            $result = $handler->pull($installation);
        } catch (\Throwable $e) {
            Log::error('Marketplace sync failed', ['installation' => $installation->id, 'error' => $e->getMessage()]);

            return redirect()->route('marketplace.manage', $installation)
                ->with('sms_error', 'دریافت اطلاعات از سرویس ناموفق بود: '.$e->getMessage());
        }

        return redirect()->route('marketplace.manage', $installation)
            ->with('sms_status', 'همگام‌سازی انجام شد — '.$result->summary());
    }

    public function updateConfig(Request $request, MarketplaceInstallation $installation): RedirectResponse
    {
        $this->ensureEnabled();
        $this->authorizeOwner($request, $installation);

        try {
            $config = $installation->handler()->validateConfig($request->input('config', []));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $installation->forceFill(['config' => $config])->save();

        return redirect()->route('marketplace.manage', $installation)
            ->with('auth_status', 'اطلاعات اتصال به‌روزرسانی شد.');
    }

    public function uninstall(Request $request, MarketplaceInstallation $installation): RedirectResponse
    {
        $this->ensureEnabled();
        $this->authorizeOwner($request, $installation);

        $installation->handler()->onDeactivate($installation);
        $installation->forceFill(['status' => 'cancelled'])->save();

        return redirect()->route('dashboard.marketplace')
            ->with('auth_status', 'افزونه حذف شد.');
    }

    public function payFromWallet(Request $request, MarketplaceInstallation $installation, PayableSettlement $settlement): RedirectResponse
    {
        $this->ensureEnabled();
        $this->authorizeOwner($request, $installation);

        if (! $installation->isPayable()) {
            return redirect()->route('marketplace.manage', $installation);
        }

        return $this->settleFromWallet($installation, $settlement);
    }

    public function pay(Request $request, MarketplaceInstallation $installation)
    {
        $this->ensureEnabled();
        $this->authorizeOwner($request, $installation);

        if (! $this->onlinePaymentEnabled() || ! $installation->isPayable()) {
            return redirect()->route('marketplace.manage', $installation);
        }

        try {
            return $this->purchaseViaGateway(
                (int) $installation->price,
                route('marketplace.payment.callback'),
                fn ($transactionId) => $installation->forceFill([
                    'transaction_id' => $transactionId,
                    'payment_driver' => config('payment.default'),
                ])->save(),
            );
        } catch (\Throwable $e) {
            Log::error('Marketplace payment purchase failed', ['installation' => $installation->id, 'error' => $e->getMessage()]);

            return redirect()->route('marketplace.manage', $installation)
                ->with('payment_error', 'اتصال به درگاه پرداخت ممکن نشد. لطفاً بعداً تلاش کنید.');
        }
    }

    public function callback(Request $request, PayableSettlement $settlement): RedirectResponse
    {
        $transactionId = $this->gatewayTransactionId($request);

        $installation = MarketplaceInstallation::query()
            ->when($transactionId, fn ($q) => $q->where('transaction_id', $transactionId))
            ->latest('id')
            ->first();

        if (! $installation) {
            return redirect()->route('dashboard.marketplace')->with('payment_error', 'سفارش مربوط به این پرداخت پیدا نشد.');
        }

        if ($this->gatewayPaymentCancelled($request)) {
            return redirect()->route('marketplace.manage', $installation)
                ->with('payment_error', 'پرداخت لغو شد. می‌توانید دوباره تلاش کنید.');
        }

        try {
            $receipt = $this->verifyViaGateway((int) $installation->price, $installation->transaction_id);

            $settlement->settle($installation, [
                'method' => 'online',
                'reference_id' => $receipt->getReferenceId(),
                'payment_driver' => $installation->payment_driver ?? config('payment.default'),
            ]);

            return redirect()->route('marketplace.manage', $installation)->with('payment_success', true);
        } catch (InvalidPaymentException $e) {
            return redirect()->route('marketplace.manage', $installation)
                ->with('payment_error', $e->getMessage() ?: 'پرداخت ناموفق بود یا لغو شد.');
        } catch (\Throwable $e) {
            Log::error('Marketplace payment verify failed', ['installation' => $installation->id, 'error' => $e->getMessage()]);

            return redirect()->route('marketplace.manage', $installation)
                ->with('payment_error', 'تأیید پرداخت با خطا مواجه شد. اگر مبلغ کسر شده با پشتیبانی تماس بگیرید.');
        }
    }

    private function settleFromWallet(MarketplaceInstallation $installation, PayableSettlement $settlement): RedirectResponse
    {
        $wallet = $installation->user->wallet();

        if (! $wallet->hasSufficient((int) $installation->price)) {
            return redirect()->route('marketplace.manage', $installation)
                ->with('payment_error', 'موجودی کیف پول کافی نیست. ابتدا حساب خود را شارژ کنید.');
        }

        $wallet->debit(
            (int) $installation->price,
            'marketplace_purchase',
            $installation,
            'خرید افزونه «'.($installation->app?->name ?? '').'»',
            "marketplace:{$installation->id}",
        );

        $settlement->settle($installation, ['method' => 'wallet']);

        return redirect()->route('marketplace.manage', $installation)->with('payment_success', true);
    }

    private function authorizeOwner(Request $request, MarketplaceInstallation $installation): void
    {
        abort_unless($installation->user_id === $request->user()->id, 403);
    }

    private function ensureEnabled(): void
    {
        abort_unless((bool) Setting::get('marketplace_enabled', true), 404);
    }

    private function onlinePaymentEnabled(): bool
    {
        return (bool) Setting::get('marketplace_payment_online', false) && filled(config('payment.default'));
    }

    private function receiptEnabled(): bool
    {
        return (bool) Setting::get('receipt_payment_enabled', true)
            && (bool) Setting::get('receipt_for_marketplace', true);
    }
}
