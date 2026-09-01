<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('badge_label')
                    ->label('برچسب')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('price_monthly')
                    ->label('ماهانه')
                    ->numeric(locale: 'en')
                    ->suffix(' ت')
                    ->sortable(),

                TextColumn::make('price_yearly')
                    ->label('سالانه')
                    ->numeric(locale: 'en')
                    ->suffix(' ت')
                    ->toggleable(),

                TextColumn::make('sms_count')
                    ->label('پیامک')
                    ->numeric(locale: 'en')
                    ->placeholder('—')
                    ->toggleable(),

                IconColumn::make('is_featured')
                    ->label('ویژه')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),

                TextColumn::make('sort')
                    ->label('ترتیب')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('وضعیت'),
                TernaryFilter::make('is_featured')->label('ویژه'),
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
