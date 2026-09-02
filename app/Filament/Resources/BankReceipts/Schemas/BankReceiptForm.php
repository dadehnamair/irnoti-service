<?php

namespace App\Filament\Resources\BankReceipts\Schemas;

use App\Models\BankReceipt;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات فیش')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('purpose_label')
                            ->label('بابت')
                            ->content(fn (BankReceipt $record) => $record->purpose_label),

                        Placeholder::make('amount_display')
                            ->label('مبلغ')
                            ->content(fn (BankReceipt $record) => toman($record->amount, true)),

                        TextInput::make('tracking_code')->label('شماره پیگیری')->disabled()->dehydrated(false),
                        Placeholder::make('transfer_type_label')
                            ->label('نوع انتقال')
                            ->content(fn (BankReceipt $record) => $record->transfer_type_label),

                        Placeholder::make('paid_at_jalali')
                            ->label('تاریخ واریز')
                            ->content(fn (BankReceipt $record) => jalali_date($record->paid_at)),

                        Placeholder::make('bank_account_label')
                            ->label('واریز به حساب')
                            ->content(fn (BankReceipt $record) => $record->bankAccount?->label ?? '—'),

                        FileUpload::make('image_path')
                            ->label('تصویر فیش')
                            ->disk('local')
                            ->image()
                            ->openable()
                            ->downloadable()
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('مشتری')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('user_name')
                            ->label('نام')
                            ->content(fn (BankReceipt $record) => $record->user?->full_name ?? '—'),
                        Placeholder::make('user_mobile')
                            ->label('موبایل')
                            ->content(fn (BankReceipt $record) => $record->user?->mobile ?? '—'),
                    ]),

                Section::make('بررسی')
                    ->schema([
                        Placeholder::make('status_label')
                            ->label('وضعیت فعلی')
                            ->content(fn (BankReceipt $record) => $record->status_label),

                        Textarea::make('admin_note')
                            ->label('یادداشت / دلیل رد')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
