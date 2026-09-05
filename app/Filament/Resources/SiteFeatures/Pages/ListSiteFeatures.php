<?php

namespace App\Filament\Resources\SiteFeatures\Pages;

use App\Filament\Resources\SiteFeatures\SiteFeatureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSiteFeatures extends ListRecords
{
    protected static string $resource = SiteFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
