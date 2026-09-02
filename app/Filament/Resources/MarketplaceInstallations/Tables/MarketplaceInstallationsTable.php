<?php

namespace App\Filament\Resources\MarketplaceInstallations\Tables;

use App\Models\MarketplaceInstallation;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MarketplaceInstallationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('نصب')->jalaliDateTime()->sortable(),

                TextColumn::make('user.mobile')->label('مشتری')->searchable(),

                TextColumn::make('app.name')->label('افزونه')->badge()->searchable(),

                TextColumn::make('price')
                    ->label('مبلغ')
                    ->numeric(locale: 'en')
                    ->suffix(' ت')
                    ->formatStateUsing(fn ($state) => (int) $state === 0 ? 'رایگان' : number_format((int) $state)),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'cancelled', 'expired', 'suspended' => 'danger',
                        'awaiting_payment' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => MarketplaceInstallation::STATUSES[$state] ?? $state),

                TextColumn::make('expires_at')->label('انقضا')->jalaliDate()->placeholder('—')->toggleable(),

                TextColumn::make('last_synced_at')->label('آخرین همگام‌سازی')->jalaliDateTime()->placeholder('—')->toggleable(),

                TextColumn::make('token')->label('کد پیگیری')->badge()->color('gray')->copyable()->searchable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(MarketplaceInstallation::STATUSES),
                SelectFilter::make('marketplace_app_id')
                    ->label('افزونه')
                    ->relationship('app', 'name'),
            ])
            ->recordActions([EditAction::make()]);
    }
}
