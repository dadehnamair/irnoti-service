<?php

namespace App\Filament\Resources\Domains\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DomainForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('مشخصات دامنه')
                    ->columns(2)
                    ->schema([
                        TextInput::make('host')
                            ->label('دامنه')
                            ->required()
                            ->placeholder('11v.ir')
                            ->helperText('بدون http/https — همان چیزی که در Host واقعی درخواست‌ها می‌آید.'),

                        TextInput::make('label')
                            ->label('عنوان نمایشی')
                            ->placeholder('دامنه ویژه ۱۱وی'),

                        Toggle::make('is_active')
                            ->label('فعال')
                            ->default(true),

                        Toggle::make('is_default')
                            ->label('دامنه پیش‌فرض')
                            ->helperText('کارت‌های استاندارد در نبود انتخاب دیگر روی این دامنه ساخته می‌شوند.'),

                        TextInput::make('sort')
                            ->label('ترتیب نمایش')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),

                Section::make('تعرفه کد اختصاصی')
                    ->description('برای کارت‌های «اختصاصی»: بر اساس طول و نوع کد، اولین ردیف منطبق قیمت را تعیین می‌کند.')
                    ->schema([
                        Repeater::make('code_price_tiers')
                            ->label('')
                            ->addActionLabel('افزودن ردیف تعرفه')
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->columns(4)
                            ->schema([
                                TextInput::make('label')
                                    ->label('عنوان')
                                    ->placeholder('کد تک‌رقمی')
                                    ->columnSpanFull(),

                                Select::make('type')
                                    ->label('نوع کد')
                                    ->options([
                                        'numeric' => 'فقط عدد',
                                        'alpha' => 'فقط حرف',
                                        'mixed' => 'عدد یا حرف',
                                    ])
                                    ->default('mixed')
                                    ->required(),

                                TextInput::make('min_length')
                                    ->label('حداقل طول')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('max_length')
                                    ->label('حداکثر طول')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('price')
                                    ->label('قیمت (تومان)')
                                    ->numeric()
                                    ->required()
                                    ->suffix('تومان'),
                            ]),
                    ]),
            ]);
    }
}
