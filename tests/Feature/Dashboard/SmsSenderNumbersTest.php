<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Dedicated sender numbers (سرشماره) — pulled from Melipayamak's GetUserNumbers,
 * cached on the user, picked by the customer per-send and as a default
 * (docs/starter.md §12).
 */
class SmsSenderNumbersTest extends TestCase
{
    use RefreshDatabase;

    private function approvedPanelUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'status' => 'active',
            'approved_at' => now(),
            'sms_username' => 'panel-user',
            'sms_password' => 'panel-pass',
        ], $attributes));
    }

    private function fakeNumbers(array $numbers): void
    {
        $strings = implode('', array_map(fn ($n) => "<string>{$n}</string>", $numbers));

        Http::fake([
            'api.payamak-panel.com/post/Users.asmx/GetUserNumbers' => Http::response(
                '<?xml version="1.0" encoding="utf-8"?><ArrayOfString xmlns="http://tempuri.org/">'.$strings.'</ArrayOfString>',
                200,
            ),
            'api.payamak-panel.com/*' => Http::response('<double>0</double>', 200),
        ]);
    }

    public function test_opening_the_panel_syncs_and_caches_sender_numbers(): void
    {
        $this->fakeNumbers(['30001234', '50004000']);
        $user = $this->approvedPanelUser();

        $this->actingAs($user)->get(route('dashboard.sms'))
            ->assertOk()
            ->assertSee('30001234')
            ->assertSee('50004000');

        $user->refresh();
        $this->assertSame(['30001234', '50004000'], $user->sms_numbers);
        $this->assertNotNull($user->sms_numbers_synced_at);
        // sms_sender was blank → first number becomes the default.
        $this->assertSame('30001234', $user->sms_sender);
    }

    public function test_fresh_sync_is_skipped_when_the_cache_is_recent(): void
    {
        $this->fakeNumbers(['99999999']);
        $user = $this->approvedPanelUser([
            'sms_numbers' => ['30001234'],
            'sms_numbers_synced_at' => now()->subMinutes(5),
            'sms_sender' => '30001234',
        ]);

        $this->actingAs($user)->get(route('dashboard.sms'))->assertOk();

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'GetUserNumbers'));
        $this->assertSame(['30001234'], $user->fresh()->sms_numbers);
    }

    public function test_manual_refresh_pulls_the_list_again(): void
    {
        $this->fakeNumbers(['30001234', '50004000']);
        $user = $this->approvedPanelUser([
            'sms_numbers' => ['30001234'],
            'sms_numbers_synced_at' => now(),
            'sms_sender' => '30001234',
        ]);

        $this->actingAs($user)->post(route('dashboard.sms.numbers.refresh'))
            ->assertRedirect(route('dashboard.sms'))
            ->assertSessionHas('sms_status');

        $this->assertSame(['30001234', '50004000'], $user->fresh()->sms_numbers);
    }

    public function test_customer_sets_a_default_sender(): void
    {
        $user = $this->approvedPanelUser([
            'sms_numbers' => ['30001234', '50004000'],
            'sms_numbers_synced_at' => now(),
            'sms_sender' => '30001234',
        ]);

        $this->actingAs($user)->post(route('dashboard.sms.numbers.default'), ['from' => '50004000'])
            ->assertRedirect(route('dashboard.sms'))
            ->assertSessionHas('sms_status');

        $this->assertSame('50004000', $user->fresh()->sms_sender);
    }

    public function test_default_sender_must_belong_to_the_account(): void
    {
        $user = $this->approvedPanelUser([
            'sms_numbers' => ['30001234'],
            'sms_numbers_synced_at' => now(),
            'sms_sender' => '30001234',
        ]);

        $this->actingAs($user)->post(route('dashboard.sms.numbers.default'), ['from' => '40000000'])
            ->assertSessionHasErrors('from');

        $this->assertSame('30001234', $user->fresh()->sms_sender);
    }

    public function test_send_uses_the_chosen_sender_line(): void
    {
        Http::fake([
            'api.payamak-panel.com/post/Send.asmx/SendSimpleSMS2' => Http::response('<string xmlns="http://tempuri.org/">25025255138</string>', 200),
            'api.payamak-panel.com/*' => Http::response('<double>0</double>', 200),
        ]);

        $user = $this->approvedPanelUser([
            'sms_numbers' => ['30001234', '50004000'],
            'sms_numbers_synced_at' => now(),
            'sms_sender' => '30001234',
        ]);

        $this->actingAs($user)->post(route('dashboard.sms.send'), [
            'to' => '09121234567',
            'from' => '50004000',
            'message' => 'سلام تست',
        ])->assertRedirect(route('dashboard.sms'))->assertSessionHas('sms_status');

        $this->assertDatabaseHas('sms_messages', [
            'user_id' => $user->id,
            'to' => '09121234567',
            'from' => '50004000',
            'status' => 'sent',
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'SendSimpleSMS2')
            && $request['from'] === '50004000');
    }

    public function test_send_rejects_a_sender_line_not_on_the_account(): void
    {
        $user = $this->approvedPanelUser([
            'sms_numbers' => ['30001234'],
            'sms_numbers_synced_at' => now(),
            'sms_sender' => '30001234',
        ]);

        $this->actingAs($user)->post(route('dashboard.sms.send'), [
            'to' => '09121234567',
            'from' => '90000000',
            'message' => 'x',
        ])->assertSessionHasErrors('from');
    }
}
