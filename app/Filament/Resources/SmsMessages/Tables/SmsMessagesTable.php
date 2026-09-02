<?php

namespace App\Filament\Resources\SmsMessages\Tables;

use App\Models\SmsMessage;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SmsMessagesTable
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

                TextColumn::make('user.name')
                    ->label('کاربر')
                    ->searchable(['first_name', 'last_name', 'name'])
                    ->placeholder('—'),

                TextColumn::make('to')
                    ->label('گیرنده')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('body')
                    ->label('متن')
                    ->limit(60)
                    ->wrap(),

                TextColumn::make('parts')
                    ->label('تعداد')
                    ->alignCenter(),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => SmsMessage::STATUSES[$state] ?? $state),

                TextColumn::make('delivery_status')
                    ->label('تحویل')
                    ->badge()
                    ->placeholder('—')
                    ->color(fn (?string $state) => match ($state) {
                        'delivered' => 'success',
                        'undelivered', 'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => SmsMessage::DELIVERY_STATUSES[$state] ?? $state)
                    ->description(fn (SmsMessage $record) => $record->delivery_checked_at
                        ? 'آخرین بررسی: '.$record->delivery_checked_at->format('Y-m-d H:i')
                        : null),

                TextColumn::make('rec_id')
                    ->label('کد پیام')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('error')
                    ->label('خطا')
                    ->placeholder('—')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(SmsMessage::STATUSES),

                SelectFilter::make('delivery_status')
                    ->label('وضعیت تحویل')
                    ->options(SmsMessage::DELIVERY_STATUSES),
            ]);
    }
}
