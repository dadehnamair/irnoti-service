<?php

namespace App\Filament\Resources\LineGroups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LineGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('prefix')
                    ->label('پیش‌شماره')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('نامک')
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('lines_count')
                    ->label('گونه‌ها')
                    ->counts('lines')
                    ->numeric(locale: 'en'),

                TextColumn::make('bundles_count')
                    ->label('باندل‌ها')
                    ->counts('bundles')
                    ->numeric(locale: 'en'),

                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),

                TextColumn::make('sort')
                    ->label('ترتیب')
                    ->numeric()
                    ->sortable(),
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
