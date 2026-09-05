<?php

namespace App\Filament\Resources\BusinessCards\Pages;

use App\Filament\Resources\BusinessCards\BusinessCardResource;
use Filament\Resources\Pages\ListRecords;

class ListBusinessCards extends ListRecords
{
    protected static string $resource = BusinessCardResource::class;
}
