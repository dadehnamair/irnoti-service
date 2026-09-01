<?php

namespace App\Filament\Resources\LineOrders\Pages;

use App\Filament\Resources\LineOrders\LineOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListLineOrders extends ListRecords
{
    protected static string $resource = LineOrderResource::class;
}
