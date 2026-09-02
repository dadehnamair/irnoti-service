<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Models\Subscription;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اشتراک')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('وضعیت')
                            ->options(Subscription::STATUSES)
                            ->required(),

                        TextInput::make('token')
                            ->label('کد پیگیری')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('plan_name')
                            ->label('پلن')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('price')
                            ->label('مبلغ (تومان)')
                            ->disabled()
                            ->dehydrated(false),

                        DateTimePicker::make('starts_at')->label('شروع'),
                        DateTimePicker::make('expires_at')->label('انقضا'),

                        Textarea::make('admin_note')
                            ->label('یادداشت داخلی')
                            ->rows(2)
                            ->columnSpanFull(),
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
                    ->schema([
                        TextInput::make('user.full_name')->label('نام')->disabled()->dehydrated(false),
                        TextInput::make('user.mobile')->label('موبایل')->disabled()->dehydrated(false),
                    ]),
            ]);
    }
}
