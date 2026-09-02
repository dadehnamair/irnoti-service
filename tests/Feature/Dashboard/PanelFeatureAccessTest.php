<?php

namespace Tests\Feature\Dashboard;

use App\Models\Feature;
use App\Models\User;
use App\Models\UserGroup;
use Database\Seeders\FeaturesSeeder;
use Database\Seeders\UserGroupsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Panel-feature access: the built-in («is_system») pages are always available,
 * while the «بزودی» catalogue items are gated by the access group + per-user
 * overrides + the global toggle (docs/starter.md §15).
 */
class PanelFeatureAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeaturesSeeder::class);
        $this->seed(UserGroupsSeeder::class);
    }

    public function test_new_user_lands_in_the_default_group(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserGroup::defaultId(), $user->user_group_id);
    }

    public function test_system_pages_are_usable_without_any_group(): void
    {
        $user = User::factory()->create(['user_group_id' => null]);

        $this->assertTrue($user->canUseFeature('account.summary'));
        $this->assertTrue($user->canUseFeature('finance.wallet'));
        $this->assertFalse($user->canUseFeature('sms.gradual'));
    }

    public function test_granted_keys_combine_group_and_overrides(): void
    {
        $group = UserGroup::where('slug', 'default')->first();
        $targeted = Feature::where('key', 'sms.targeted')->first();
        $inbox = Feature::where('key', 'messages.inbox')->first();
        $sent = Feature::where('key', 'messages.sent')->first();

        $group->features()->sync([$targeted->id, $sent->id]);

        $user = User::factory()->create(['user_group_id' => $group->id]);
        // Per-user: add one the group lacks, drop one the group has.
        $user->featureOverrides()->create(['feature_id' => $inbox->id, 'mode' => 'grant']);
        $user->featureOverrides()->create(['feature_id' => $sent->id, 'mode' => 'revoke']);

        $keys = $user->fresh()->grantedFeatureKeys();

        $this->assertContains('sms.targeted', $keys);
        $this->assertContains('messages.inbox', $keys);
        $this->assertNotContains('messages.sent', $keys);
    }

    public function test_can_use_feature_requires_the_global_toggle(): void
    {
        $group = UserGroup::where('slug', 'default')->first();
        $feature = Feature::where('key', 'messages.inbox')->first();
        $group->features()->sync([$feature->id]);

        $user = User::factory()->create(['user_group_id' => $group->id]);

        $this->assertFalse($user->canUseFeature('messages.inbox'), 'disabled feature is not usable');

        $feature->update(['is_active' => true]);

        $this->assertTrue($user->fresh()->canUseFeature('messages.inbox'));
    }

    public function test_sidebar_shows_soon_badge_until_a_feature_is_switched_on(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('بزودی');
        $response->assertSee('ارسال تدریجی');           // a «بزودی» catalogue item
        $response->assertSee(route('dashboard.wallet'), false); // a built-in page renders as a link
    }

    public function test_switched_on_and_granted_feature_becomes_a_real_link(): void
    {
        $group = UserGroup::where('slug', 'default')->first();
        $feature = Feature::where('key', 'messages.inbox')->first();
        $feature->update(['is_active' => true, 'route' => 'dashboard']);
        $group->features()->sync([$feature->id]);

        $user = User::factory()->create(['status' => 'active', 'user_group_id' => $group->id]);

        $this->assertTrue($user->canUseFeature('messages.inbox'));

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('<a href="'.route('dashboard').'"', false);
        $response->assertSee('دریافتی');
    }
}
