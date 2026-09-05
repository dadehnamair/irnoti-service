<?php

namespace App\Filament\Resources\Domains\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DomainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('host')
                    ->label('دامنه')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('label')
                    ->label('عنوان')
                    ->placeholder('—'),

                TextColumn::make('code_price_tiers')
                    ->label('تعرفه‌ها')
                    ->formatStateUsing(fn (?array $state) => $state ? count($state).' ردیف' : '—'),

                TextColumn::make('cards_count')
                    ->label('کارت‌ها')
                    ->counts('cards')
                    ->numeric(locale: 'en'),

                IconColumn::make('is_default')
                    ->label('پیش‌فرض')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('فعال'),
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
