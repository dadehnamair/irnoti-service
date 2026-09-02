<?php

namespace App\Marketplace\Handlers\IrPlus;

use App\Services\Sms\Phonebook\UserPhonebook;

/**
 * Talks to one travel agency's ایرپلاس account (docs/starter.md §13/§17). Built
 * per-installation from its stored credentials — mirrors
 * {@see UserPhonebook}. `FakeIrPlusClient` is the
 * credential-free dev/test driver; `HttpIrPlusClient` is the real REST client.
 */
interface IrPlusClient
{
    /**
     * The agency's passenger groupings.
     *
     * @return array<int, array{external_id: string, name: string, count: int}>
     */
    public function groups(): array;

    /**
     * The agency's passengers, optionally filtered to one group.
     *
     * @return array<int, array{first_name: ?string, last_name: ?string, mobile: string, group_external_ids: array<int, string>, meta: array<string, mixed>}>
     */
    public function passengers(?string $groupExternalId = null): array;
}
