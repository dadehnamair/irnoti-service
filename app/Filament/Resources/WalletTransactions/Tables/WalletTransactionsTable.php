<?php

namespace App\Filament\Resources\WalletTransactions\Tables;

use App\Models\WalletTransaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WalletTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('تاریخ')->jalaliDateTime()->sortable(),
                TextColumn::make('user.mobile')->label('کاربر')->searchable(),
                TextColumn::make('type')
                    ->label('نوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => WalletTransaction::TYPES[$state] ?? $state),
                TextColumn::make('direction')
                    ->label('جهت')
                    ->badge()
                    ->color(fn ($state) => $state === 'debit' ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state) => WalletTransaction::DIRECTIONS[$state] ?? $state),
                TextColumn::make('amount')->label('مبلغ')->toman()->sortable(),
                TextColumn::make('balance_after')->label('موجودی پس از تراکنش')->toman(),
                TextColumn::make('description')->label('شرح')->wrap()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('type')->label('نوع')->options(WalletTransaction::TYPES),
                SelectFilter::make('direction')->label('جهت')->options(WalletTransaction::DIRECTIONS),
            ]);
    }
}
