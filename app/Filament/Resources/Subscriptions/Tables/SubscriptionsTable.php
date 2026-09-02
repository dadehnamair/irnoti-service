<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Models\Subscription;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('تاریخ')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('user.mobile')
                    ->label('مشتری')
                    ->searchable(),

                TextColumn::make('plan_name')
                    ->label('پلن')
                    ->badge()
                    ->searchable(),

                TextColumn::make('billing_period')
                    ->label('دوره')
                    ->formatStateUsing(fn (string $state) => Subscription::BILLING_PERIODS[$state] ?? $state),

                TextColumn::make('price')
                    ->label('مبلغ')
                    ->numeric(locale: 'en')
                    ->suffix(' ت')
                    ->formatStateUsing(fn ($state) => (int) $state === 0 ? 'رایگان' : number_format((int) $state)),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active', 'paid' => 'success',
                        'cancelled', 'expired' => 'danger',
                        'awaiting_payment' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => Subscription::STATUSES[$state] ?? $state),

                TextColumn::make('expires_at')
                    ->label('انقضا')
                    ->date('Y-m-d')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('token')
                    ->label('کد پیگیری')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(Subscription::STATUSES),
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
