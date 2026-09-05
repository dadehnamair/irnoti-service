<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Models\ContactMessage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactMessagesTable
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

                TextColumn::make('name')
                    ->label('نام')
                    ->searchable(),

                TextColumn::make('mobile')
                    ->label('موبایل')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('subject')
                    ->label('موضوع')
                    ->placeholder('—')
                    ->limit(40),

                TextColumn::make('message')
                    ->label('پیام')
                    ->limit(60)
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'replied' => 'success',
                        'read' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ContactMessage::STATUSES[$state] ?? $state),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(ContactMessage::STATUSES),
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
