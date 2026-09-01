<?php

namespace App\Filament\Resources\DocArticles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DocArticlesTable
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

                TextColumn::make('category.title')
                    ->label('دسته')
                    ->badge()
                    ->sortable(),

                TextColumn::make('http_method')
                    ->label('متد')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'GET' => 'success',
                        'POST' => 'warning',
                        'PUT', 'PATCH' => 'info',
                        'DELETE' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('—'),

                TextColumn::make('endpoint')
                    ->label('Endpoint')
                    ->fontFamily('mono')
                    ->color('gray')
                    ->placeholder('—')
                    ->searchable(),

                IconColumn::make('is_published')
                    ->label('انتشار')
                    ->boolean(),

                TextColumn::make('sort')
                    ->label('ترتیب')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('doc_category_id')
                    ->label('دسته‌بندی')
                    ->relationship('category', 'title'),

                TernaryFilter::make('is_published')
                    ->label('وضعیت انتشار'),
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
