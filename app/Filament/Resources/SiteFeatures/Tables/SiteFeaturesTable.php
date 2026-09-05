<?php

namespace App\Filament\Resources\SiteFeatures\Tables;

use App\Models\SiteFeature;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SiteFeaturesTable
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

                TextColumn::make('category')
                    ->label('دسته‌بندی')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => SiteFeature::CATEGORIES[$state] ?? $state),

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
                SelectFilter::make('category')
                    ->label('دسته‌بندی')
                    ->options(SiteFeature::CATEGORIES),
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
