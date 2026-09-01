<?php

namespace App\Filament\Resources\LineOrders\Pages;

use App\Filament\Resources\LineOrders\LineOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLineOrder extends EditRecord
{
    protected static string $resource = LineOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
