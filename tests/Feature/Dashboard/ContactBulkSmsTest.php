<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Group send from the phonebook (docs/starter.md §17 / §18): "local" resolves
 * groups to stored numbers and sends via our gateway; "melipayamak" hands whole
 * groups to newbulks.asmx/SendSmsToContact.
 */
class ContactBulkSmsTest extends TestCase
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

    public function test_local_group_send_creates_one_message_per_recipient(): void
    {
        Http::fake([
            'api.payamak-panel.com/post/Send.asmx/SendSimpleSMS2' => Http::response('<string xmlns="http://tempuri.org/">25025255138</string>'),
            'api.payamak-panel.com/*' => Http::response('<string>0</string>'),
        ]);

        $user = $this->panelUser();
        $group = $user->contactGroups()->create(['name' => 'گیرندگان']);
        $group->contacts()->attach($user->contacts()->create(['mobile' => '09121112233'])->id);

        $this->actingAs($user)->post(route('dashboard.contacts.send.post'), [
            'mode' => 'local',
            'groups' => [$group->id],
            'numbers' => '09124445566',
            'message' => 'سلام گروهی',
        ])->assertRedirect(route('dashboard.contacts.send'));

        $this->assertDatabaseHas('sms_messages', ['user_id' => $user->id, 'to' => '09121112233', 'status' => 'sent']);
        $this->assertDatabaseHas('sms_messages', ['user_id' => $user->id, 'to' => '09124445566', 'status' => 'sent']);
    }

    public function test_melipayamak_group_send_calls_send_sms_to_contact(): void
    {
        Http::fake([
            'api.payamak-panel.com/post/newbulks.asmx/SendSmsToContact*' => Http::response('<string xmlns="http://tempuri.org/">987654321</string>'),
            'api.payamak-panel.com/*' => Http::response('<string>0</string>'),
        ]);

        $user = $this->panelUser();
        $group = $user->contactGroups()->create([
            'name' => 'گروه همگام', 'remote_id' => 55, 'sync_status' => 'synced', 'synced_at' => now(),
        ]);

        $this->actingAs($user)->post(route('dashboard.contacts.send.post'), [
            'mode' => 'melipayamak',
            'groups' => [$group->id],
            'message' => 'پیام گروهی ملی‌پیامک',
        ])->assertRedirect(route('dashboard.contacts.send'))->assertSessionHas('sms_status');

        Http::assertSent(fn ($r) => str_contains($r->url(), 'newbulks.asmx/SendSmsToContact'));
        $this->assertDatabaseHas('sms_messages', ['user_id' => $user->id, 'rec_id' => '987654321', 'status' => 'sent']);
    }

    public function test_melipayamak_mode_rejects_an_unsynced_group(): void
    {
        Http::fake(['api.payamak-panel.com/*' => Http::response('<string>0</string>')]);

        $user = $this->panelUser();
        $group = $user->contactGroups()->create(['name' => 'همگام‌نشده']);

        $this->actingAs($user)->post(route('dashboard.contacts.send.post'), [
            'mode' => 'melipayamak',
            'groups' => [$group->id],
            'message' => 'x',
        ])->assertSessionHas('sms_error');

        $this->assertDatabaseCount('sms_messages', 0);
    }
}
