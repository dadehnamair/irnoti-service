<?php

namespace App\Filament\Resources\LineGroups;

use App\Filament\Resources\LineGroups\Pages\CreateLineGroup;
use App\Filament\Resources\LineGroups\Pages\EditLineGroup;
use App\Filament\Resources\LineGroups\Pages\ListLineGroups;
use App\Filament\Resources\LineGroups\Schemas\LineGroupForm;
use App\Filament\Resources\LineGroups\Tables\LineGroupsTable;
use App\Models\LineGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LineGroupResource extends Resource
{
    protected static ?string $model = LineGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\UnitEnum|null $navigationGroup = 'محتوای سایت';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'صفحه‌های خطوط';

    protected static ?string $modelLabel = 'صفحهٔ خط';

    protected static ?string $pluralModelLabel = 'صفحه‌های خطوط';

    public static function form(Schema $schema): Schema
    {
        return LineGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LineGroupsTable::configure($table);
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
            'index' => ListLineGroups::route('/'),
            'create' => CreateLineGroup::route('/create'),
            'edit' => EditLineGroup::route('/{record}/edit'),
        ];
    }
}
