<?php

namespace Tests\Feature\Dashboard;

use App\Jobs\SyncProviderMessagesJob;
use App\Models\ProviderMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The «پیام‌ها» menu (docs/starter.md §14) — دریافتی / ارسالی render from the local
 * {@see ProviderMessage} mirror; opening a page queues {@see SyncProviderMessagesJob}
 * to refresh that mirror from the provider's getMessages.
 */
class MessagesArchiveTest extends TestCase
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

    /** An ASMX ArrayOfMessagesBL body for the given rows. */
    private function messagesXml(array $rows): string
    {
        $elements = collect($rows)->map(function (array $row) {
            $inner = collect($row)
                ->map(fn ($v, $k) => "<{$k}>".htmlspecialchars((string) $v)."</{$k}>")
                ->implode('');

            return "<MessagesBL>{$inner}</MessagesBL>";
        })->implode('');

        return '<?xml version="1.0" encoding="utf-8"?>'
            .'<ArrayOfMessagesBL xmlns="http://tempuri.org/">'.$elements.'</ArrayOfMessagesBL>';
    }

    /** Fake getMessages: inbox rows for location=1, sent rows for location=2. */
    private function fakeGetMessages(array $inbox = [], array $sent = []): void
    {
        Http::fake([
            'api.payamak-panel.com/post/Send.asmx/getMessages' => function ($request) use ($inbox, $sent) {
                return Http::response($this->messagesXml((string) $request['location'] === '2' ? $sent : $inbox), 200);
            },
            'api.payamak-panel.com/*' => Http::response('<double>0</double>', 200),
        ]);
    }

    public function test_opening_inbox_syncs_the_mirror_and_lists_incoming_messages(): void
    {
        $this->fakeGetMessages(inbox: [[
            'MsgID' => 987654321,
            'Body' => 'سلام، این یک پیام دریافتی است',
            'Sender' => '9121112233',
            'Receiver' => '30001234567',
            'SendDate' => '2026-08-20T10:15:00',
            'Parts' => 1,
        ]]);

        $user = $this->approvedPanelUser();

        $this->actingAs($user)->get(route('dashboard.messages.inbox'))
            ->assertOk()
            ->assertSee('پیام‌های دریافتی')
            ->assertSee('سلام، این یک پیام دریافتی است')
            ->assertSee('09121112233')  // sender, normalised for display
            ->assertSee('30001234567'); // the account's سرشماره

        $this->assertDatabaseHas('provider_messages', [
            'user_id' => $user->id,
            'direction' => 'inbox',
            'provider_msg_id' => '987654321',
            'sender' => '9121112233',
        ]);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'Send.asmx/getMessages') && (string) $r['location'] === '1');
    }

    public function test_sent_box_lists_outgoing_messages_from_the_mirror(): void
    {
        $this->fakeGetMessages(sent: [[
            'MsgID' => 111,
            'Body' => 'پیام ارسالی نمونه',
            'Sender' => '30001234567',
            'Receiver' => '9120000000',
            'SendDate' => '2026-08-21T08:00:00',
            'Parts' => 2,
            'RecCount' => 5,
            'RecSuccess' => 4,
            'RecFailed' => 1,
        ]]);

        $this->actingAs($this->approvedPanelUser())->get(route('dashboard.messages.sent'))
            ->assertOk()
            ->assertSee('پیام‌های ارسالی')
            ->assertSee('پیام ارسالی نمونه')
            ->assertSee('4 از 5');

        $this->assertDatabaseHas('provider_messages', [
            'direction' => 'sent',
            'provider_msg_id' => '111',
            'rec_success' => 4,
            'rec_count' => 5,
        ]);
    }

    public function test_sync_is_idempotent_and_updates_changed_counts(): void
    {
        $user = $this->approvedPanelUser();
        $recSuccess = 2;

        Http::fake([
            'api.payamak-panel.com/post/Send.asmx/getMessages' => function ($request) use (&$recSuccess) {
                if ((string) $request['location'] !== '2') {
                    return Http::response($this->messagesXml([]), 200);
                }

                return Http::response($this->messagesXml([[
                    'MsgID' => 55, 'Body' => 'x', 'Sender' => '3000', 'Receiver' => '9120000000',
                    'SendDate' => '2026-08-21T08:00:00', 'RecCount' => 5, 'RecSuccess' => $recSuccess,
                ]]), 200);
            },
            'api.payamak-panel.com/*' => Http::response('<double>0</double>', 200),
        ]);

        (new SyncProviderMessagesJob($user->id))->handle();
        $recSuccess = 5;
        (new SyncProviderMessagesJob($user->id))->handle();

        $this->assertSame(1, ProviderMessage::where('user_id', $user->id)->count());
        $this->assertSame(5, ProviderMessage::where('provider_msg_id', '55')->value('rec_success'));
    }

    public function test_page_still_renders_when_the_provider_is_down(): void
    {
        Http::fake(['api.payamak-panel.com/*' => Http::response('boom', 500)]);

        $user = $this->approvedPanelUser();
        ProviderMessage::create([
            'user_id' => $user->id, 'direction' => 'inbox', 'provider_msg_id' => '1',
            'sender' => '9121110000', 'receiver' => '3000', 'body' => 'پیام ذخیره‌شده',
            'sent_at' => now()->subDay(),
        ]);

        $this->actingAs($user)->get(route('dashboard.messages.inbox'))
            ->assertOk()
            ->assertSee('پیام ذخیره‌شده'); // the stored row is shown regardless
    }

    public function test_refresh_button_forces_a_sync(): void
    {
        Bus::fake();

        $this->actingAs($this->approvedPanelUser())
            ->post(route('dashboard.messages.refresh', 'sent'))
            ->assertRedirect(route('dashboard.messages.sent'))
            ->assertSessionHas('sms_status');

        Bus::assertDispatched(SyncProviderMessagesJob::class);
    }

    public function test_customer_without_a_panel_sees_the_inactive_notice(): void
    {
        Http::fake();
        $user = $this->approvedPanelUser(['sms_username' => null, 'sms_password' => null]);

        $this->actingAs($user)->get(route('dashboard.messages.sent'))
            ->assertOk()
            ->assertSee('پنل پیامک شما هنوز فعال نشده است');

        Http::assertNothingSent();
    }
}
