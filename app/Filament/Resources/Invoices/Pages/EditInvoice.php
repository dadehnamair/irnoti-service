<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Support\OperationNotifier;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function afterSave(): void
    {
        $this->record->recalculateTotals();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('issue')
                ->label('صدور صورت‌حساب')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'draft' && $this->record->total > 0)
                ->action(function () {
                    $this->record->forceFill(['status' => 'issued', 'issued_at' => now()])->save();
                    app(OperationNotifier::class)->invoiceIssued($this->record);
                    Notification::make()->title('صورت‌حساب صادر شد و برای مشتری پیامک شد.')->success()->send();
                }),

            DeleteAction::make(),
        ];
    }
}
