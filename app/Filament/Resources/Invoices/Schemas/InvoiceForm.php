<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Invoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('صورت‌حساب')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('مشتری')
                            ->relationship('user', 'mobile')
                            ->getOptionLabelFromRecordUsing(fn ($record) => trim($record->full_name.' — '.$record->mobile))
                            ->searchable(['mobile', 'first_name', 'last_name', 'name'])
                            ->required(),

                        Placeholder::make('number')
                            ->label('شماره صورت‌حساب')
                            ->content(fn (?Invoice $record) => $record?->number ?? 'هنگام ذخیره ساخته می‌شود'),

                        TextInput::make('title')->label('عنوان')->required()->columnSpanFull(),

                        Textarea::make('description')->label('توضیحات')->rows(2)->columnSpanFull(),

                        Select::make('status')
                            ->label('وضعیت')
                            ->options(Invoice::STATUSES)
                            ->default('draft')
                            ->required()
                            ->helperText('برای ارسال به مشتری از دکمهٔ «صدور صورت‌حساب» استفاده کنید.'),

                        DatePicker::make('due_at')->label('مهلت پرداخت'),
                    ]),

                Section::make('اقلام')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->label('ردیف‌ها')
                            ->columns(4)
                            ->schema([
                                TextInput::make('description')->label('شرح')->required()->columnSpan(2),
                                TextInput::make('quantity')->label('تعداد')->numeric()->default(1)->minValue(1)->required(),
                                TextInput::make('unit_price')->label('قیمت واحد (تومان)')->numeric()->default(0)->required(),
                            ])
                            ->addActionLabel('افزودن ردیف')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),

                Section::make('مبالغ (تومان)')
                    ->columns(3)
                    ->schema([
                        TextInput::make('discount')->label('تخفیف')->numeric()->default(0),
                        TextInput::make('tax')->label('مالیات')->numeric()->default(0),
                        Placeholder::make('total_hint')
                            ->label('مبلغ نهایی')
                            ->content(fn (?Invoice $record) => $record ? toman($record->total, true) : 'پس از ذخیره محاسبه می‌شود'),
                    ]),
            ]);
    }
}
