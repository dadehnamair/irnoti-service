<?php

namespace App\Observers;

use App\Http\Controllers\LineController;
use App\Models\LineOrder;
use App\Support\OperationNotifier;

/**
 * Texts the buyer whenever the admin moves a line order along the status
 * workflow from the Filament panel (docs/starter.md §11 / §44). The
 * "paid" transition is announced by {@see LineController::paymentCallback()}
 * itself, so it's skipped here to avoid a double SMS.
 */
class LineOrderObserver
{
    public function __construct(private readonly OperationNotifier $notifier) {}

    public function updated(LineOrder $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        if ($order->status === 'paid') {
            return;
        }

        $this->notifier->lineOrderStatusChanged($order);
    }
}
