<?php

namespace App\Filament\Resources\DocArticles\Pages;

use App\Filament\Resources\DocArticles\DocArticleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDocArticle extends EditRecord
{
    protected static string $resource = DocArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
