<?php

namespace App\Filament\Resources\RepresentationTiers\Pages;

use App\Filament\Resources\RepresentationTiers\RepresentationTierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRepresentationTiers extends ListRecords
{
    protected static string $resource = RepresentationTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
