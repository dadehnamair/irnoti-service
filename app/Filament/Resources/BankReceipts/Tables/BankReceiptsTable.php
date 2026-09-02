<?php

namespace App\Filament\Resources\BankReceipts\Tables;

use App\Models\BankReceipt;
use App\Support\BankReceiptService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BankReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('تاریخ ثبت')->jalaliDateTime()->sortable(),
                TextColumn::make('user.mobile')->label('مشتری')->searchable(),
                TextColumn::make('purpose_label')->label('بابت')->badge(),
                TextColumn::make('amount')->label('مبلغ')->toman()->sortable(),
                TextColumn::make('tracking_code')->label('شماره پیگیری')->searchable()->copyable(),
                TextColumn::make('transfer_type')
                    ->label('نوع انتقال')
                    ->formatStateUsing(fn ($state) => BankReceipt::TRANSFER_TYPES[$state] ?? $state),
                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state) => BankReceipt::STATUSES[$state] ?? $state),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(BankReceipt::STATUSES),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('تأیید')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (BankReceipt $record) => $record->status === 'pending')
                    ->action(function (BankReceipt $record) {
                        app(BankReceiptService::class)->approve($record, auth()->id());
                        Notification::make()->title('فیش تأیید و اعمال شد.')->success()->send();
                    }),

                Action::make('reject')
                    ->label('رد')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (BankReceipt $record) => $record->status === 'pending')
                    ->schema([
                        Textarea::make('admin_note')->label('دلیل رد')->required()->rows(2),
                    ])
                    ->action(function (BankReceipt $record, array $data) {
                        app(BankReceiptService::class)->reject($record, auth()->id(), $data['admin_note']);
                        Notification::make()->title('فیش رد شد.')->warning()->send();
                    }),

                EditAction::make()->label('جزئیات'),
            ]);
    }
}
