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
 * their own Melipayamak panel (docs/starter.md §17). Every method is
 * best-effort: a gateway failure is recorded on the row (`sync_status='error'`,
 * `sync_error`) and swallowed so the local write always stands. When the
 * customer has no SMS panel the phonebook stays local-only.
 *
 * Known Melipayamak limitations (no endpoint exists for these):
 *  - a group cannot be renamed or deleted remotely;
 *  - a contact's group membership can only be set when it is first created.
 */
class PhonebookSync
{
    /** Push a new group to Melipayamak and capture its GroupID. */
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
                throw new \RuntimeException('ملی‌پیامک ساخت گروه را نپذیرفت.');
            }

            $remoteId = $this->matchRemoteGroupId($client, $user, $group->name);

            $group->forceFill([
                'remote_id' => $remoteId,
                'sync_status' => $remoteId ? 'synced' : 'error',
                'sync_error' => $remoteId ? null : 'گروه در ملی‌پیامک ساخته شد اما شناسهٔ آن یافت نشد.',
                'synced_at' => now(),
            ])->save();
        } catch (\Throwable $e) {
            $this->fail($group, $e);
        }
    }

    /** Create or update a contact on Melipayamak. */
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
                    throw new \RuntimeException('ملی‌پیامک ویرایش مخاطب را نپذیرفت.');
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
                throw new \RuntimeException('ملی‌پیامک ثبت مخاطب را نپذیرفت.');
            }

            $remoteId = $this->matchRemoteContactId($client, $contact->mobile);

            $contact->forceFill([
                'remote_id' => $remoteId,
                'sync_status' => $remoteId ? 'synced' : 'error',
                'sync_error' => $remoteId ? null : 'مخاطب در ملی‌پیامک ثبت شد اما شناسهٔ آن یافت نشد.',
                'synced_at' => now(),
            ])->save();
        } catch (\Throwable $e) {
            $this->fail($contact, $e);
        }
    }

    /** Set the contact inactive on Melipayamak just before it is deleted locally. */
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
     * Pull everything from Melipayamak into the local tables (upsert by
     * remote_id). Returns ['groups' => n, 'contacts' => n].
     *
     * @return array{groups:int, contacts:int}
     */
    public function import(User $user): array
    {
        $client = UserPhonebook::for($user);

        $groupMap = []; // remote GroupID => local ContactGroup id
        $groupCount = 0;

        foreach ($client->groups() as $g) {
            $group = ContactGroup::updateOrCreate(
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

            $groupMap[$g['remote_id']] = $group->id;
            $groupCount++;
        }

        $contactCount = 0;
        $from = 0;
        $page = 200;

        do {
            $batch = $client->contacts(null, null, $from, $page);

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

                $localGroupIds = array_values(array_filter(array_map(
                    fn ($remoteId) => $groupMap[$remoteId] ?? null,
                    $c['group_ids'],
                )));

                $contact->groups()->sync($localGroupIds);
                $contactCount++;
            }

            $from += $page;
        } while (count($batch) === $page && $from < 10000);

        return ['groups' => $groupCount, 'contacts' => $contactCount];
    }

    /** Melipayamak-shaped field map for AddContact / ChangeContact2. */
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

    private function matchRemoteContactId(PhonebookClientInterface $client, string $mobile): ?int
    {
        foreach ($client->contacts(null, $mobile, 0, 50) as $c) {
            if (normalize_mobile($c['mobile']) === normalize_mobile($mobile)) {
                return $c['remote_id'];
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
