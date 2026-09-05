<?php

namespace App\Filament\Resources\BusinessCards\Tables;

use App\Models\BusinessCard;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BusinessCardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('تاریخ')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('user.mobile')
                    ->label('کاربر')
                    ->searchable(),

                TextColumn::make('domain.host')
                    ->label('دامنه')
                    ->badge(),

                TextColumn::make('code')
                    ->label('کد')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('tier')
                    ->label('نوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => BusinessCard::TIERS[$state] ?? $state),

                TextColumn::make('price')
                    ->label('قیمت')
                    ->numeric(locale: 'en')
                    ->suffix(' ت'),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'disabled' => 'danger',
                        'awaiting_payment' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => BusinessCard::STATUSES[$state] ?? $state),

                TextColumn::make('views_count')
                    ->label('بازدید')
                    ->numeric(locale: 'en')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(BusinessCard::STATUSES),

                SelectFilter::make('tier')
                    ->label('نوع')
                    ->options(BusinessCard::TIERS),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
