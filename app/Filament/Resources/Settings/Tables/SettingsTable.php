<?php

namespace App\Filament\Resources\Settings\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->defaultGroup('group')
            ->paginated(false)
            ->columns([
                TextColumn::make('label')
                    ->label('عنوان')
                    ->searchable(),

                TextColumn::make('key')
                    ->label('کلید')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('value')
                    ->label('مقدار فعلی')
                    ->limit(60)
                    ->color(fn ($record) => $record->type === 'color' ? null : 'gray'),

                TextColumn::make('group')
                    ->label('دسته')
                    ->badge(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
