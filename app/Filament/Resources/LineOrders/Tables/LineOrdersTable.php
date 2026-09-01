<?php

namespace App\Filament\Resources\LineOrders\Tables;

use App\Models\LineOrder;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LineOrdersTable
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

                TextColumn::make('line_label')
                    ->label('خط')
                    ->searchable(),

                TextColumn::make('customer_name')
                    ->label('مشتری')
                    ->searchable(),

                TextColumn::make('customer_phone')
                    ->label('موبایل')
                    ->searchable(),

                TextColumn::make('price')
                    ->label('قیمت')
                    ->numeric(locale: 'en')
                    ->suffix(' ت'),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'completed', 'paid' => 'success',
                        'rejected', 'cancelled' => 'danger',
                        'processing', 'awaiting_payment' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => LineOrder::STATUSES[$state] ?? $state),

                TextColumn::make('paid_at')
                    ->label('پرداخت')
                    ->dateTime('Y-m-d H:i')
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
                    ->options(LineOrder::STATUSES),
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
