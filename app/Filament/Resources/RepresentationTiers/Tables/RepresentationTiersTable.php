<?php

namespace App\Filament\Resources\RepresentationTiers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RepresentationTiersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')
                    ->label('نام تعرفه')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('commission_percent')
                    ->label('کمیسیون')
                    ->suffix('٪'),

                TextColumn::make('investment_label')
                    ->label('سرمایه لازم'),

                TextColumn::make('applications_count')
                    ->label('درخواست‌ها')
                    ->counts('applications')
                    ->badge(),

                IconColumn::make('is_featured')
                    ->label('پیشنهادی')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),

                TextColumn::make('sort')
                    ->label('ترتیب')
                    ->numeric()
                    ->sortable(),
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
