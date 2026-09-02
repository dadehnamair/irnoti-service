<?php

namespace App\Marketplace\Handlers;

use App\Marketplace\Contracts\AppHandler;
use App\Marketplace\Contracts\SyncsContacts;
use App\Marketplace\Handlers\IrPlus\FakeIrPlusClient;
use App\Marketplace\Handlers\IrPlus\HttpIrPlusClient;
use App\Marketplace\Handlers\IrPlus\IrPlusClient;
use App\Marketplace\SyncResult;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\MarketplaceInstallation;
use Illuminate\Support\Facades\Validator;

/**
 * «ایرپلاس» — the reference external integration (docs/starter.md §13/§17). Given a
 * travel agency's API key it pulls their passenger list and groupings into a
 * dedicated phonebook scoped to the installation, ready for group SMS. The
 * `fake` driver ships sample data so the flow works with no real credentials.
 */
class IrPlusHandler implements AppHandler, SyncsContacts
{
    public function key(): string
    {
        return 'irplus';
    }

    public function validateConfig(array $config): array
    {
        return Validator::make($config, [
            'api_key' => ['required', 'string', 'min:8', 'max:255'],
            'agency_code' => ['required', 'string', 'max:100'],
            'base_url' => ['nullable', 'url', 'max:255'],
        ], [], [
            'api_key' => 'کلید API',
            'agency_code' => 'کد آژانس',
            'base_url' => 'آدرس سرویس',
        ])->validate();
    }

    public function onActivate(MarketplaceInstallation $installation): void
    {
        $group = ContactGroup::firstOrCreate(
            [
                'user_id' => $installation->user_id,
                'marketplace_installation_id' => $installation->id,
                'name' => 'ایرپلاس',
            ],
            [
                'source' => 'irplus',
                'description' => 'مخاطبان همگام‌شده از ایرپلاس',
                'sync_status' => 'local',
            ],
        );

        $installation->putSetting('group_id', $group->id);
    }

    public function onDeactivate(MarketplaceInstallation $installation): void
    {
        // Contacts / groups are kept (the customer may still want them); they
        // just stop being refreshed. FK is nullOnDelete so nothing breaks.
    }

    public function panelView(MarketplaceInstallation $installation): ?string
    {
        return 'dashboard.marketplace.handlers.irplus';
    }

    public function remoteGroups(MarketplaceInstallation $installation): array
    {
        return array_map(fn (array $g) => [
            'external_id' => $g['external_id'],
            'name' => $g['name'],
            'count' => $g['count'] ?? 0,
        ], $this->client($installation)->groups());
    }

    public function remoteContacts(MarketplaceInstallation $installation, ?string $groupExternalId = null): array
    {
        return array_map(fn (array $p) => [
            'first_name' => $p['first_name'] ?? null,
            'last_name' => $p['last_name'] ?? null,
            'mobile' => $p['mobile'],
            'group_external_ids' => $p['group_external_ids'] ?? [],
            'meta' => $p['meta'] ?? [],
        ], $this->client($installation)->passengers($groupExternalId));
    }

    public function pull(MarketplaceInstallation $installation): SyncResult
    {
        $client = $this->client($installation);
        $result = new SyncResult;

        $rootId = $installation->settingValue('group_id');
        if (! $rootId || ! ContactGroup::whereKey($rootId)->exists()) {
            $this->onActivate($installation);
            $rootId = $installation->settingValue('group_id');
        }

        // Remote group → local ContactGroup, scoped to this installation.
        $groupIdByExternal = [];

        foreach ($client->groups() as $remoteGroup) {
            $group = ContactGroup::updateOrCreate(
                [
                    'user_id' => $installation->user_id,
                    'marketplace_installation_id' => $installation->id,
                    'name' => $remoteGroup['name'] !== '' ? $remoteGroup['name'] : 'گروه ایرپلاس',
                ],
                [
                    'source' => 'irplus',
                    'sync_status' => 'local',
                ],
            );

            $groupIdByExternal[$remoteGroup['external_id']] = $group->id;
            $result->groups++;
        }

        foreach ($client->passengers() as $passenger) {
            $mobile = normalize_mobile($passenger['mobile']);

            if ($mobile === '') {
                $result->skipped++;

                continue;
            }

            $contact = Contact::updateOrCreate(
                [
                    'user_id' => $installation->user_id,
                    'marketplace_installation_id' => $installation->id,
                    'mobile' => $mobile,
                ],
                [
                    'first_name' => $passenger['first_name'] ?? null,
                    'last_name' => $passenger['last_name'] ?? null,
                    'source' => 'irplus',
                    'description' => $this->describe($passenger['meta'] ?? []),
                    'sync_status' => 'local',
                ],
            );

            $contact->wasRecentlyCreated ? $result->created++ : $result->updated++;

            $groupIds = array_values(array_filter(array_map(
                fn (string $ext) => $groupIdByExternal[$ext] ?? null,
                $passenger['group_external_ids'] ?? [],
            )));
            $groupIds[] = (int) $rootId;

            $contact->groups()->syncWithoutDetaching(array_unique($groupIds));
        }

        foreach (array_merge([$rootId], array_values($groupIdByExternal)) as $groupId) {
            if ($group = ContactGroup::find($groupId)) {
                $group->forceFill([
                    'contact_count' => $group->contacts()->count(),
                    'contacts_synced_at' => now(),
                ])->save();
            }
        }

        $installation->forceFill(['last_synced_at' => now()])->save();

        return $result;
    }

    private function client(MarketplaceInstallation $installation): IrPlusClient
    {
        $config = (array) $installation->config;

        return match ((string) config('marketplace.irplus.driver', 'fake')) {
            'http' => new HttpIrPlusClient($config),
            default => new FakeIrPlusClient($config),
        };
    }

    /** @param array<string, mixed> $meta */
    private function describe(array $meta): ?string
    {
        $parts = [];

        if (! empty($meta['passport'])) {
            $parts[] = 'گذرنامه: '.$meta['passport'];
        }

        if (! empty($meta['national_code'])) {
            $parts[] = 'کد ملی: '.$meta['national_code'];
        }

        return $parts === [] ? null : implode(' — ', $parts);
    }
}
