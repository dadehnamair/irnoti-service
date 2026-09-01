<?php

namespace App\Filament\Resources\DocArticles\Pages;

use App\Filament\Resources\DocArticles\DocArticleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocArticle extends CreateRecord
{
    protected static string $resource = DocArticleResource::class;
}
