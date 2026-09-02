<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use App\Support\OperationNotifier;
use App\Support\PayableSettlement;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('number')->label('شماره')->searchable()->copyable(),
                TextColumn::make('user.mobile')->label('مشتری')->searchable(),
                TextColumn::make('title')->label('عنوان')->limit(40)->wrap(),
                TextColumn::make('total')->label('مبلغ')->toman()->sortable(),
                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        'issued', 'awaiting_payment' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => Invoice::STATUSES[$state] ?? $state),
                TextColumn::make('issued_at')->label('تاریخ صدور')->jalaliDate()->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->label('ایجاد')->jalaliDate()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(Invoice::STATUSES),
            ])
            ->recordActions([
                Action::make('issue')
                    ->label('صدور صورت‌حساب')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record) => $record->status === 'draft' && $record->total > 0)
                    ->action(function (Invoice $record) {
                        $record->forceFill(['status' => 'issued', 'issued_at' => now()])->save();
                        app(OperationNotifier::class)->invoiceIssued($record);
                        Notification::make()->title('صورت‌حساب صادر شد و برای مشتری پیامک شد.')->success()->send();
                    }),

                Action::make('markPaid')
                    ->label('ثبت پرداخت دستی')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record) => in_array($record->status, ['issued', 'awaiting_payment'], true))
                    ->action(function (Invoice $record) {
                        app(PayableSettlement::class)->settle($record, ['method' => 'manual']);
                        Notification::make()->title('صورت‌حساب پرداخت‌شده ثبت شد.')->success()->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
