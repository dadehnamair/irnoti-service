<?php

namespace App\Filament\Resources\Pages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagesTable
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

                TextColumn::make('slug')
                    ->label('نامک')
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_published')
                    ->label('منتشر شده')
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
