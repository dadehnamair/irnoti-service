<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Editable "base info" for the public site. Read it through {@see Setting::get()}
 * (cached) — or, for the whole map ready to overlay on config, {@see Setting::map()}.
 */
class Setting extends Model
{
    public const CACHE_KEY = 'settings.map';

    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'sort'];

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget(self::CACHE_KEY);

        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * All settings as a `key => typed value` map. Cached forever and rebuilt
     * whenever a row is saved or deleted. Returns [] when the table is missing
     * (fresh checkout / mid-migration) so callers can fall back to config.
     */
    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return static::query()
                ->get(['key', 'value', 'type'])
                ->mapWithKeys(fn (self $s) => [$s->key => $s->castValue()])
                ->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::map()[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => is_array($value) ? json_encode($value) : $value]);
    }

    protected function castValue(): mixed
    {
        return match ($this->type) {
            'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'int' => $this->value === null || $this->value === '' ? null : (int) $this->value,
            default => $this->value === '' ? null : $this->value,
        };
    }
}
