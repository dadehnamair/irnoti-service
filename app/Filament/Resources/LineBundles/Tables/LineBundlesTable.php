<?php

namespace App\Filament\Resources\LineBundles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LineBundlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('group.prefix')
                    ->label('خط')
                    ->badge()
                    ->sortable(),

                TextColumn::make('sms_credit')
                    ->label('اعتبار پیامک')
                    ->numeric(locale: 'en')
                    ->sortable(),

                TextColumn::make('validity_days')
                    ->label('روز')
                    ->numeric(locale: 'en')
                    ->placeholder('—'),

                TextColumn::make('price')
                    ->label('قیمت')
                    ->numeric(locale: 'en')
                    ->suffix(' ت')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),

                TextColumn::make('sort')
                    ->label('ترتیب')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('line_group_id')
                    ->label('صفحهٔ خط')
                    ->relationship('group', 'title'),

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
