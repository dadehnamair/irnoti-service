<?php

namespace App\Services\Sms\Phonebook;

use App\Models\User;
use App\Services\Sms\SmsPanelNotConfiguredException;

/**
 * Builds a {@see PhonebookClientInterface} bound to a single customer's own
 * SMS panel credentials (docs/starter.md §17). Mirrors
 * {@see \App\Services\Sms\UserSmsGateway}.
 */
class UserPhonebook
{
    public static function for(User $user): PhonebookClientInterface
    {
        if (! $user->hasSmsPanel()) {
            throw new SmsPanelNotConfiguredException;
        }

        return new PasargadPhonebookClient([
            'username' => $user->sms_username,
            'password' => $user->sms_password,
            'sender' => $user->sms_sender,
        ]);
    }
}
