<?php

namespace App\Marketplace;

/**
 * Outcome of a {@see Contracts\SyncsContacts::pull()} run, for the flash message
 * on the app's page.
 */
class SyncResult
{
    public function __construct(
        public int $groups = 0,
        public int $created = 0,
        public int $updated = 0,
        public int $skipped = 0,
    ) {}

    public function summary(): string
    {
        return sprintf(
            '%s گروه، %s مخاطب جدید، %s به‌روزرسانی%s.',
            number_format($this->groups),
            number_format($this->created),
            number_format($this->updated),
            $this->skipped ? '، '.number_format($this->skipped).' نادیده' : '',
        );
    }
}
