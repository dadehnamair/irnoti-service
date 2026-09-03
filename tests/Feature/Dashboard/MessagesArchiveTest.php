<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The «پیام‌ها» menu (docs/starter.md §14) — دریافتی / ارسالی read live from the
 * provider's GetMessages through the customer's own panel credentials.
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

    private function fakeArchive(array $rows): void
    {
        $elements = collect($rows)->map(function (array $row) {
            $inner = collect($row)
                ->map(fn ($v, $k) => "<{$k}>".htmlspecialchars((string) $v)."</{$k}>")
                ->implode('');

            return "<MessagesBL>{$inner}</MessagesBL>";
        })->implode('');

        Http::fake([
            'api.payamak-panel.com/post/Send.asmx/GetMessages' => Http::response(
                '<?xml version="1.0" encoding="utf-8"?>'
                .'<ArrayOfMessagesBL xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://tempuri.org/">'
                .$elements.'</ArrayOfMessagesBL>',
                200,
            ),
            'api.payamak-panel.com/*' => Http::response('<double>0</double>', 200),
        ]);
    }

    public function test_inbox_lists_incoming_messages_from_the_provider(): void
    {
        $this->fakeArchive([[
            'MsgID' => 987654321,
            'Body' => 'سلام، این یک پیام دریافتی است',
            'Sender' => '9121112233',
            'Receiver' => '30001234567',
            'SendDate' => '2026-08-20T10:15:00',
            'Parts' => 1,
            'RecCount' => 1,
            'RecSuccess' => 0,
            'RecFailed' => 0,
        ]]);

        $this->actingAs($this->approvedPanelUser())
            ->get(route('dashboard.messages.inbox'))
            ->assertOk()
            ->assertSee('پیام‌های دریافتی')
            ->assertSee('سلام، این یک پیام دریافتی است')
            ->assertSee('09121112233') // sender, normalised for display
            ->assertSee('30001234567'); // the account's سرشماره

        Http::assertSent(fn ($request) => str_contains($request->url(), 'Send.asmx/GetMessages')
            && (string) $request['location'] === '1'
            && (string) $request['index'] === '0');
    }

    public function test_sent_box_lists_outgoing_messages_and_paginates(): void
    {
        $this->fakeArchive([[
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

        $this->actingAs($this->approvedPanelUser())
            ->get(route('dashboard.messages.sent', ['page' => 3]))
            ->assertOk()
            ->assertSee('پیام‌های ارسالی')
            ->assertSee('پیام ارسالی نمونه')
            ->assertSee('4 از 5') // success / total
            ->assertSee('صفحهٔ قبل'); // page 3 → a "previous" link is offered

        Http::assertSent(fn ($request) => str_contains($request->url(), 'Send.asmx/GetMessages')
            && (string) $request['location'] === '2'
            && (string) $request['index'] === '50'); // (3 - 1) * 25
    }

    public function test_provider_failure_shows_a_graceful_notice(): void
    {
        Http::fake([
            'api.payamak-panel.com/post/Send.asmx/GetMessages' => Http::response('boom', 500),
            'api.payamak-panel.com/*' => Http::response('<double>0</double>', 200),
        ]);

        $this->actingAs($this->approvedPanelUser())
            ->get(route('dashboard.messages.inbox'))
            ->assertOk()
            ->assertSee('دریافت فهرست پیام‌ها');
    }

    public function test_customer_without_a_panel_sees_the_inactive_notice(): void
    {
        Http::fake();
        $user = $this->approvedPanelUser(['sms_username' => null, 'sms_password' => null]);

        $this->actingAs($user)
            ->get(route('dashboard.messages.sent'))
            ->assertOk()
            ->assertSee('پنل پیامک شما هنوز فعال نشده است');

        Http::assertNothingSent();
    }
}
