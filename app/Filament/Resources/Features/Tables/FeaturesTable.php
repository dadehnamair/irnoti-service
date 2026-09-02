<?php

namespace App\Filament\Resources\Features\Tables;

use App\Models\Feature;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class FeaturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->defaultGroup('group_label')
            ->groups([
                Group::make('group_label')->label('گروه منو'),
            ])
            ->columns([
                TextColumn::make('label')
                    ->label('عنوان')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('key')
                    ->label('کلید')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('route')
                    ->label('مسیر')
                    ->placeholder('—'),

                TextColumn::make('user_groups_count')
                    ->counts('userGroups')
                    ->label('گروه‌ها')
                    ->badge(),

                ToggleColumn::make('is_active')
                    ->label('فعال'),

                TextColumn::make('sort')
                    ->label('ترتیب')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('group_key')
                    ->label('گروه منو')
                    ->options(fn () => Feature::query()
                        ->orderBy('sort')
                        ->pluck('group_label', 'group_key')
                        ->all()),

                TernaryFilter::make('is_active')
                    ->label('وضعیت فعال‌سازی'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([])]);
    }
}
