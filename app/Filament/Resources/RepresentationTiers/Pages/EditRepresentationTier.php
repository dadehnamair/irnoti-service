<?php

namespace App\Filament\Resources\RepresentationTiers\Pages;

use App\Filament\Resources\RepresentationTiers\RepresentationTierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRepresentationTier extends EditRecord
{
    protected static string $resource = RepresentationTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
