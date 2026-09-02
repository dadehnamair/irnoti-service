<?php

namespace App\Jobs;

use App\Models\ContactGroup;
use App\Support\PhonebookSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pull one phonebook group's contacts from the SMS provider into our tables
 * (docs/starter.md §17). Queued because a big group is many paged GetContacts
 * calls. The per-group cache lock set by the controller is released here.
 */
class ImportGroupContactsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(public int $groupId) {}

    public function handle(PhonebookSync $sync): void
    {
        $group = ContactGroup::with('user')->find($this->groupId);

        try {
            if ($group) {
                $result = $sync->importGroupContacts($group);

                Log::info('[phonebook] group contacts imported', [
                    'group' => $group->id,
                    'contacts' => $result['contacts'],
                ]);
            }
        } finally {
            Cache::forget(self::lockKey($this->groupId));
        }
    }

    public function failed(Throwable $e): void
    {
        Cache::forget(self::lockKey($this->groupId));

        Log::error('[phonebook] group contacts import failed', ['group' => $this->groupId, 'error' => $e->getMessage()]);
    }

    public static function lockKey(int $groupId): string
    {
        return "phonebook_group_pull:{$groupId}";
    }
}
