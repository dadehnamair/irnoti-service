<?php

namespace App\Filament\Resources\SmsLines\Pages;

use App\Filament\Resources\SmsLines\SmsLineResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSmsLine extends EditRecord
{
    protected static string $resource = SmsLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
