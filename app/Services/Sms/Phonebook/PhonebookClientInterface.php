<?php

namespace App\Services\Sms\Phonebook;

use App\Services\Sms\SmsProviderInterface;

/**
 * Phonebook / contacts layer (docs/starter.md §14 / §17). Parallels
 * {@see SmsProviderInterface}: no controller talks to the
 * vendor phonebook API directly. The Melipayamak implementation speaks the
 * Contacts.asmx web service; per-customer instances are built by
 * {@see UserPhonebook}.
 */
interface PhonebookClientInterface
{
    /**
     * Every group on the account.
     *
     * @return array<int, array{remote_id:int, name:string, description:?string, parent_id:?int, contact_count:int, show_to_child:bool}>
     */
    public function groups(): array;

    /**
     * A page of contacts, optionally filtered by group and/or a keyword
     * (name or mobile). Melipayamak caps `count` at 100.
     *
     * @return array<int, array{remote_id:int, first_name:?string, last_name:?string, mobile:string, email:?string, company:?string, nickname:?string, gender:?string, birth_date:?string, description:?string, group_names:array<int,string>}>
     */
    public function contacts(?int $groupId = null, ?string $keyword = null, int $from = 0, int $count = 100): array;

    /** Create a group. Returns true on success (Melipayamak gives no new id here). */
    public function createGroup(string $name, ?string $description, bool $showToChild): bool;

    /**
     * Create a contact. `$data` is already Melipayamak-shaped
     * (groupIds, firstname, lastname, nickname, corporation, mobilenumber,
     * gender, birthdate, email, descriptions). Returns true on success.
     *
     * @param  array<string, mixed>  $data
     */
    public function createContact(array $data): bool;

    /**
     * Update a contact by its Melipayamak ContactID. `$data` is Melipayamak-shaped
     * and may include `contactStatus` (0 active / 1 inactive / -1 unchanged).
     *
     * @param  array<string, mixed>  $data
     */
    public function updateContact(int $remoteId, array $data): bool;

    /** Set a contact inactive on Melipayamak — the closest thing to a delete. */
    public function deactivateContact(int $remoteId): bool;

    /** -1 bad credentials · 0 not in phonebook · 1 in phonebook. */
    public function checkMobile(string $mobile): int;

    /**
     * Bulk-send to whole phonebook groups (newbulks.asmx/SendSmsToContact).
     * Returns the bulkId string, or throws on a gateway error.
     *
     * @param  array<int, int|string>  $remoteGroupIds  up to 5
     */
    public function sendToGroups(array $remoteGroupIds, string $message, ?string $from = null, ?string $title = null, ?string $dateToSend = null): string;
}
