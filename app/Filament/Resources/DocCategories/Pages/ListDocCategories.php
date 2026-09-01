<?php

namespace App\Filament\Resources\DocCategories\Pages;

use App\Filament\Resources\DocCategories\DocCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocCategories extends ListRecords
{
    protected static string $resource = DocCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
