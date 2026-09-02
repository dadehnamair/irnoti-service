<?php

namespace Tests\Feature\Dashboard;

use App\Models\ContactGroup;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Phonebook groups CRUD from the customer panel (docs/starter.md §17). Without an
 * SMS panel the group stays local-only.
 */
class ContactGroupTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'status' => 'active',
            'approved_at' => now(),
        ], $attributes));
    }

    public function test_customer_creates_a_local_group(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('dashboard.contacts.groups.store'), [
            'name' => 'مشتریان ویژه',
            'description' => 'گروه تست',
        ])->assertRedirect(route('dashboard.contacts.groups'));

        $this->assertDatabaseHas('contact_groups', [
            'user_id' => $user->id,
            'name' => 'مشتریان ویژه',
            'sync_status' => 'local',
            'remote_id' => null,
        ]);
    }

    public function test_customer_updates_and_deletes_a_group(): void
    {
        $user = $this->user();
        $group = $user->contactGroups()->create(['name' => 'قدیمی']);

        $this->actingAs($user)->put(route('dashboard.contacts.groups.update', $group), [
            'name' => 'جدید',
        ])->assertRedirect(route('dashboard.contacts.groups'));

        $this->assertSame('جدید', $group->fresh()->name);

        $this->actingAs($user)->delete(route('dashboard.contacts.groups.destroy', $group))
            ->assertRedirect(route('dashboard.contacts.groups'));

        $this->assertDatabaseMissing('contact_groups', ['id' => $group->id]);
    }

    public function test_group_actions_are_scoped_to_the_owner(): void
    {
        $group = $this->user()->contactGroups()->create(['name' => 'مال دیگری']);
        $intruder = $this->user();

        $this->actingAs($intruder)->put(route('dashboard.contacts.groups.update', $group), ['name' => 'x'])
            ->assertForbidden();
        $this->actingAs($intruder)->delete(route('dashboard.contacts.groups.destroy', $group))
            ->assertForbidden();
    }

    public function test_phonebook_can_be_disabled_by_setting(): void
    {
        Setting::updateOrCreate(['key' => 'phonebook_enabled'], [
            'value' => '0', 'type' => 'bool', 'group' => 'account',
        ]);
        Cache::forget(Setting::CACHE_KEY);

        $this->actingAs($this->user())->get(route('dashboard.contacts'))->assertNotFound();
    }
}
