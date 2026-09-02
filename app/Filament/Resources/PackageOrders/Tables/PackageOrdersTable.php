<?php

namespace App\Filament\Resources\PackageOrders\Tables;

use App\Models\PackageOrder;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PackageOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('تاریخ')->jalaliDateTime()->sortable(),
                TextColumn::make('user.mobile')->label('مشتری')->searchable(),
                TextColumn::make('package_name')->label('بسته')->badge()->searchable(),
                TextColumn::make('sms_count')->label('پیامک')->numeric(locale: 'en'),
                TextColumn::make('price')->label('مبلغ')->toman(),
                TextColumn::make('method')->label('روش')->badge()->placeholder('—'),
                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'completed', 'paid' => 'success',
                        'cancelled' => 'danger',
                        'awaiting_payment' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => PackageOrder::STATUSES[$state] ?? $state),
                TextColumn::make('token')->label('کد پیگیری')->badge()->color('gray')->copyable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(PackageOrder::STATUSES),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
