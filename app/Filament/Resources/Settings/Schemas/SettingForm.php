<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('ویرایش مقدار')
                    ->description('این مقدار روی کل سایت عمومی اعمال می‌شود و بلافاصله پس از ذخیره، کش تنظیمات پاک می‌گردد.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('label')
                            ->label('عنوان')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('key')
                            ->label('کلید')
                            ->disabled()
                            ->dehydrated(false),

                        Toggle::make('value')
                            ->label('فعال باشد')
                            ->visible(fn ($record) => $record?->type === 'bool')
                            ->formatStateUsing(fn ($state) => filter_var($state, FILTER_VALIDATE_BOOLEAN))
                            ->dehydrateStateUsing(fn ($state) => $state ? '1' : '0')
                            ->columnSpanFull(),

                        Textarea::make('value')
                            ->label('مقدار')
                            ->visible(fn ($record) => $record?->type !== 'bool')
                            ->rows(fn ($record) => $record?->type === 'text' ? 3 : 1)
                            ->autosize()
                            ->helperText(fn ($record) => match ($record?->type) {
                                'color' => 'کد رنگ هگز، مثل #ff3000',
                                'url' => 'نشانی کامل، مثل https://instagram.com/irnoti',
                                'int' => 'فقط عدد',
                                default => null,
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
