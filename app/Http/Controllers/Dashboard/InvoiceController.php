<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Setting;
use App\Support\HandlesGatewayPayment;
use App\Support\PayableSettlement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;

/**
 * Customer view of admin-issued invoices (docs/starter.md §22 / §51). The
 * customer pays online, from wallet balance, or by submitting a bank receipt;
 * online/wallet settle immediately, a receipt waits for admin approval.
 */
class InvoiceController extends Controller
{
    use HandlesGatewayPayment;

    public function index(Request $request): View
    {
        return view('dashboard.invoices', [
            'invoices' => $request->user()->invoices()
                ->whereIn('status', ['issued', 'awaiting_payment', 'paid', 'cancelled'])
                ->paginate(15),
        ]);
    }

    public function show(Request $request, Invoice $invoice): View
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);
        abort_if($invoice->status === 'draft', 404);

        return view('dashboard.invoice', [
            'invoice' => $invoice->load('items'),
            'canPayOnline' => $this->onlinePaymentEnabled() && $invoice->isPayable(),
            'walletBalance' => $request->user()->wallet()->balance,
            'receiptEnabled' => $this->receiptEnabled(),
        ]);
    }

    public function payFromWallet(Request $request, Invoice $invoice, PayableSettlement $settlement): RedirectResponse
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        if (! $invoice->isPayable()) {
            return redirect()->route('dashboard.invoices.show', $invoice);
        }

        $wallet = $request->user()->wallet();

        if (! $wallet->hasSufficient((int) $invoice->total)) {
            return redirect()->route('dashboard.invoices.show', $invoice)
                ->with('payment_error', 'موجودی کیف پول کافی نیست. ابتدا حساب خود را شارژ کنید.');
        }

        $wallet->debit((int) $invoice->total, 'invoice_payment', $invoice, 'پرداخت صورت‌حساب '.$invoice->number, "invoice:{$invoice->id}");
        $settlement->settle($invoice, ['method' => 'wallet']);

        return redirect()->route('dashboard.invoices.show', $invoice)->with('payment_success', true);
    }

    public function pay(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        if (! $this->onlinePaymentEnabled() || ! $invoice->isPayable()) {
            return redirect()->route('dashboard.invoices.show', $invoice);
        }

        try {
            return $this->purchaseViaGateway(
                (int) $invoice->total,
                route('invoices.callback'),
                function ($transactionId) use ($invoice) {
                    $invoice->forceFill(['payment_method' => 'online', 'status' => 'awaiting_payment'])->save();
                    // Invoices have no transaction_id column — map it in the cache for the callback.
                    cache()->put("invoice_txn:$transactionId", $invoice->id, now()->addHours(2));
                },
            );
        } catch (\Throwable $e) {
            Log::error('Invoice payment purchase failed', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);

            return redirect()->route('dashboard.invoices.show', $invoice)
                ->with('payment_error', 'اتصال به درگاه پرداخت ممکن نشد. لطفاً بعداً تلاش کنید.');
        }
    }

    public function callback(Request $request, PayableSettlement $settlement): RedirectResponse
    {
        $transactionId = $this->gatewayTransactionId($request);
        $invoiceId = $transactionId ? cache()->pull("invoice_txn:$transactionId") : null;
        $invoice = $invoiceId ? Invoice::find($invoiceId) : null;

        if (! $invoice) {
            return redirect()->route('dashboard.invoices')->with('payment_error', 'صورت‌حساب مربوط به این پرداخت پیدا نشد.');
        }

        if ($this->gatewayPaymentCancelled($request)) {
            return redirect()->route('dashboard.invoices.show', $invoice)
                ->with('payment_error', 'پرداخت لغو شد. می‌توانید دوباره تلاش کنید.');
        }

        try {
            $receipt = $this->verifyViaGateway((int) $invoice->total, $transactionId);

            $settlement->settle($invoice, [
                'method' => 'online',
                'reference_id' => $receipt->getReferenceId(),
            ]);

            return redirect()->route('dashboard.invoices.show', $invoice)->with('payment_success', true);
        } catch (InvalidPaymentException $e) {
            return redirect()->route('dashboard.invoices.show', $invoice)
                ->with('payment_error', $e->getMessage() ?: 'پرداخت ناموفق بود یا لغو شد.');
        } catch (\Throwable $e) {
            Log::error('Invoice payment verify failed', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);

            return redirect()->route('dashboard.invoices.show', $invoice)
                ->with('payment_error', 'تأیید پرداخت با خطا مواجه شد. اگر مبلغ کسر شده با پشتیبانی تماس بگیرید.');
        }
    }

    private function onlinePaymentEnabled(): bool
    {
        return filled(config('payment.default'));
    }

    private function receiptEnabled(): bool
    {
        return (bool) Setting::get('receipt_payment_enabled', true)
            && (bool) Setting::get('receipt_for_invoices', true);
    }
}
