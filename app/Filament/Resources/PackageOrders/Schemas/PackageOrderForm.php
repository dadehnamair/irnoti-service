<?php

namespace App\Filament\Resources\PackageOrders\Schemas;

use App\Models\PackageOrder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PackageOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('سفارش بسته')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('وضعیت')
                            ->options(PackageOrder::STATUSES)
                            ->required()
                            ->helperText('«تکمیل شده» اعتبار پیامکی را به کاربر اضافه نمی‌کند؛ افزودن اعتبار هنگام پرداخت انجام می‌شود.'),

                        TextInput::make('token')->label('کد پیگیری')->disabled()->dehydrated(false),
                        TextInput::make('package_name')->label('بسته')->disabled()->dehydrated(false),
                        TextInput::make('sms_count')->label('تعداد پیامک')->disabled()->dehydrated(false),
                        TextInput::make('price')->label('مبلغ (تومان)')->disabled()->dehydrated(false),
                        TextInput::make('method')->label('روش پرداخت')->disabled()->dehydrated(false)->placeholder('—'),
                    ]),

                Section::make('پرداخت')
                    ->columns(2)
                    ->schema([
                        TextInput::make('payment_driver')->label('درگاه')->disabled()->dehydrated(false)->placeholder('—'),
                        TextInput::make('transaction_id')->label('شناسه تراکنش')->disabled()->dehydrated(false)->placeholder('—'),
                        TextInput::make('reference_id')->label('کد پیگیری بانک')->disabled()->dehydrated(false)->placeholder('—'),
                        TextInput::make('paid_at')->label('زمان پرداخت')->disabled()->dehydrated(false)->placeholder('پرداخت نشده'),
                    ]),

                Section::make('مشتری')
                    ->columns(2)
                    ->schema([
                        TextInput::make('user.full_name')->label('نام')->disabled()->dehydrated(false),
                        TextInput::make('user.mobile')->label('موبایل')->disabled()->dehydrated(false),
                    ]),
            ]);
    }
}
