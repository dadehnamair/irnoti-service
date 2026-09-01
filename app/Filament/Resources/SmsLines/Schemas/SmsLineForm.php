<?php

namespace App\Filament\Resources\SmsLines\Schemas;

use App\Models\SmsLine;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SmsLineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('مشخصات خط')
                    ->columns(2)
                    ->schema([
                        TextInput::make('prefix')
                            ->label('پیش‌شماره (Prefix)')
                            ->required()
                            ->placeholder('3000')
                            ->helperText('گروه‌بندی صفحه خطوط بر اساس همین مقدار است.'),

                        TextInput::make('operator')
                            ->label('اپراتور')
                            ->placeholder('مگفا / آسیاتک / همراه اول'),

                        TextInput::make('number')
                            ->label('شماره کامل (اختیاری)')
                            ->placeholder('30001234567')
                            ->helperText('اگر خالی باشد، به‌صورت 3000XXXX نمایش داده می‌شود.'),

                        TextInput::make('digits')
                            ->label('تعداد ارقام')
                            ->numeric()
                            ->minValue(3)
                            ->maxValue(20)
                            ->required()
                            ->default(10),

                        Select::make('line_type')
                            ->label('نوع خط')
                            ->options(SmsLine::TYPES)
                            ->default('dedicated')
                            ->required(),

                        Toggle::make('is_rond')
                            ->label('رند'),
                    ]),

                Section::make('قیمت‌گذاری (تومان)')
                    ->columns(3)
                    ->schema([
                        TextInput::make('price')
                            ->label('قیمت')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->suffix('تومان'),

                        TextInput::make('reseller_price')
                            ->label('قیمت نمایندگی')
                            ->numeric()
                            ->nullable()
                            ->suffix('تومان'),

                        TextInput::make('compare_at_price')
                            ->label('قیمت قبل از تخفیف')
                            ->numeric()
                            ->nullable()
                            ->suffix('تومان')
                            ->helperText('اگر پر شود، خط‌خورده کنار قیمت نمایش داده می‌شود.'),
                    ]),

                Section::make('توضیحات و امکانات')
                    ->schema([
                        Textarea::make('description')
                            ->label('توضیح کوتاه')
                            ->rows(2)
                            ->columnSpanFull(),

                        TagsInput::make('features')
                            ->label('فهرست امکانات')
                            ->placeholder('یک ویژگی بنویسید و Enter بزنید')
                            ->columnSpanFull(),
                    ]),

                Section::make('وضعیت')
                    ->columns(2)
                    ->schema([
                        Select::make('sale_status')
                            ->label('وضعیت فروش')
                            ->options(SmsLine::SALE_STATUSES)
                            ->default('available')
                            ->required(),

                        TextInput::make('sort')
                            ->label('ترتیب نمایش')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Toggle::make('requires_inquiry')
                            ->label('نیازمند استعلام')
                            ->helperText('به‌جای قیمت، دکمه «استعلام قیمت» نمایش داده می‌شود.'),

                        Toggle::make('is_active')
                            ->label('فعال')
                            ->default(true),
                    ]),
            ]);
    }
}
