<?php

namespace Tests\Feature\Dashboard;

use App\Jobs\SendMessengerCampaignJob;
use App\Models\MessengerCampaign;
use App\Models\Setting;
use App\Models\User;
use App\Services\Messenger\MessengerManager;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * پیام‌رسان‌ها (docs/starter.md §91): bulk send to بله / ایتا / واتساپ. Cost is
 * taken from the wallet up front; SendMessengerCampaignJob delivers via the
 * configured channel driver and refunds whatever fails. Tests run with
 * MESSENGER_DRIVER=null (every recipient fails) unless a test binds the log
 * channel for a happy path.
 */
class MessengerCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    private function approvedUser(int $walletBalance = 0): User
    {
        $user = User::factory()->create(['status' => 'active', 'approved_at' => now()]);

        if ($walletBalance > 0) {
            $user->wallet()->credit($walletBalance, 'topup', null, 'شارژ آزمایشی');
        }

        return $user;
    }

    private function groupWith(User $user, array $mobiles): int
    {
        $group = $user->contactGroups()->create(['name' => 'گیرندگان']);

        foreach ($mobiles as $mobile) {
            $group->contacts()->attach($user->contacts()->create(['mobile' => $mobile])->id);
        }

        return $group->id;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard.messenger'))->assertRedirect(route('login'));
    }

    public function test_unapproved_user_is_bounced_to_dashboard(): void
    {
        $user = User::factory()->create(['status' => 'active', 'approved_at' => null]);

        $this->actingAs($user)->get(route('dashboard.messenger'))->assertRedirect(route('dashboard'));
    }

    public function test_disabled_channel_is_not_found(): void
    {
        $user = $this->approvedUser();

        // whatsapp ships disabled (messenger_whatsapp_enabled = 0)
        $this->actingAs($user)->get(route('dashboard.messenger.create', 'whatsapp'))->assertNotFound();

        $this->actingAs($user)->post(route('dashboard.messenger.send'), [
            'channel' => 'whatsapp',
            'recipients' => '09121112233',
            'message' => 'سلام',
        ])->assertNotFound();

        $this->assertDatabaseCount('messenger_campaigns', 0);
    }

    public function test_master_switch_off_returns_404(): void
    {
        Setting::set('messenger_enabled', '0');
        $user = $this->approvedUser();

        $this->actingAs($user)->get(route('dashboard.messenger'))->assertNotFound();
    }

    public function test_send_debits_wallet_and_queues_the_job(): void
    {
        Bus::fake();
        Setting::set('messenger_bale_tariff', '100');

        $user = $this->approvedUser(walletBalance: 10_000);
        $groupId = $this->groupWith($user, ['09121112233', '09124445566']);

        $this->actingAs($user)->post(route('dashboard.messenger.send'), [
            'channel' => 'bale',
            'groups' => [$groupId],
            'recipients' => '09127778899',
            'message' => 'سلام گروهی',
        ])->assertRedirect(route('dashboard.messenger'))->assertSessionHas('status');

        $campaign = MessengerCampaign::firstOrFail();
        $this->assertSame('bale', $campaign->channel);
        $this->assertSame(3, $campaign->recipients_count);
        $this->assertSame(300, $campaign->cost);
        $this->assertSame('queued', $campaign->status);
        $this->assertDatabaseCount('messenger_recipients', 3);
        $this->assertSame(9_700, $user->fresh()->walletBalance());

        Bus::assertDispatched(SendMessengerCampaignJob::class, fn ($job) => $job->campaignId === $campaign->id);
    }

    public function test_job_marks_campaign_sent_when_the_channel_succeeds(): void
    {
        $this->app->singleton(MessengerManager::class, fn () => new MessengerManager('log'));

        $user = $this->approvedUser();
        $groupId = $this->groupWith($user, ['09121112233']);

        $this->actingAs($user)->post(route('dashboard.messenger.send'), [
            'channel' => 'bale',
            'groups' => [$groupId],
            'message' => 'سلام',
        ])->assertRedirect(route('dashboard.messenger'));

        $campaign = MessengerCampaign::firstOrFail();
        $this->assertSame('sent', $campaign->status);
        $this->assertSame(1, $campaign->success_count);
        $this->assertSame(0, $campaign->failed_count);
        $this->assertDatabaseHas('messenger_recipients', ['to' => '09121112233', 'status' => 'sent']);
    }

    public function test_failed_delivery_refunds_the_failed_portion(): void
    {
        // The "null" transport fails every recipient — exercises the refund path.
        $this->app->singleton(MessengerManager::class, fn () => new MessengerManager('null'));
        Setting::set('messenger_bale_tariff', '50');

        $user = $this->approvedUser(walletBalance: 1_000);
        $groupId = $this->groupWith($user, ['09121112233', '09124445566']);

        $this->actingAs($user)->post(route('dashboard.messenger.send'), [
            'channel' => 'bale',
            'groups' => [$groupId],
            'message' => 'سلام',
        ])->assertRedirect(route('dashboard.messenger'));

        $campaign = MessengerCampaign::firstOrFail();
        $this->assertSame('failed', $campaign->status);   // null driver fails every recipient
        $this->assertSame(2, $campaign->failed_count);
        $this->assertSame(100, $campaign->refunded);
        $this->assertSame(1_000, $user->fresh()->walletBalance()); // debited 100, refunded 100

        $this->assertDatabaseHas('wallet_transactions', ['type' => 'messenger_send', 'direction' => 'debit', 'amount' => 100]);
        $this->assertDatabaseHas('wallet_transactions', ['type' => 'messenger_refund', 'direction' => 'credit', 'amount' => 100]);
    }

    public function test_insufficient_wallet_balance_blocks_the_send(): void
    {
        Setting::set('messenger_bale_tariff', '100000');

        $user = $this->approvedUser(walletBalance: 1_000);
        $groupId = $this->groupWith($user, ['09121112233']);

        $this->actingAs($user)->post(route('dashboard.messenger.send'), [
            'channel' => 'bale',
            'groups' => [$groupId],
            'message' => 'سلام',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('messenger_campaigns', 0);
    }

    public function test_send_requires_at_least_one_recipient(): void
    {
        $user = $this->approvedUser();

        $this->actingAs($user)->post(route('dashboard.messenger.send'), [
            'channel' => 'bale',
            'message' => 'سلام',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('messenger_campaigns', 0);
    }
}
