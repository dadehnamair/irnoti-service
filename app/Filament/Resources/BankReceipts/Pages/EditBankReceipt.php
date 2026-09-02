<?php

namespace App\Filament\Resources\BankReceipts\Pages;

use App\Filament\Resources\BankReceipts\BankReceiptResource;
use App\Support\BankReceiptService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBankReceipt extends EditRecord
{
    protected static string $resource = BankReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('تأیید و اعمال')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'pending')
                ->action(function () {
                    app(BankReceiptService::class)->approve($this->record, auth()->id(), $this->record->admin_note);
                    Notification::make()->title('فیش تأیید و اعمال شد.')->success()->send();
                    $this->redirect(static::getResource()::getUrl('index'));
                }),

            Action::make('reject')
                ->label('رد')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'pending')
                ->schema([
                    Textarea::make('admin_note')->label('دلیل رد')->required()->rows(2),
                ])
                ->action(function (array $data) {
                    app(BankReceiptService::class)->reject($this->record, auth()->id(), $data['admin_note']);
                    Notification::make()->title('فیش رد شد.')->warning()->send();
                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}
