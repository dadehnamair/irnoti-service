<?php

namespace App\Filament\Resources\BankAccounts\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('حساب بانکی')
                    ->columns(2)
                    ->schema([
                        TextInput::make('bank_name')->label('نام بانک')->required(),
                        TextInput::make('owner_name')->label('نام صاحب حساب')->required(),
                        TextInput::make('card_number')->label('شماره کارت')->nullable()->maxLength(30),
                        TextInput::make('sheba')->label('شماره شبا (بدون IR)')->nullable()->maxLength(30),
                        TextInput::make('account_number')->label('شماره حساب')->nullable(),
                        TextInput::make('note')->label('توضیح')->nullable()->columnSpanFull(),
                        TextInput::make('sort')->label('ترتیب نمایش')->numeric()->default(0)->required(),
                        Toggle::make('is_active')->label('فعال')->default(true),
                    ]),
            ]);
    }
}
