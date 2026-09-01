<?php

namespace App\Filament\Resources\DocArticles;

use App\Filament\Resources\DocArticles\Pages\CreateDocArticle;
use App\Filament\Resources\DocArticles\Pages\EditDocArticle;
use App\Filament\Resources\DocArticles\Pages\ListDocArticles;
use App\Filament\Resources\DocArticles\Schemas\DocArticleForm;
use App\Filament\Resources\DocArticles\Tables\DocArticlesTable;
use App\Models\DocArticle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DocArticleResource extends Resource
{
    protected static ?string $model = DocArticle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\UnitEnum|null $navigationGroup = 'مستندات API';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'مقاله‌ها';

    protected static ?string $modelLabel = 'مقالهٔ مستندات';

    protected static ?string $pluralModelLabel = 'مقاله‌های مستندات';

    public static function form(Schema $schema): Schema
    {
        return DocArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocArticlesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocArticles::route('/'),
            'create' => CreateDocArticle::route('/create'),
            'edit' => EditDocArticle::route('/{record}/edit'),
        ];
    }
}
