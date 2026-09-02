<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LineController;
use App\Models\LineOrder;
use App\Models\Setting;
use App\Models\SmsLine;
use App\Support\HandlesGatewayPayment;
use App\Support\OperationNotifier;
use App\Support\PayableSettlement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Buying a dedicated line from inside the customer panel (docs/starter.md §11).
 * Same flow as the public {@see LineController}, but the order is linked to the
 * account, the contact form is pre-filled from the profile, and pages live
 * behind auth + the "approved" gate.
 */
class LineOrderController extends Controller
{
    use HandlesGatewayPayment;

    public function index(): View
    {
        $lines = SmsLine::query()->active()->ordered()->get();

        return view('dashboard.lines.index', [
            'groups' => LineController::groupLines($lines),
            'digitOptions' => $lines->pluck('digits')->unique()->sort()->values(),
            'typeOptions' => $lines->pluck('line_type')->unique()->values(),
            'myOrders' => LineOrder::query()
                ->where('user_id', request()->user()->id)
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    public function checkout(Request $request, SmsLine $line): View
    {
        abort_unless($line->is_active, 404);

        return view('dashboard.lines.checkout', [
            'line' => $line,
            'user' => $request->user(),
            'onlinePayment' => $this->onlinePaymentEnabled() && ! $line->requires_inquiry && $line->price > 0,
        ]);
    }

    public function order(Request $request, OperationNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'sms_line_id' => ['required', Rule::exists('sms_lines', 'id')->where('is_active', true)],
            'desired_number' => ['nullable', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $line = SmsLine::findOrFail($data['sms_line_id']);
        $payOnline = $this->onlinePaymentEnabled() && ! $line->requires_inquiry && $line->price > 0;

        $order = LineOrder::create([
            'user_id' => $user->id,
            'sms_line_id' => $line->id,
            'line_label' => trim($line->group_label.' — '.$line->display_number),
            'price' => $line->price,
            'customer_name' => $user->full_name,
            'customer_phone' => $user->mobile,
            'customer_email' => $user->email,
            'company' => $user->company,
            'desired_number' => $data['desired_number'] ?? null,
            'note' => $data['note'] ?? null,
            'status' => $line->requires_inquiry ? 'pending' : 'awaiting_payment',
        ]);

        $notifier->lineOrderCreated($order);

        if ($payOnline) {
            return redirect()->route('dashboard.lines.pay', $order);
        }

        return redirect()->route('dashboard.lines.show', $order)->with('order_created', true);
    }

    public function show(Request $request, LineOrder $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return view('dashboard.lines.order', [
            'order' => $order,
            'justCreated' => (bool) session('order_created'),
            'canPayOnline' => $this->onlinePaymentEnabled() && $order->isPayable(),
            'walletBalance' => $request->user()->wallet()->balance,
            'receiptEnabled' => (bool) Setting::get('receipt_payment_enabled', true) && (bool) Setting::get('receipt_for_lines', true),
        ]);
    }

    /** Pay a pending line order from the wallet balance (docs/starter.md §23). */
    public function payFromWallet(Request $request, LineOrder $order, PayableSettlement $settlement): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if (! $order->isPayable()) {
            return redirect()->route('dashboard.lines.show', $order);
        }

        $wallet = $request->user()->wallet();

        if (! $wallet->hasSufficient((int) $order->price)) {
            return redirect()->route('dashboard.lines.show', $order)
                ->with('payment_error', 'موجودی کیف پول کافی نیست. ابتدا حساب خود را شارژ کنید.');
        }

        $wallet->debit((int) $order->price, 'line_purchase', $order, 'خرید خط '.$order->line_label, "line_order:{$order->id}");
        $settlement->settle($order, ['method' => 'wallet']);

        return redirect()->route('dashboard.lines.show', $order)->with('payment_success', true);
    }

    public function pay(Request $request, LineOrder $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if (! $this->onlinePaymentEnabled() || ! $order->isPayable()) {
            return redirect()->route('dashboard.lines.show', $order);
        }

        try {
            return $this->purchaseViaGateway(
                (int) $order->price,
                route('lines.payment.callback'),
                fn ($transactionId) => $order->update([
                    'transaction_id' => $transactionId,
                    'payment_driver' => config('payment.default'),
                ]),
            );
        } catch (\Throwable $e) {
            Log::error('Dashboard line payment purchase failed', ['order' => $order->id, 'error' => $e->getMessage()]);

            return redirect()->route('dashboard.lines.show', $order)
                ->with('payment_error', 'اتصال به درگاه پرداخت ممکن نشد. لطفاً بعداً دوباره تلاش کنید.');
        }
    }

    private function onlinePaymentEnabled(): bool
    {
        return (bool) Setting::get('line_payment_online', false);
    }
}
