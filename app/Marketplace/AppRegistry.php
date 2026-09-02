<?php

namespace App\Marketplace;

use App\Marketplace\Contracts\AppHandler;
use App\Models\MarketplaceApp;
use App\Models\MarketplaceInstallation;
use App\Providers\AppServiceProvider;
use InvalidArgumentException;

/**
 * Resolves a {@see MarketplaceApp} / {@see MarketplaceInstallation} to its
 * {@see AppHandler}, from the config('marketplace.handlers') map. Bound as a
 * singleton in {@see AppServiceProvider::register()}, mirroring
 * the SMS provider binding.
 */
class AppRegistry
{
    /** @var array<string, class-string<AppHandler>> */
    private array $map;

    public function __construct(?array $map = null)
    {
        $this->map = $map ?? (array) config('marketplace.handlers', []);
    }

    public function has(string $key): bool
    {
        return isset($this->map[$key]);
    }

    public function handler(string $key): AppHandler
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Unknown marketplace handler: {$key}");
        }

        return app($this->map[$key]);
    }

    public function for(MarketplaceApp|MarketplaceInstallation $subject): AppHandler
    {
        $key = $subject instanceof MarketplaceInstallation
            ? $subject->app?->handler
            : $subject->handler;

        return $this->handler((string) $key);
    }

    /** @return array<string, string>  key => Persian label, for the admin Select. */
    public function options(): array
    {
        $labels = [
            'feature_unlock' => 'آنلاک قابلیت داخلی پنل',
            'irplus' => 'اتصال به ایرپلاس',
        ];

        return collect(array_keys($this->map))
            ->mapWithKeys(fn (string $key) => [$key => $labels[$key] ?? $key])
            ->all();
    }
}
