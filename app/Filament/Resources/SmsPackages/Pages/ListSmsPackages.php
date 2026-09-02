<?php

namespace App\Filament\Resources\SmsPackages\Pages;

use App\Filament\Resources\SmsPackages\SmsPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSmsPackages extends ListRecords
{
    protected static string $resource = SmsPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
