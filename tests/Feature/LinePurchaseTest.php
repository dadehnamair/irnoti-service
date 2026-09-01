<?php

namespace Tests\Feature;

use App\Models\LineOrder;
use App\Models\Setting;
use App\Models\SmsLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LinePurchaseTest extends TestCase
{
    use RefreshDatabase;

    private function line(array $overrides = []): SmsLine
    {
        return SmsLine::create(array_merge([
            'prefix' => '3000',
            'operator' => 'آسیاتک',
            'digits' => 10,
            'line_type' => 'dedicated',
            'price' => 390000,
            'sale_status' => 'available',
            'is_active' => true,
        ], $overrides));
    }

    private function setOnlinePayment(bool $on): void
    {
        Setting::updateOrCreate(['key' => 'line_payment_online'], [
            'value' => $on ? '1' : '0',
            'type' => 'bool',
            'group' => 'commerce',
        ]);
        Cache::forget(Setting::CACHE_KEY);
    }

    public function test_lines_page_and_checkout_render(): void
    {
        $line = $this->line();

        $this->get('/lines')->assertOk()->assertSee('خطوط 3000');
        $this->get("/lines/{$line->id}/checkout")->assertOk()->assertSee('تکمیل سفارش');
    }

    public function test_order_without_online_payment_lands_on_tracking(): void
    {
        $this->setOnlinePayment(false);
        $line = $this->line();

        $response = $this->post('/lines/order', [
            'sms_line_id' => $line->id,
            'customer_name' => 'علی تست',
            'customer_phone' => '09120000000',
        ]);

        $order = LineOrder::firstOrFail();
        $response->assertRedirect(route('lines.track', $order));
        $this->assertSame('awaiting_payment', $order->status);
        $this->assertSame(390000, $order->price);
    }

    public function test_order_with_online_payment_redirects_to_gateway(): void
    {
        $this->setOnlinePayment(true);
        $line = $this->line();

        $response = $this->post('/lines/order', [
            'sms_line_id' => $line->id,
            'customer_name' => 'علی تست',
            'customer_phone' => '09120000000',
        ]);

        $order = LineOrder::firstOrFail();
        $response->assertRedirect(route('lines.pay', $order));

        // The "local" test driver renders an HTML gateway form and stores the txid.
        $this->get(route('lines.pay', $order))->assertOk()->assertSee('درگاه پرداخت تست');
        $this->assertNotNull($order->fresh()->transaction_id);
    }

    public function test_gateway_callback_marks_order_paid(): void
    {
        $this->setOnlinePayment(true);
        $line = $this->line();
        $order = LineOrder::create([
            'sms_line_id' => $line->id,
            'line_label' => 'خطوط 3000',
            'price' => $line->price,
            'customer_name' => 'x',
            'customer_phone' => '0912',
            'status' => 'awaiting_payment',
            'transaction_id' => '1234567',
        ]);

        $this->get('/lines/payment/callback?transactionId=1234567')
            ->assertRedirect(route('lines.track', $order));

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertNotNull($order->reference_id);
    }

    public function test_requires_inquiry_line_never_goes_online(): void
    {
        $this->setOnlinePayment(true);
        $line = $this->line(['requires_inquiry' => true, 'price' => 0]);

        $response = $this->post('/lines/order', [
            'sms_line_id' => $line->id,
            'customer_name' => 'x',
            'customer_phone' => '0912',
        ]);

        $order = LineOrder::firstOrFail();
        $response->assertRedirect(route('lines.track', $order));
        $this->assertSame('pending', $order->status);
    }
}
