<?php

namespace App\Filament\Resources\RepresentationApplications\Tables;

use App\Models\RepresentationApplication;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RepresentationApplicationsTable
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

                TextColumn::make('full_name')
                    ->label('نام')
                    ->searchable(),

                TextColumn::make('mobile')
                    ->label('موبایل')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('tier.name')
                    ->label('تعرفه')
                    ->placeholder('—'),

                TextColumn::make('city')
                    ->label('شهر')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'contacted' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => RepresentationApplication::STATUSES[$state] ?? $state),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(RepresentationApplication::STATUSES),
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
