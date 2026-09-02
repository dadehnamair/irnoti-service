<?php

namespace Tests\Feature\Dashboard;

use App\Models\BankAccount;
use App\Models\BankReceipt;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\SmsPackage;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use App\Support\BankReceiptService;
use App\Support\PayableSettlement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Wallet, bank receipts, SMS packages and invoices (docs/starter.md §22 / §23).
 */
class WalletAndFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_topup_settles_once_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $topup = WalletTopup::create([
            'user_id' => $user->id,
            'amount' => 500000,
            'status' => 'awaiting_payment',
            'method' => 'online',
        ]);

        $settlement = app(PayableSettlement::class);
        $settlement->settle($topup, ['method' => 'online', 'reference_id' => 'REF-1']);
        $settlement->settle($topup, ['method' => 'online', 'reference_id' => 'REF-1']);

        $this->assertSame(500000, $user->fresh()->wallet()->balance);
        $this->assertSame('paid', $topup->fresh()->status);
        $this->assertSame(1, WalletTransaction::where('type', 'topup')->count());
    }

    public function test_topup_endpoint_creates_pending_row_and_redirects_to_gateway(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('dashboard.wallet.topup'), [
            'amount' => 250000,
            'method' => 'online',
        ])->assertRedirect();

        $topup = WalletTopup::firstOrFail();
        $this->assertSame(250000, $topup->amount);
        $this->assertSame('awaiting_payment', $topup->status);
    }

    public function test_topup_below_minimum_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('dashboard.wallet.topup'), [
            'amount' => 500,
            'method' => 'online',
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, WalletTopup::count());
    }

    public function test_wallet_transactions_are_immutable(): void
    {
        $user = User::factory()->create();
        $tx = $user->wallet()->credit(10000, 'adjustment', null, 'seed', 'seed:1');

        $this->expectException(RuntimeException::class);
        $tx->update(['amount' => 1]);
    }

    public function test_bank_receipt_approval_credits_wallet(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $account = BankAccount::create(['bank_name' => 'ملت', 'owner_name' => 'irnoti', 'is_active' => true]);

        $this->actingAs($user)->post(route('receipts.store'), [
            'for' => 'topup',
            'amount' => 300000,
            'tracking_code' => 'TRK-9',
            'transfer_type' => 'paya',
            'paid_at' => jalali_date(now()),
            'bank_account_id' => $account->id,
            'image' => UploadedFile::fake()->image('receipt.jpg'),
        ])->assertRedirect(route('dashboard.receipts'));

        $receipt = BankReceipt::firstOrFail();
        $this->assertSame('pending', $receipt->status);
        $this->assertSame(0, $user->fresh()->wallet()->balance);

        app(BankReceiptService::class)->approve($receipt, $user->id);

        $this->assertSame('approved', $receipt->fresh()->status);
        $this->assertSame(300000, $user->fresh()->wallet()->balance);
    }

    public function test_bank_receipt_for_a_subscription_activates_it(): void
    {
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'پایه', 'slug' => 'basic', 'price_monthly' => 990000, 'duration_days' => 30, 'is_active' => true]);
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'billing_period' => 'monthly',
            'price' => $plan->price_monthly,
            'status' => 'awaiting_payment',
        ]);

        $receipt = new BankReceipt([
            'amount' => $plan->price_monthly,
            'tracking_code' => 'TRK-SUB',
            'transfer_type' => 'satna',
            'paid_at' => now(),
            'status' => 'pending',
        ]);
        $receipt->user()->associate($user);
        $receipt->receiptable()->associate($subscription);
        $receipt->save();

        app(BankReceiptService::class)->approve($receipt, $user->id);

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame($plan->id, $user->fresh()->plan_id);
    }

    public function test_sms_package_purchase_from_wallet_adds_credit(): void
    {
        $user = User::factory()->create();
        $user->wallet()->credit(2000000, 'topup', null, 'seed', 'seed:pkg');
        $package = SmsPackage::create(['name' => 'ده هزارتایی', 'slug' => 'p10k', 'sms_count' => 10000, 'price' => 750000, 'is_active' => true]);

        $this->actingAs($user)->post(route('package-orders.order'), [
            'package' => $package->slug,
            'method' => 'wallet',
        ])->assertRedirect();

        $order = \App\Models\PackageOrder::firstOrFail();
        $this->assertSame('completed', $order->status);
        $this->assertSame(10000, $user->fresh()->sms_credit);
        $this->assertSame(2000000 - 750000, $user->fresh()->wallet()->balance);
    }

    public function test_issued_invoice_is_paid_from_wallet(): void
    {
        $user = User::factory()->create();
        $user->wallet()->credit(1000000, 'topup', null, 'seed', 'seed:inv');

        $invoice = Invoice::create(['user_id' => $user->id, 'title' => 'خدمات', 'status' => 'issued', 'issued_at' => now()]);
        $invoice->items()->create(['description' => 'ردیف ۱', 'quantity' => 2, 'unit_price' => 150000]);
        $invoice->refresh();
        $this->assertSame(300000, $invoice->total);

        $this->actingAs($user)->post(route('invoices.wallet', $invoice))
            ->assertRedirect(route('dashboard.invoices.show', $invoice));

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(700000, $user->fresh()->wallet()->balance);
    }

    public function test_invoice_wallet_payment_blocked_without_balance(): void
    {
        $user = User::factory()->create();
        $invoice = Invoice::create(['user_id' => $user->id, 'title' => 'خدمات', 'status' => 'issued', 'issued_at' => now()]);
        $invoice->items()->create(['description' => 'x', 'quantity' => 1, 'unit_price' => 500000]);

        $this->actingAs($user)->post(route('invoices.wallet', $invoice->fresh()))
            ->assertSessionHas('payment_error');

        $this->assertSame('issued', $invoice->fresh()->status);
    }
}
