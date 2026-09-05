<?php

namespace App\Filament\Resources\RepresentationApplications\Pages;

use App\Filament\Resources\RepresentationApplications\RepresentationApplicationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRepresentationApplication extends EditRecord
{
    protected static string $resource = RepresentationApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
