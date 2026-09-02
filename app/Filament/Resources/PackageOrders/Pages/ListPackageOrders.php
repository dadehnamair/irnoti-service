<?php

namespace App\Filament\Resources\PackageOrders\Pages;

use App\Filament\Resources\PackageOrders\PackageOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListPackageOrders extends ListRecords
{
    protected static string $resource = PackageOrderResource::class;
}
