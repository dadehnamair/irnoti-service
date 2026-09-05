<?php

namespace App\Filament\Resources\LineBundles\Pages;

use App\Filament\Resources\LineBundles\LineBundleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLineBundles extends ListRecords
{
    protected static string $resource = LineBundleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
