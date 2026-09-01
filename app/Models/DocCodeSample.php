<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocCodeSample extends Model
{
    /**
     * Languages offered for API code samples. Key = highlight.js language id,
     * value = human label shown on the docs tab.
     */
    public const LANGUAGES = [
        'curl' => 'cURL',
        'php' => 'PHP',
        'laravel' => 'Laravel',
        'javascript' => 'JavaScript',
        'python' => 'Python',
        'csharp' => 'C#',
        'java' => 'Java',
    ];

    protected $fillable = [
        'doc_article_id',
        'language',
        'label',
        'code',
        'sort',
    ];

    protected $casts = [
        'sort' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(DocArticle::class, 'doc_article_id');
    }

    public function getLanguageLabelAttribute(): string
    {
        return self::LANGUAGES[$this->language] ?? ucfirst($this->language);
    }

    public function getHighlightLanguageAttribute(): string
    {
        return $this->language === 'laravel' ? 'php' : $this->language;
    }
}
