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
 * Panel-feature access: the built-in («is_system») pages are always available;
 * every other catalogue item is hidden from the sidebar unless the account has
 * it (access group + per-user overrides), and once it does it shows «بزودی»
 * until the global toggle switches it on (docs/starter.md §15).
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
        $gradual = Feature::where('key', 'sms.gradual')->first();
        $smart = Feature::where('key', 'sms.smart')->first();

        $group->features()->sync([$targeted->id, $smart->id]);

        $user = User::factory()->create(['user_group_id' => $group->id]);
        // Per-user: add one the group lacks, drop one the group has.
        $user->featureOverrides()->create(['feature_id' => $gradual->id, 'mode' => 'grant']);
        $user->featureOverrides()->create(['feature_id' => $smart->id, 'mode' => 'revoke']);

        $keys = $user->fresh()->grantedFeatureKeys();

        $this->assertContains('sms.targeted', $keys);
        $this->assertContains('sms.gradual', $keys);
        $this->assertNotContains('sms.smart', $keys);
    }

    public function test_can_use_feature_requires_the_global_toggle(): void
    {
        $group = UserGroup::where('slug', 'default')->first();
        $feature = Feature::where('key', 'sms.gradual')->first();
        $group->features()->sync([$feature->id]);

        $user = User::factory()->create(['user_group_id' => $group->id]);

        $this->assertFalse($user->canUseFeature('sms.gradual'), 'disabled feature is not usable');

        $feature->update(['is_active' => true]);

        $this->assertTrue($user->fresh()->canUseFeature('sms.gradual'));
    }

    public function test_ungranted_catalogue_items_are_hidden_until_granted(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        // Nothing granted: the sidebar shows only the built-in pages — no «بزودی» stubs.
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('ارسال تدریجی');               // ungranted catalogue item — hidden entirely
        $response->assertSee(route('dashboard.wallet'), false); // a built-in page still renders as a link

        // Grant it through the group but leave it switched off → now visible as «بزودی».
        UserGroup::where('slug', 'default')->first()
            ->features()->sync([Feature::where('key', 'sms.gradual')->first()->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('ارسال تدریجی');
        $response->assertSee('بزودی');
    }

    public function test_switched_on_and_granted_feature_becomes_a_real_link(): void
    {
        $group = UserGroup::where('slug', 'default')->first();
        $feature = Feature::where('key', 'sms.gradual')->first();
        $feature->update(['is_active' => true, 'route' => 'dashboard']);
        $group->features()->sync([$feature->id]);

        $user = User::factory()->create(['status' => 'active', 'user_group_id' => $group->id]);

        $this->assertTrue($user->canUseFeature('sms.gradual'));

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('<a href="'.route('dashboard').'"', false);
        $response->assertSee('ارسال تدریجی');
    }
}
