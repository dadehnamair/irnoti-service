<?php

namespace App\Services\Sms\Phonebook;

use Illuminate\Support\Facades\Log;

/**
 * Credential-free phonebook driver for local dev & staging (SMS_PROVIDER=log).
 * Writes each call to the log instead of hitting the web service, and returns
 * plausible values so the panel UI is fully usable without a real Melipayamak
 * account. Mirrors {@see \App\Services\Sms\LogProvider}. Never use in production.
 */
class LogPhonebookClient implements PhonebookClientInterface
{
    public function groups(): array
    {
        $this->log('groups', []);

        return [];
    }

    public function contacts(?int $groupId = null, ?string $keyword = null, int $from = 0, int $count = 200): array
    {
        $this->log('contacts', compact('groupId', 'keyword', 'from', 'count'));

        return [];
    }

    public function createGroup(string $name, ?string $description, bool $showToChild): bool
    {
        $this->log('createGroup', compact('name', 'description', 'showToChild'));

        return true;
    }

    public function createContact(array $data): bool
    {
        $this->log('createContact', $data);

        return true;
    }

    public function updateContact(int $remoteId, array $data): bool
    {
        $this->log('updateContact', ['remoteId' => $remoteId] + $data);

        return true;
    }

    public function deactivateContact(int $remoteId): bool
    {
        $this->log('deactivateContact', ['remoteId' => $remoteId]);

        return true;
    }

    public function checkMobile(string $mobile): int
    {
        $this->log('checkMobile', compact('mobile'));

        return 0;
    }

    public function sendToGroups(array $remoteGroupIds, string $message, ?string $from = null, ?string $title = null, ?string $dateToSend = null): string
    {
        $this->log('sendToGroups', compact('remoteGroupIds', 'message', 'from', 'title', 'dateToSend'));

        return (string) random_int(100000, 999999);
    }

    /** @param  array<string, mixed>  $context */
    private function log(string $op, array $context): void
    {
        Log::channel(config('logging.default'))->info('[sms:phonebook:log] '.$op, $context);
    }
}
