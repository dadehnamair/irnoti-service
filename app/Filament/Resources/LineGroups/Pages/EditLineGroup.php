<?php

namespace App\Filament\Resources\LineGroups\Pages;

use App\Filament\Resources\LineGroups\LineGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLineGroup extends EditRecord
{
    protected static string $resource = LineGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
