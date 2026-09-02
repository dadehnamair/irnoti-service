<?php

namespace App\Services\Sms;

use App\Models\User;

/**
 * Builds an {@see SmsManager} bound to a single customer's own SMS panel
 * credentials (docs/starter.md §12). Used by the customer panel for the single
 * send and the credit read — never for app notifications, which use the shared
 * provider binding.
 */
class UserSmsGateway
{
    public static function for(User $user): SmsManager
    {
        if (! $user->hasSmsPanel()) {
            throw new SmsPanelNotConfiguredException;
        }

        $provider = new PasargadProvider([
            'username' => $user->sms_username,
            'password' => $user->sms_password,
            'sender' => $user->sms_sender,
        ]);

        return new SmsManager($provider, $user->sms_sender);
    }
}
