<?php

namespace App\Support;

use App\Models\BankReceipt;
use Illuminate\Support\Facades\DB;

/**
 * Reviews a {@see BankReceipt}. Approval runs the same domain effect the online
 * gateway would have (via {@see PayableSettlement}) — exactly once — then flips
 * the receipt to "approved". Used by the customer-facing flow and by the Filament
 * admin review actions.
 */
class BankReceiptService
{
    public function __construct(
        private PayableSettlement $settlement,
        private OperationNotifier $notifier,
    ) {}

    public function approve(BankReceipt $receipt, ?int $reviewerId = null, ?string $note = null): void
    {
        if ($receipt->status === 'approved') {
            return;
        }

        DB::transaction(function () use ($receipt, $reviewerId, $note) {
            $receipt->forceFill([
                'status' => 'approved',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'admin_note' => $note ?? $receipt->admin_note,
            ])->save();

            $payment = [
                'method' => 'receipt',
                'reference_id' => $receipt->tracking_code,
            ];

            if ($payable = $receipt->receiptable) {
                $this->settlement->settle($payable, $payment);
            } else {
                // A bare wallet top-up with no WalletTopup row attached.
                $receipt->user->wallet()->credit(
                    (int) $receipt->amount,
                    'topup',
                    $receipt,
                    'شارژ کیف پول (فیش بانکی)',
                    "receipt:{$receipt->id}",
                    ['method' => 'receipt'],
                );
                $this->notifier->walletToppedUp($receipt->user, (int) $receipt->amount);
            }
        });

        $this->notifier->bankReceiptApproved($receipt);
    }

    public function reject(BankReceipt $receipt, ?int $reviewerId = null, ?string $note = null): void
    {
        if ($receipt->status === 'rejected') {
            return;
        }

        $receipt->forceFill([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'admin_note' => $note ?? $receipt->admin_note,
        ])->save();

        $this->notifier->bankReceiptRejected($receipt);
    }
}
