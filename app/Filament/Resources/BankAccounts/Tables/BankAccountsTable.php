<?php

namespace App\Filament\Resources\BankAccounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BankAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('bank_name')->label('بانک')->searchable()->sortable(),
                TextColumn::make('owner_name')->label('صاحب حساب')->searchable(),
                TextColumn::make('card_number')->label('شماره کارت')->copyable()->placeholder('—'),
                TextColumn::make('sheba')->label('شبا')->copyable()->placeholder('—')->toggleable(),
                IconColumn::make('is_active')->label('فعال')->boolean(),
                TextColumn::make('sort')->label('ترتیب')->numeric()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('وضعیت'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
