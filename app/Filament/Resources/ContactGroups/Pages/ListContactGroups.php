<?php

namespace App\Filament\Resources\ContactGroups\Pages;

use App\Filament\Resources\ContactGroups\ContactGroupResource;
use Filament\Resources\Pages\ListRecords;

class ListContactGroups extends ListRecords
{
    protected static string $resource = ContactGroupResource::class;
}
