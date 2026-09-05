<?php

namespace App\Filament\Resources\BusinessCards\Schemas;

use App\Models\BusinessCard;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BusinessCardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('پیگیری')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('وضعیت')
                            ->options(BusinessCard::STATUSES)
                            ->required(),

                        Textarea::make('admin_note')
                            ->label('یادداشت داخلی')
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('payment_driver')
                            ->label('درگاه پرداخت')
                            ->disabled()->dehydrated(false)->placeholder('—'),

                        TextInput::make('reference_id')
                            ->label('کد پیگیری بانک')
                            ->disabled()->dehydrated(false)->placeholder('—'),

                        TextInput::make('paid_at')
                            ->label('زمان پرداخت')
                            ->disabled()->dehydrated(false)->placeholder('پرداخت نشده'),
                    ]),

                Section::make('مشخصات کارت')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('کد')
                            ->disabled()->dehydrated(false),

                        TextInput::make('price')
                            ->label('قیمت (تومان)')
                            ->disabled()->dehydrated(false),

                        TextInput::make('title')
                            ->label('عنوان')
                            ->disabled()->dehydrated(false),

                        TextInput::make('company')
                            ->label('شرکت')
                            ->disabled()->dehydrated(false),

                        TextInput::make('phone')
                            ->label('تلفن')
                            ->disabled()->dehydrated(false),

                        TextInput::make('mobile')
                            ->label('موبایل')
                            ->disabled()->dehydrated(false),
                    ]),
            ]);
    }
}
