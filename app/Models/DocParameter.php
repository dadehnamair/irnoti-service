<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocParameter extends Model
{
    protected $fillable = [
        'doc_article_id',
        'name',
        'type',
        'is_required',
        'description',
        'example',
        'sort',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'sort' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(DocArticle::class, 'doc_article_id');
    }
}
