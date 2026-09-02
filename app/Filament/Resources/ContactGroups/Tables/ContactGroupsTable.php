<?php

namespace App\Filament\Resources\ContactGroups\Tables;

use App\Models\ContactGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('کاربر')
                    ->searchable(['first_name', 'last_name', 'name'])
                    ->placeholder('—'),

                TextColumn::make('name')
                    ->label('نام گروه')
                    ->searchable(),

                TextColumn::make('contacts_count')
                    ->label('مخاطبین')
                    ->counts('contacts')
                    ->alignCenter(),

                TextColumn::make('sync_status')
                    ->label('همگام‌سازی')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'synced' => 'success',
                        'error' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ContactGroup::SYNC_STATUSES[$state] ?? $state),

                TextColumn::make('synced_at')
                    ->label('آخرین همگام‌سازی')
                    ->jalaliDateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('sync_status')
                    ->label('همگام‌سازی')
                    ->options(ContactGroup::SYNC_STATUSES),
            ]);
    }
}
