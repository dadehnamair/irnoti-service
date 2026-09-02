<?php

namespace App\Filament\Resources\Features\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeatureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('امکان پنل')
                    ->columns(2)
                    ->schema([
                        TextInput::make('key')
                            ->label('کلید')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('group_label')
                            ->label('گروه منو')
                            ->required(),

                        TextInput::make('label')
                            ->label('عنوان نمایشی')
                            ->required(),

                        TextInput::make('route')
                            ->label('نام مسیر (route)')
                            ->placeholder('مثلاً dashboard.sms')
                            ->helperText('اگر خالی باشد یا صفحه‌ای برایش نباشد، فقط به‌صورت «بزودی» نمایش داده می‌شود.'),

                        TextInput::make('sort')
                            ->label('ترتیب نمایش')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Toggle::make('is_active')
                            ->label('فعال (نمایش به‌صورت لینک واقعی)')
                            ->helperText('تا وقتی خاموش است، این آیتم برای همه کاربران «بزودی» است.'),

                        Textarea::make('description')
                            ->label('توضیح')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('گروه‌های کاربری با دسترسی به این امکان')
                    ->schema([
                        CheckboxList::make('userGroups')
                            ->label('')
                            ->relationship('userGroups', 'name')
                            ->columns(3)
                            ->bulkToggleable(),
                    ]),
            ]);
    }
}
