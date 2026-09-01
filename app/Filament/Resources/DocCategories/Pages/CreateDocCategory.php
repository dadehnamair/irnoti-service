<?php

namespace App\Filament\Resources\DocCategories\Pages;

use App\Filament\Resources\DocCategories\DocCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocCategory extends CreateRecord
{
    protected static string $resource = DocCategoryResource::class;
}
