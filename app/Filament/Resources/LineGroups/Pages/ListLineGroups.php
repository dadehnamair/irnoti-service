<?php

namespace App\Filament\Resources\LineGroups\Pages;

use App\Filament\Resources\LineGroups\LineGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLineGroups extends ListRecords
{
    protected static string $resource = LineGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
