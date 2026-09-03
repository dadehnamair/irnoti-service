<?php

namespace App\Filament\Resources\MessengerCampaigns\Tables;

use App\Models\MessengerCampaign;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MessengerCampaignsTable
{
    /** @return array<string, string> channel key => brand-neutral label */
    private static function channelOptions(): array
    {
        return collect(config('messenger.channels', []))
            ->mapWithKeys(fn (array $c, string $key) => [$key => $c['label'] ?? $key])
            ->all();
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('تاریخ')
                    ->jalaliDateTime()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('کاربر')
                    ->searchable(['first_name', 'last_name', 'name'])
                    ->placeholder('—'),

                TextColumn::make('channel')
                    ->label('پیام‌رسان')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::channelOptions()[$state] ?? $state),

                TextColumn::make('body')
                    ->label('متن')
                    ->limit(50)
                    ->wrap(),

                TextColumn::make('recipients_count')
                    ->label('گیرندگان')
                    ->alignCenter(),

                TextColumn::make('success_count')
                    ->label('موفق')
                    ->alignCenter()
                    ->color('success'),

                TextColumn::make('failed_count')
                    ->label('ناموفق')
                    ->alignCenter()
                    ->color(fn (int $state) => $state > 0 ? 'danger' : 'gray'),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'sent' => 'success',
                        'partial' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => MessengerCampaign::STATUSES[$state] ?? $state),

                TextColumn::make('cost')
                    ->label('هزینه')
                    ->toman(),

                TextColumn::make('refunded')
                    ->label('برگشتی')
                    ->toman()
                    ->toggleable(),

                TextColumn::make('batch_id')
                    ->label('کد دسته')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('error')
                    ->label('خطا')
                    ->placeholder('—')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->label('پیام‌رسان')
                    ->options(self::channelOptions()),

                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(MessengerCampaign::STATUSES),
            ]);
    }
}
