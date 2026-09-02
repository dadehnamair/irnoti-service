<?php

namespace Tests\Feature\Dashboard;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Two-way mirror between the local phonebook and the customer's own Melipayamak
 * panel (docs/starter.md §17). The gateway is faked; the local write always
 * stands and `sync_status` records the mirror.
 */
class ContactSyncTest extends TestCase
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

    private function groupsXml(array $groups): string
    {
        $items = '';
        foreach ($groups as $g) {
            $items .= '<GroupsList>'
                .'<GroupID>'.$g['id'].'</GroupID>'
                .'<ParentId>0</ParentId>'
                .'<GroupName>'.$g['name'].'</GroupName>'
                .'<GroupDescription></GroupDescription>'
                .'<ContactCount>'.($g['count'] ?? 0).'</ContactCount>'
                .'<ShowToChild>false</ShowToChild>'
                .'</GroupsList>';
        }

        return '<?xml version="1.0" encoding="utf-8"?><ArrayOfGroupsList xmlns="http://tempuri.org/">'.$items.'</ArrayOfGroupsList>';
    }

    private function contactsXml(array $contacts): string
    {
        $items = '';
        foreach ($contacts as $c) {
            $items .= '<ContactsGridList>'
                .'<ContactID>'.$c['id'].'</ContactID>'
                .'<FirstName>'.($c['first'] ?? '').'</FirstName>'
                .'<LastName>'.($c['last'] ?? '').'</LastName>'
                .'<NickName></NickName><Corporation></Corporation>'
                .'<MobileNumbers>'.$c['mobile'].'</MobileNumbers>'
                .'<Email></Email><Gender>0</Gender><BirthDate>0001-01-01T00:00:00</BirthDate>'
                .'<Descriptions></Descriptions>'
                .'<Groups>'.($c['groups'] ?? '').'</Groups>'
                .'</ContactsGridList>';
        }

        return '<?xml version="1.0" encoding="utf-8"?><GetContactsResponse xmlns="http://tempuri.org/"><GetContactsResult>'.$items.'</GetContactsResult></GetContactsResponse>';
    }

    public function test_creating_a_group_pushes_to_melipayamak_and_captures_the_id(): void
    {
        Http::fake([
            'api.payamak-panel.com/post/Contacts.asmx/AddGroup' => Http::response('<int xmlns="http://tempuri.org/">1</int>'),
            'api.payamak-panel.com/post/Contacts.asmx/GetGroups' => Http::response($this->groupsXml([
                ['id' => 55, 'name' => 'مشتریان طلایی'],
            ])),
            'api.payamak-panel.com/*' => Http::response('<int>0</int>'),
        ]);

        $user = $this->panelUser();

        $this->actingAs($user)->post(route('dashboard.contacts.groups.store'), ['name' => 'مشتریان طلایی']);

        $this->assertDatabaseHas('contact_groups', [
            'user_id' => $user->id,
            'name' => 'مشتریان طلایی',
            'remote_id' => 55,
            'sync_status' => 'synced',
        ]);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'Contacts.asmx/AddGroup'));
    }

    public function test_group_sync_failure_is_recorded_but_the_local_row_stands(): void
    {
        Http::fake([
            'api.payamak-panel.com/post/Contacts.asmx/AddGroup' => Http::response('<int xmlns="http://tempuri.org/">0</int>'),
            'api.payamak-panel.com/*' => Http::response('<int>0</int>'),
        ]);

        $user = $this->panelUser();

        $this->actingAs($user)->post(route('dashboard.contacts.groups.store'), ['name' => 'گروه ناموفق']);

        $this->assertDatabaseHas('contact_groups', [
            'user_id' => $user->id,
            'name' => 'گروه ناموفق',
            'remote_id' => null,
            'sync_status' => 'error',
        ]);
    }

    public function test_creating_a_contact_in_a_synced_group_pushes_and_captures_the_id(): void
    {
        Http::fake([
            'api.payamak-panel.com/post/Contacts.asmx/AddContact' => Http::response('<int xmlns="http://tempuri.org/">1</int>'),
            'api.payamak-panel.com/post/Contacts.asmx/GetContacts' => Http::response($this->contactsXml([
                ['id' => 900, 'mobile' => '09121234567'],
            ])),
            'api.payamak-panel.com/*' => Http::response('<int>0</int>'),
        ]);

        $user = $this->panelUser();
        $group = $user->contactGroups()->create([
            'name' => 'همکاران', 'remote_id' => 55, 'sync_status' => 'synced', 'synced_at' => now(),
        ]);

        $this->actingAs($user)->post(route('dashboard.contacts.store'), [
            'first_name' => 'سارا',
            'mobile' => '09121234567',
            'groups' => [$group->id],
        ]);

        $contact = Contact::firstWhere('user_id', $user->id);
        $this->assertNotNull($contact);
        $this->assertSame(900, (int) $contact->remote_id);
        $this->assertSame('synced', $contact->sync_status);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'Contacts.asmx/AddContact'));
    }

    public function test_contact_without_a_synced_group_stays_local(): void
    {
        Http::fake(['api.payamak-panel.com/*' => Http::response('<int>0</int>')]);

        $user = $this->panelUser();
        $group = $user->contactGroups()->create(['name' => 'محلی']); // not synced

        $this->actingAs($user)->post(route('dashboard.contacts.store'), [
            'mobile' => '09120000000',
            'groups' => [$group->id],
        ]);

        $this->assertDatabaseHas('contacts', [
            'user_id' => $user->id,
            'mobile' => '09120000000',
            'sync_status' => 'local',
        ]);
    }

    public function test_importing_groups_pulls_only_the_group_list(): void
    {
        Http::fake([
            'api.payamak-panel.com/post/Contacts.asmx/GetGroups' => Http::response($this->groupsXml([
                ['id' => 55, 'name' => 'وارداتی', 'count' => 12],
                ['id' => 56, 'name' => 'صادراتی', 'count' => 4],
            ])),
            'api.payamak-panel.com/*' => Http::response('<int>0</int>'),
        ]);

        $user = $this->panelUser();

        $this->actingAs($user)->post(route('dashboard.contacts.import'))
            ->assertRedirect(route('dashboard.contacts.groups'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('contact_groups', ['user_id' => $user->id, 'remote_id' => 55, 'sync_status' => 'synced']);
        $this->assertDatabaseHas('contact_groups', ['user_id' => $user->id, 'remote_id' => 56]);
        $this->assertSame(0, Contact::where('user_id', $user->id)->count());
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'Contacts.asmx/GetContacts'));
    }

    public function test_pulling_a_group_imports_its_contacts(): void
    {
        Http::fake([
            'api.payamak-panel.com/post/Contacts.asmx/GetContacts' => Http::response($this->contactsXml([
                ['id' => 900, 'mobile' => '09129998877', 'first' => 'مهمان', 'groups' => 'وارداتی'],
                ['id' => 901, 'mobile' => '09121112233', 'first' => 'دوم', 'groups' => 'وارداتی'],
            ])),
            'api.payamak-panel.com/*' => Http::response('<int>0</int>'),
        ]);

        $user = $this->panelUser();
        $group = $user->contactGroups()->create([
            'name' => 'وارداتی', 'remote_id' => 55, 'sync_status' => 'synced', 'synced_at' => now(),
        ]);

        $this->actingAs($user)->post(route('dashboard.contacts.groups.pull', $group))
            ->assertRedirect(route('dashboard.contacts.groups'))
            ->assertSessionHas('status');

        $group->refresh();
        $this->assertNotNull($group->contacts_synced_at);
        $this->assertSame(2, $group->contacts()->count());

        $contact = Contact::firstWhere(['user_id' => $user->id, 'remote_id' => 900]);
        $this->assertNotNull($contact);
        $this->assertSame('09129998877', $contact->mobile);
        $this->assertTrue($contact->groups->contains($group));
    }

    public function test_pulling_an_unsynced_group_is_rejected(): void
    {
        Http::fake(['api.payamak-panel.com/*' => Http::response('<int>0</int>')]);

        $user = $this->panelUser();
        $group = $user->contactGroups()->create(['name' => 'محلی']); // no remote_id

        $this->actingAs($user)->post(route('dashboard.contacts.groups.pull', $group))
            ->assertRedirect(route('dashboard.contacts.groups'))
            ->assertSessionHas('warning');

        $this->assertSame(0, $group->contacts()->count());
    }
}
