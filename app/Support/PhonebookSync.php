<?php

namespace App\Support;

use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\User;
use App\Services\Sms\Phonebook\PhonebookClientInterface;
use App\Services\Sms\Phonebook\UserPhonebook;
use Illuminate\Support\Facades\Log;

/**
 * Keeps a customer's local phonebook (contacts / contact_groups) mirrored to
 * their own SMS panel (docs/starter.md §17). Every method is best-effort: a
 * gateway failure is recorded on the row (`sync_status='error'`, `sync_error`)
 * and swallowed so the local write always stands. When the customer has no SMS
 * panel the phonebook stays local-only.
 *
 * Known provider limitations (no endpoint exists for these):
 *  - a group cannot be renamed or deleted remotely;
 *  - a contact's group membership can only be set when it is first created.
 */
class PhonebookSync
{
    /** Push a new group to the SMS provider and capture its GroupID. */
    public function pushGroup(ContactGroup $group): void
    {
        $user = $group->user;

        if (! $user || ! $user->hasSmsPanel()) {
            $group->forceFill(['sync_status' => 'local', 'sync_error' => null])->save();

            return;
        }

        // AddGroup only creates — a synced group cannot be renamed remotely.
        if ($group->remote_id) {
            return;
        }

        try {
            $client = UserPhonebook::for($user);

            if (! $client->createGroup($group->name, $group->description, (bool) $group->show_to_child)) {
                throw new \RuntimeException(sms_provider_label().' ساخت گروه را نپذیرفت.');
            }

            $remoteId = $this->matchRemoteGroupId($client, $user, $group->name);

            $group->forceFill([
                'remote_id' => $remoteId,
                'sync_status' => $remoteId ? 'synced' : 'error',
                'sync_error' => $remoteId ? null : 'گروه در '.sms_provider_label().' ساخته شد اما شناسهٔ آن یافت نشد.',
                'synced_at' => now(),
            ])->save();
        } catch (\Throwable $e) {
            $this->fail($group, $e);
        }
    }

    /** Create or update a contact on the SMS provider. */
    public function pushContact(Contact $contact): void
    {
        $user = $contact->user;

        if (! $user || ! $user->hasSmsPanel()) {
            $contact->forceFill(['sync_status' => 'local', 'sync_error' => null])->save();

            return;
        }

        try {
            $client = UserPhonebook::for($user);
            $payload = $this->contactPayload($contact);

            if ($contact->remote_id) {
                if (! $client->updateContact((int) $contact->remote_id, $payload + ['contactStatus' => 0])) {
                    throw new \RuntimeException(sms_provider_label().' ویرایش مخاطب را نپذیرفت.');
                }

                $contact->forceFill(['sync_status' => 'synced', 'sync_error' => null, 'synced_at' => now()])->save();

                return;
            }

            $remoteGroupIds = $contact->groups
                ->pluck('remote_id')
                ->filter()
                ->values();

            if ($remoteGroupIds->isEmpty()) {
                $contact->forceFill([
                    'sync_status' => 'local',
                    'sync_error' => 'برای همگام‌سازی، مخاطب باید حداقل در یک گروهِ همگام‌شده باشد.',
                ])->save();

                return;
            }

            if (! $client->createContact($payload + ['groupIds' => $remoteGroupIds->implode(',')])) {
                throw new \RuntimeException(sms_provider_label().' ثبت مخاطب را نپذیرفت.');
            }

            $remoteId = $this->matchRemoteContactId($client, $contact->mobile, $remoteGroupIds->all());

            $contact->forceFill([
                'remote_id' => $remoteId,
                'sync_status' => $remoteId ? 'synced' : 'error',
                'sync_error' => $remoteId ? null : 'مخاطب در '.sms_provider_label().' ثبت شد اما شناسهٔ آن یافت نشد.',
                'synced_at' => now(),
            ])->save();
        } catch (\Throwable $e) {
            $this->fail($contact, $e);
        }
    }

    /** Set the contact inactive on the SMS provider just before it is deleted locally. */
    public function deleteContactRemote(Contact $contact): void
    {
        $user = $contact->user;

        if (! $user || ! $user->hasSmsPanel() || ! $contact->remote_id) {
            return;
        }

        try {
            UserPhonebook::for($user)->deactivateContact((int) $contact->remote_id);
        } catch (\Throwable $e) {
            Log::warning('[phonebook] remote deactivate failed', [
                'contact' => $contact->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Pull only the group list from the SMS provider (fast — one call). Contacts are
     * fetched later, per group, by {@see importGroupContacts()}. Returns the
     * number of groups upserted.
     */
    public function importGroups(User $user): int
    {
        $count = 0;

        foreach (UserPhonebook::for($user)->groups() as $g) {
            ContactGroup::updateOrCreate(
                ['user_id' => $user->id, 'remote_id' => $g['remote_id']],
                [
                    'name' => $g['name'] !== '' ? $g['name'] : 'گروه '.$g['remote_id'],
                    'description' => $g['description'],
                    'show_to_child' => $g['show_to_child'],
                    'contact_count' => $g['contact_count'],
                    'sync_status' => 'synced',
                    'sync_error' => null,
                    'synced_at' => now(),
                ],
            );

            $count++;
        }

        return $count;
    }

    /**
     * Pull one group's contacts from the SMS provider (paged; `count` capped at 100).
     * Each contact is upserted and attached to this group plus any other local
     * group whose name appears in the remote `<Groups>` field. Returns
     * ['contacts' => n].
     *
     * @return array{contacts:int}
     */
    public function importGroupContacts(ContactGroup $group): array
    {
        $user = $group->user;

        if (! $user || ! $user->hasSmsPanel() || ! $group->remote_id) {
            return ['contacts' => 0];
        }

        $client = UserPhonebook::for($user);

        $groupByName = ContactGroup::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy(fn (ContactGroup $g) => mb_strtolower(trim($g->name)))
            ->map->id
            ->all();

        $seen = 0;
        $from = 0;
        $page = 100;

        do {
            $batch = $client->contacts((int) $group->remote_id, null, $from, $page);

            foreach ($batch as $c) {
                $contact = Contact::updateOrCreate(
                    ['user_id' => $user->id, 'remote_id' => $c['remote_id']],
                    [
                        'first_name' => $c['first_name'],
                        'last_name' => $c['last_name'],
                        'mobile' => $c['mobile'],
                        'email' => $c['email'],
                        'company' => $c['company'],
                        'nickname' => $c['nickname'],
                        'gender' => $c['gender'],
                        'birth_date' => $c['birth_date'],
                        'description' => $c['description'],
                        'sync_status' => 'synced',
                        'sync_error' => null,
                        'synced_at' => now(),
                    ],
                );

                $ids = [$group->id];

                foreach ($c['group_names'] as $name) {
                    $key = mb_strtolower(trim($name));

                    if (isset($groupByName[$key])) {
                        $ids[] = $groupByName[$key];
                    }
                }

                $contact->groups()->syncWithoutDetaching(array_values(array_unique($ids)));
                $seen++;
            }

            $from += $page;
        } while (count($batch) === $page && $from < 200000);

        $group->forceFill([
            'contacts_synced_at' => now(),
            'contact_count' => $group->contacts()->count(),
        ])->save();

        return ['contacts' => $seen];
    }

    /** Provider-shaped field map for AddContact / ChangeContact2. */
    private function contactPayload(Contact $contact): array
    {
        return [
            'firstname' => (string) $contact->first_name,
            'lastname' => (string) $contact->last_name,
            'nickname' => (string) $contact->nickname,
            'corporation' => (string) $contact->company,
            'mobilenumber' => (string) $contact->mobile,
            'email' => (string) $contact->email,
            'gender' => match ($contact->gender) {
                'female' => '1',
                'male' => '2',
                default => '',
            },
            'birthdate' => $contact->birth_date?->toDateString() ?? '',
            'descriptions' => (string) $contact->description,
        ];
    }

    private function matchRemoteGroupId(PhonebookClientInterface $client, User $user, string $name): ?int
    {
        $used = ContactGroup::query()
            ->where('user_id', $user->id)
            ->whereNotNull('remote_id')
            ->pluck('remote_id')
            ->all();

        foreach ($client->groups() as $g) {
            if (mb_strtolower(trim($g['name'])) === mb_strtolower(trim($name)) && ! in_array($g['remote_id'], $used, true)) {
                return $g['remote_id'];
            }
        }

        return null;
    }

    /**
     * Find the remote ContactID of a just-created contact. GetContacts needs
     * a real GroupId, so we search within each group the contact was added to
     * (plus a best-effort ungrouped lookup).
     *
     * @param  array<int, int|string>  $groupRemoteIds
     */
    private function matchRemoteContactId(PhonebookClientInterface $client, string $mobile, array $groupRemoteIds): ?int
    {
        $target = normalize_mobile($mobile);

        foreach (array_merge(array_map('intval', $groupRemoteIds), [null]) as $groupId) {
            foreach ($client->contacts($groupId, $mobile, 0, 50) as $c) {
                if (normalize_mobile($c['mobile']) === $target) {
                    return $c['remote_id'];
                }
            }
        }

        return null;
    }

    private function fail(Contact|ContactGroup $model, \Throwable $e): void
    {
        Log::warning('[phonebook] sync failed', [
            'model' => $model::class,
            'id' => $model->id,
            'error' => $e->getMessage(),
        ]);

        $model->forceFill([
            'sync_status' => 'error',
            'sync_error' => mb_substr($e->getMessage(), 0, 250),
        ])->save();
    }
}
