<?php

namespace App\Filament\Resources\SmsLines\Tables;

use App\Models\SmsLine;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SmsLinesTable
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

                TextColumn::make('operator')
                    ->label('اپراتور')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('digits')
                    ->label('ارقام')
                    ->numeric(locale: 'en')
                    ->sortable(),

                TextColumn::make('line_type')
                    ->label('نوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => SmsLine::TYPES[$state] ?? $state),

                TextColumn::make('price')
                    ->label('قیمت')
                    ->numeric(locale: 'en')
                    ->suffix(' ت')
                    ->sortable(),

                IconColumn::make('is_rond')
                    ->label('رند')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('sale_status')
                    ->label('فروش')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'available' => 'success',
                        'reserved' => 'warning',
                        'sold' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => SmsLine::SALE_STATUSES[$state] ?? $state),

                IconColumn::make('requires_inquiry')
                    ->label('استعلام')
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('prefix')
                    ->label('پیش‌شماره')
                    ->options(fn () => SmsLine::query()->distinct()->orderBy('prefix')->pluck('prefix', 'prefix')->all()),

                SelectFilter::make('line_type')
                    ->label('نوع خط')
                    ->options(SmsLine::TYPES),

                SelectFilter::make('sale_status')
                    ->label('وضعیت فروش')
                    ->options(SmsLine::SALE_STATUSES),

                TernaryFilter::make('is_active')->label('فعال'),
                TernaryFilter::make('is_rond')->label('رند'),
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
