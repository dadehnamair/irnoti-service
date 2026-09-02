<?php

namespace App\Providers;

use App\Models\LineOrder;
use App\Models\Setting;
use App\Observers\LineOrderObserver;
use App\Services\Sms\LogProvider;
use App\Services\Sms\MelipayamakProvider;
use App\Services\Sms\NullProvider;
use App\Services\Sms\Phonebook\LogPhonebookClient;
use App\Services\Sms\Phonebook\NullPhonebookClient;
use App\Services\Sms\Phonebook\PhonebookClientInterface;
use App\Services\Sms\SmsProviderInterface;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->bindSmsProvider();
        $this->bindPhonebookClient();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->overlaySettingsOnConfig();

        // SMS the buyer when a line order's status changes (docs/starter.md §44).
        LineOrder::observe(LineOrderObserver::class);

        $this->registerBladeDirectives();
        $this->registerFilamentColumnMacros();
    }

    /**
     * Jalali dates + comma-grouped Toman for Blade. Backed by the helpers in
     * app/Support/helpers.php so views can do @jdate($x) / @toman($x).
     */
    private function registerBladeDirectives(): void
    {
        Blade::directive('jdate', fn ($expr) => "<?php echo e(jalali_date($expr)); ?>");
        Blade::directive('jdatetime', fn ($expr) => "<?php echo e(jalali_datetime($expr)); ?>");
        Blade::directive('toman', fn ($expr) => "<?php echo e(toman($expr)); ?>");
    }

    /**
     * Same two concerns for Filament admin tables: ->jalaliDate() / ->jalaliDateTime()
     * on date columns and ->toman() on money columns, so every *Table.php stays a
     * one-liner and the whole panel reads Shamsi + 1,234,567.
     */
    private function registerFilamentColumnMacros(): void
    {
        if (! class_exists(TextColumn::class)) {
            return;
        }

        TextColumn::macro('jalaliDate', function () {
            /** @var TextColumn $this */
            return $this->formatStateUsing(fn ($state) => jalali_date($state))->tooltip(fn ($state) => $state);
        });

        TextColumn::macro('jalaliDateTime', function () {
            /** @var TextColumn $this */
            return $this->formatStateUsing(fn ($state) => jalali_datetime($state))->tooltip(fn ($state) => $state);
        });

        TextColumn::macro('toman', function (bool $withUnit = true) {
            /** @var TextColumn $this */
            return $this->formatStateUsing(fn ($state) => $state === null || $state === ''
                ? '—'
                : toman($state, $withUnit));
        });
    }

    /**
     * Resolve the SMS driver from config('services.sms.provider') — docs/starter.md
     * §12/§13. "log" is the credential-free default (mirrors PAYMENT_DRIVER=local);
     * tests get the no-op NullProvider.
     */
    private function bindSmsProvider(): void
    {
        $this->app->singleton(SmsProviderInterface::class, function () {
            return match ((string) config('services.sms.provider', 'log')) {
                'melipayamak' => new MelipayamakProvider((array) config('services.sms.melipayamak')),
                'null' => new NullProvider,
                default => new LogProvider,
            };
        });
    }

    /**
     * The phonebook client (docs/starter.md §17). The real, per-customer path
     * always builds its own instance via App\Services\Sms\Phonebook\UserPhonebook
     * from the customer's panel credentials — this container binding only serves
     * tests (SMS_PROVIDER=null) and the credential-free dev default.
     */
    private function bindPhonebookClient(): void
    {
        $this->app->singleton(PhonebookClientInterface::class, function () {
            return match ((string) config('services.sms.provider', 'log')) {
                'null' => new NullPhonebookClient,
                default => new LogPhonebookClient,
            };
        });
    }

    /**
     * Overlay the DB-backed "base info" (settings table) on top of
     * config/theme.php so every Blade view can keep reading config('theme.*')
     * while the values are actually editable from the admin panel.
     * Falls back silently to the file defaults when the table is empty/missing.
     */
    private function overlaySettingsOnConfig(): void
    {
        try {
            $s = Setting::map();
        } catch (\Throwable $e) {
            return; // DB down / table missing — keep config/theme.php defaults
        }

        if ($s === []) {
            return;
        }

        $put = function (string $configKey, string $settingKey) use ($s): void {
            if (array_key_exists($settingKey, $s) && $s[$settingKey] !== null && $s[$settingKey] !== '') {
                config([$configKey => $s[$settingKey]]);
            }
        };

        $put('theme.brand', 'brand');
        $put('theme.tagline', 'tagline');

        $put('theme.primary', 'primary');
        $put('theme.accent', 'accent');
        $put('theme.secondary', 'secondary');

        $put('theme.email', 'email');
        $put('theme.phone', 'phone');
        $put('theme.phone_display', 'phone_display');
        $put('theme.address', 'address');

        $put('theme.seo.title', 'seo_title');
        $put('theme.seo.description', 'seo_description');
        $put('theme.seo.keywords', 'seo_keywords');
        $put('theme.seo.image', 'seo_image');

        $social = array_filter([
            'instagram' => $s['social_instagram'] ?? null,
            'telegram' => $s['social_telegram'] ?? null,
            'linkedin' => $s['social_linkedin'] ?? null,
            'x' => $s['social_x'] ?? null,
        ]);

        if ($social !== []) {
            config(['theme.social' => $social]);
        }
    }
}
