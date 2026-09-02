<?php

namespace App\Services\Sms;

use RuntimeException;

/**
 * Thrown when a customer tries to use SMS features before the admin has wired
 * their own Melipayamak panel credentials (docs/starter.md §12).
 */
class SmsPanelNotConfiguredException extends RuntimeException
{
    public function __construct(string $message = 'پنل پیامک شما هنوز فعال نشده است.')
    {
        parent::__construct($message);
    }
}
