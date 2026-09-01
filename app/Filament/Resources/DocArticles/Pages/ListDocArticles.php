<?php

namespace App\Filament\Resources\DocArticles\Pages;

use App\Filament\Resources\DocArticles\DocArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocArticles extends ListRecords
{
    protected static string $resource = DocArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
