<?php

namespace Tests\Feature\Dashboard;

use App\Models\Setting;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * `sms:delivery-sync` polls Melipayamak GetDelivery2 for the carrier receipt of
 * panel-sent messages and stops once the outcome is final (docs/starter.md §14).
 */
class SmsDeliverySyncTest extends TestCase
{
    use RefreshDatabase;

    private function panelUser(): User
    {
        return User::factory()->create([
            'status' => 'active',
            'approved_at' => now(),
            'sms_username' => 'panel-user',
            'sms_password' => 'panel-pass',
        ]);
    }

    private function message(User $user, array $attributes = []): SmsMessage
    {
        return SmsMessage::create(array_merge([
            'user_id' => $user->id,
            'to' => '09121112233',
            'from' => '3000',
            'body' => 'سلام',
            'parts' => 1,
            'status' => 'sent',
            'rec_id' => '25025255138',
        ], $attributes));
    }

    public function test_a_delivered_receipt_settles_the_message(): void
    {
        Http::fake([
            'api.payamak-panel.com/post/Send.asmx/GetDelivery2' => Http::response('<string xmlns="http://tempuri.org/">1</string>'),
        ]);

        $message = $this->message($this->panelUser());

        $this->artisan('sms:delivery-sync')->assertSuccessful();

        $message->refresh();
        $this->assertSame('delivered', $message->delivery_status);
        $this->assertSame('1', $message->delivery_code);
        $this->assertNotNull($message->delivery_checked_at);
        $this->assertTrue($message->deliveryIsFinal());
    }

    public function test_a_settled_message_is_not_polled_again(): void
    {
        Http::fake([
            'api.payamak-panel.com/*' => Http::response('<string>2</string>'),
        ]);

        $message = $this->message($this->panelUser(), [
            'delivery_status' => 'delivered',
            'delivery_checked_at' => now()->subDay(),
        ]);

        $this->artisan('sms:delivery-sync')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame('delivered', $message->refresh()->delivery_status);
    }

    public function test_a_pending_receipt_stays_in_the_queue(): void
    {
        Http::fake([
            'api.payamak-panel.com/*' => Http::response('<string>0</string>'),
        ]);

        $message = $this->message($this->panelUser());

        $this->artisan('sms:delivery-sync')->assertSuccessful();

        $message->refresh();
        $this->assertSame('pending', $message->delivery_status);
        $this->assertFalse($message->deliveryIsFinal());
        $this->assertTrue(SmsMessage::query()->awaitingDelivery()->whereKey($message->id)->exists());
    }

    public function test_messages_without_a_panel_or_rec_id_are_skipped(): void
    {
        Http::fake();

        $noPanel = User::factory()->create(['status' => 'active', 'approved_at' => now()]);
        $this->message($noPanel);
        $this->message($this->panelUser(), ['rec_id' => null]);

        $this->artisan('sms:delivery-sync')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_the_sync_can_be_disabled_by_setting(): void
    {
        Http::fake();
        Setting::set('sms_delivery_sync_enabled', '0');

        $this->message($this->panelUser());

        $this->artisan('sms:delivery-sync')->assertSuccessful();

        Http::assertNothingSent();
    }
}
