<?php

namespace App\Services\Messenger;

use RuntimeException;

/**
 * The requested messenger channel has no usable configuration (missing token /
 * unknown key). A config problem, not a transient one — SendMessengerCampaignJob
 * treats it as terminal and does not burn retries. Mirrors
 * App\Services\Sms\SmsPanelNotConfiguredException.
 */
class MessengerChannelNotConfiguredException extends RuntimeException
{
    public function __construct(string $message = 'کانال پیام‌رسان پیکربندی نشده است.')
    {
        parent::__construct($message);
    }
}
