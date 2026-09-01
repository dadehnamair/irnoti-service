<?php

namespace App\Filament\Resources\SmsLines\Pages;

use App\Filament\Resources\SmsLines\SmsLineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSmsLines extends ListRecords
{
    protected static string $resource = SmsLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
