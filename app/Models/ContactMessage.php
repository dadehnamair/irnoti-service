<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A submission from the public /contact form. Born on the public site;
 * only ever reviewed (never created) from the Filament admin panel.
 */
class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'mobile',
        'email',
        'subject',
        'message',
        'status',
        'admin_note',
    ];

    /** Persian labels for the `status` column. */
    public const STATUSES = [
        'new' => 'جدید',
        'read' => 'مشاهده‌شده',
        'replied' => 'پاسخ داده‌شده',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
