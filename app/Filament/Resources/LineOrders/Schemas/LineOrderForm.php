<?php

namespace App\Filament\Resources\LineOrders\Schemas;

use App\Models\LineOrder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LineOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('پیگیری سفارش')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('وضعیت')
                            ->options(LineOrder::STATUSES)
                            ->required(),

                        TextInput::make('token')
                            ->label('کد پیگیری')
                            ->disabled()
                            ->dehydrated(false),

                        Textarea::make('admin_note')
                            ->label('یادداشت داخلی')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('اطلاعات خط')
                    ->columns(2)
                    ->schema([
                        TextInput::make('line_label')
                            ->label('خط')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('price')
                            ->label('قیمت (تومان)')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('desired_number')
                            ->label('شماره درخواستی مشتری')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Section::make('اطلاعات مشتری')
                    ->columns(2)
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('نام')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('customer_phone')
                            ->label('موبایل')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('customer_email')
                            ->label('ایمیل')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('company')
                            ->label('شرکت')
                            ->disabled()
                            ->dehydrated(false),

                        Textarea::make('note')
                            ->label('توضیحات مشتری')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
