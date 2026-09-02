<?php

namespace App\Services\Sms\Phonebook;

/**
 * Does nothing. Bound in the test environment (SMS_PROVIDER=null) so the suite
 * never touches the phonebook web service. Mirrors
 * {@see \App\Services\Sms\NullProvider}.
 */
class NullPhonebookClient implements PhonebookClientInterface
{
    public function groups(): array
    {
        return [];
    }

    public function contacts(?int $groupId = null, ?string $keyword = null, int $from = 0, int $count = 200): array
    {
        return [];
    }

    public function createGroup(string $name, ?string $description, bool $showToChild): bool
    {
        return true;
    }

    public function createContact(array $data): bool
    {
        return true;
    }

    public function updateContact(int $remoteId, array $data): bool
    {
        return true;
    }

    public function deactivateContact(int $remoteId): bool
    {
        return true;
    }

    public function checkMobile(string $mobile): int
    {
        return 0;
    }

    public function sendToGroups(array $remoteGroupIds, string $message, ?string $from = null, ?string $title = null, ?string $dateToSend = null): string
    {
        return '';
    }
}
