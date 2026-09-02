<?php

namespace App\Filament\Resources\MarketplaceInstallations\Schemas;

use App\Models\MarketplaceInstallation;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MarketplaceInstallationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('نصب')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('وضعیت')
                            ->options(MarketplaceInstallation::STATUSES)
                            ->required(),

                        TextInput::make('token')->label('کد پیگیری')->disabled()->dehydrated(false),
                        TextInput::make('app.name')->label('افزونه')->disabled()->dehydrated(false),
                        TextInput::make('price')->label('مبلغ (تومان)')->disabled()->dehydrated(false),

                        DateTimePicker::make('activated_at')->label('زمان فعال‌سازی'),
                        DateTimePicker::make('expires_at')->label('انقضا')->helperText('برای تمدید دستی اشتراک، این تاریخ را جلو ببرید.'),

                        Textarea::make('admin_note')->label('یادداشت داخلی')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('مشتری')
                    ->columns(2)
                    ->schema([
                        TextInput::make('user.full_name')->label('نام')->disabled()->dehydrated(false),
                        TextInput::make('user.mobile')->label('موبایل')->disabled()->dehydrated(false),
                    ]),

                Section::make('اطلاعات اتصال')
                    ->schema([
                        Placeholder::make('config_view')
                            ->label('')
                            ->content(fn (?MarketplaceInstallation $record) => static::maskedConfig($record)),
                    ]),
            ]);
    }

    private static function maskedConfig(?MarketplaceInstallation $record): string
    {
        $config = (array) ($record?->config ?? []);

        if ($config === []) {
            return 'بدون اطلاعات اتصال.';
        }

        $secretKeys = collect($record?->app?->configFields() ?? [])
            ->filter(fn ($f) => ! empty($f['secret']))
            ->pluck('key')
            ->all();

        return collect($config)
            ->map(fn ($value, $key) => $key.': '.(in_array($key, $secretKeys, true) ? str_repeat('•', 8) : (string) $value))
            ->implode("\n");
    }
}
