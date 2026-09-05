<?php

namespace App\Filament\Resources\SiteFeatures\Pages;

use App\Filament\Resources\SiteFeatures\SiteFeatureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSiteFeature extends EditRecord
{
    protected static string $resource = SiteFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
