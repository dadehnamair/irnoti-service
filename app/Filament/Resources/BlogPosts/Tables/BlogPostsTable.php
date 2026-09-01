<?php

namespace App\Filament\Resources\BlogPosts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BlogPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('تصویر')
                    ->disk('public')
                    ->height(40)
                    ->width(64),

                TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable()
                    ->limit(50)
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('دسته')
                    ->badge()
                    ->placeholder('—'),

                IconColumn::make('is_published')
                    ->label('انتشار')
                    ->boolean(),

                TextColumn::make('published_at')
                    ->label('تاریخ انتشار')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('views')
                    ->label('بازدید')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('blog_category_id')
                    ->label('دسته‌بندی')
                    ->relationship('category', 'name'),

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
