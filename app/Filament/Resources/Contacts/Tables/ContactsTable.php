<?php

namespace App\Filament\Resources\Contacts\Tables;

use App\Models\Contact;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactsTable
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

                TextColumn::make('full_name')
                    ->label('نام مخاطب')
                    ->searchable(['first_name', 'last_name']),

                TextColumn::make('mobile')
                    ->label('موبایل')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('groups.name')
                    ->label('گروه‌ها')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('sync_status')
                    ->label('همگام‌سازی')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'synced' => 'success',
                        'error' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => Contact::SYNC_STATUSES[$state] ?? $state),

                TextColumn::make('created_at')
                    ->label('ایجاد')
                    ->jalaliDateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('sync_status')
                    ->label('همگام‌سازی')
                    ->options(Contact::SYNC_STATUSES),

                SelectFilter::make('groups')
                    ->label('گروه')
                    ->relationship('groups', 'name'),
            ]);
    }
}
