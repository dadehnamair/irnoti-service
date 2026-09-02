<?php

namespace Tests\Feature\Dashboard;

use App\Models\Setting;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Panel features (send SMS, buy a line) stay locked until an admin approves the
 * account (docs/starter.md §39).
 */
class PanelGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_unapproved_account_is_bounced_from_sms_and_lines(): void
    {
        $user = User::factory()->create(['status' => 'awaiting_approval', 'approved_at' => null]);

        $this->actingAs($user)->get(route('dashboard.sms'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('gate_notice');

        $this->actingAs($user)->get(route('dashboard.lines'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_approved_account_can_open_the_sms_panel(): void
    {
        $user = User::factory()->create(['status' => 'active', 'approved_at' => now()]);

        $this->actingAs($user)->get(route('dashboard.sms'))
            ->assertOk()
            ->assertSee('پنل پیامک شما هنوز فعال نشده');
    }

    public function test_gate_can_be_lifted_by_setting(): void
    {
        Setting::updateOrCreate(['key' => 'require_admin_approval'], [
            'value' => '0', 'type' => 'bool', 'group' => 'account',
        ]);
        Cache::forget(Setting::CACHE_KEY);

        $user = User::factory()->create(['status' => 'awaiting_approval', 'approved_at' => null]);

        $this->actingAs($user)->get(route('dashboard.sms'))->assertOk();
    }

    public function test_credit_is_shown_from_both_endpoints(): void
    {
        Http::fake([
            'api.payamak-panel.com/post/Users.asmx/GetUserCredit' => Http::response('<?xml version="1.0"?><double xmlns="http://tempuri.org/">842</double>', 200),
            'api.payamak-panel.com/post/Users.asmx/GetUserCredit2' => Http::response('<?xml version="1.0"?><double xmlns="http://tempuri.org/">1560000</double>', 200),
        ]);

        $user = User::factory()->create([
            'status' => 'active',
            'approved_at' => now(),
            'sms_username' => 'panel-user',
            'sms_password' => 'panel-pass',
        ]);

        $this->actingAs($user)->get(route('dashboard.sms'))
            ->assertOk()
            ->assertSee('842 پیامک')
            ->assertSee('اعتبار ریالی');
    }

    public function test_bad_credentials_show_a_readable_error(): void
    {
        Http::fake([
            'api.payamak-panel.com/*' => Http::response('<double>-1</double>', 200),
        ]);

        $user = User::factory()->create([
            'status' => 'active',
            'approved_at' => now(),
            'sms_username' => 'wrong',
            'sms_password' => 'wrong',
        ]);

        $this->actingAs($user)->get(route('dashboard.sms'))
            ->assertOk()
            ->assertSee('نام کاربری یا رمز عبور پنل پیامک نادرست است.');
    }

    public function test_approved_user_with_panel_credentials_sends_and_logs(): void
    {
        Http::fake([
            'api.payamak-panel.com/post/Send.asmx/SendSimpleSMS2' => Http::response('<string xmlns="http://tempuri.org/">25025255138</string>', 200),
            'api.payamak-panel.com/*' => Http::response('<double>0</double>', 200),
        ]);

        $user = User::factory()->create([
            'status' => 'active',
            'approved_at' => now(),
            'sms_username' => 'panel-user',
            'sms_password' => 'panel-pass',
            'sms_sender' => '30001234',
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.sms.send'), [
                'to' => '09121234567',
                'message' => 'سلام تست',
            ])
            ->assertRedirect(route('dashboard.sms'))
            ->assertSessionHas('sms_status');

        $this->assertDatabaseHas('sms_messages', [
            'user_id' => $user->id,
            'to' => '09121234567',
            'status' => 'sent',
            'rec_id' => '25025255138',
        ]);
        $this->assertSame(1, SmsMessage::where('user_id', $user->id)->count());
    }

    public function test_send_rejects_a_bad_number(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'approved_at' => now(),
            'sms_username' => 'panel-user',
            'sms_password' => 'panel-pass',
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.sms.send'), ['to' => '12345', 'message' => 'x'])
            ->assertSessionHasErrors('to');
    }
}
