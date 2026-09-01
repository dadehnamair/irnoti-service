<?php

namespace App\Filament\Resources\DocCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocCategoriesTable
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

                TextColumn::make('parent.title')
                    ->label('والد')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('slug')
                    ->label('نامک')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('articles_count')
                    ->label('مقاله‌ها')
                    ->counts('articles')
                    ->badge(),

                IconColumn::make('is_published')
                    ->label('انتشار')
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
