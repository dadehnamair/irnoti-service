<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Support\OperationNotifier;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approveDocuments')
                ->label('تأیید مدارک')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => $this->record->documents_status !== 'approved')
                ->requiresConfirmation()
                ->action(function (OperationNotifier $notifier) {
                    $this->record->forceFill([
                        'documents_status' => 'approved',
                        'documents_reviewed_at' => now(),
                        'documents_reject_reason' => null,
                    ])->save();

                    $notifier->documentsApproved($this->record);
                    $this->refreshFormData(['documents_status', 'documents_reviewed_at', 'documents_reject_reason']);
                    Notification::make()->title('مدارک تأیید شد.')->success()->send();
                }),

            Action::make('rejectDocuments')
                ->label('رد مدارک')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->documents_status !== 'rejected')
                ->form([
                    Textarea::make('reason')->label('دلیل رد')->required()->rows(2),
                ])
                ->action(function (array $data, OperationNotifier $notifier) {
                    $this->record->forceFill([
                        'documents_status' => 'rejected',
                        'documents_reviewed_at' => now(),
                        'documents_reject_reason' => $data['reason'],
                    ])->save();

                    $notifier->documentsRejected($this->record, $data['reason']);
                    $this->refreshFormData(['documents_status', 'documents_reviewed_at', 'documents_reject_reason']);
                    Notification::make()->title('مدارک رد شد.')->warning()->send();
                }),

            Action::make('approveAccount')
                ->label('تأیید حساب')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->visible(fn () => ! $this->record->isApproved())
                ->disabled(fn () => $this->record->documents_status !== 'approved')
                ->tooltip(fn () => $this->record->documents_status !== 'approved' ? 'ابتدا مدارک را تأیید کنید.' : null)
                ->requiresConfirmation()
                ->modalDescription('حساب فعال می‌شود و کاربر به همهٔ امکانات پنل دسترسی پیدا می‌کند.')
                ->action(function (OperationNotifier $notifier) {
                    $this->record->forceFill([
                        'status' => 'active',
                        'approved_at' => now(),
                    ])->save();

                    $notifier->accountApproved($this->record);
                    $this->refreshFormData(['status', 'approved_at']);
                    Notification::make()->title('حساب تأیید و فعال شد.')->success()->send();
                }),

            DeleteAction::make(),
        ];
    }
}
