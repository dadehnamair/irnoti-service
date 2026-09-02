<?php

namespace App\Jobs;

use App\Models\User;
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
 * Pull a customer's whole Melipayamak phonebook into our tables
 * (docs/starter.md §17). Queued because a large book is hundreds of paged
 * GetContacts calls — far too slow for a web request. The per-user cache lock
 * set by the controller is released when this finishes.
 */
class ImportPhonebookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(public int $userId) {}

    public function handle(PhonebookSync $sync): void
    {
        $user = User::find($this->userId);

        if (! $user || ! $user->hasSmsPanel()) {
            return;
        }

        try {
            $result = $sync->import($user);

            Log::info('[phonebook] import finished', [
                'user' => $user->id,
                'groups' => $result['groups'],
                'contacts' => $result['contacts'],
            ]);
        } finally {
            Cache::forget($this->lockKey($this->userId));
        }
    }

    public function failed(Throwable $e): void
    {
        Cache::forget($this->lockKey($this->userId));

        Log::error('[phonebook] import job failed', ['user' => $this->userId, 'error' => $e->getMessage()]);
    }

    public static function lockKey(int $userId): string
    {
        return "phonebook_import:{$userId}";
    }
}
