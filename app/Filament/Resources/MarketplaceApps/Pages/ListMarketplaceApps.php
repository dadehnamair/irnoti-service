<?php

namespace App\Filament\Resources\MarketplaceApps\Pages;

use App\Filament\Resources\MarketplaceApps\MarketplaceAppResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarketplaceApps extends ListRecords
{
    protected static string $resource = MarketplaceAppResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
