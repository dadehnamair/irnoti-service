<?php

namespace App\Filament\Resources\Domains\Pages;

use App\Filament\Resources\Domains\DomainResource;
use App\Models\BusinessCard;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;

class EditDomain extends EditRecord
{
    protected static string $resource = DomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function () {
                    if (BusinessCard::query()->where('domain_id', $this->record->id)->exists()) {
                        Notification::make()
                            ->danger()
                            ->title('این دامنه دارای کارت ویزیت فعال است و قابل حذف نیست.')
                            ->send();

                        throw new Halt;
                    }
                }),
        ];
    }
}
