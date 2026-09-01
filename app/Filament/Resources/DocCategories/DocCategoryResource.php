<?php

namespace App\Filament\Resources\DocCategories;

use App\Filament\Resources\DocCategories\Pages\CreateDocCategory;
use App\Filament\Resources\DocCategories\Pages\EditDocCategory;
use App\Filament\Resources\DocCategories\Pages\ListDocCategories;
use App\Filament\Resources\DocCategories\Schemas\DocCategoryForm;
use App\Filament\Resources\DocCategories\Tables\DocCategoriesTable;
use App\Models\DocCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DocCategoryResource extends Resource
{
    protected static ?string $model = DocCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\UnitEnum|null $navigationGroup = 'مستندات API';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'دسته‌بندی‌ها';

    protected static ?string $modelLabel = 'دستهٔ مستندات';

    protected static ?string $pluralModelLabel = 'دسته‌بندی‌های مستندات';

    public static function form(Schema $schema): Schema
    {
        return DocCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocCategoriesTable::configure($table);
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
            'index' => ListDocCategories::route('/'),
            'create' => CreateDocCategory::route('/create'),
            'edit' => EditDocCategory::route('/{record}/edit'),
        ];
    }
}
