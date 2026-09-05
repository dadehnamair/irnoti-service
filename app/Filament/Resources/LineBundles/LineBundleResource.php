<?php

namespace App\Filament\Resources\LineBundles;

use App\Filament\Resources\LineBundles\Pages\CreateLineBundle;
use App\Filament\Resources\LineBundles\Pages\EditLineBundle;
use App\Filament\Resources\LineBundles\Pages\ListLineBundles;
use App\Filament\Resources\LineBundles\Schemas\LineBundleForm;
use App\Filament\Resources\LineBundles\Tables\LineBundlesTable;
use App\Models\LineBundle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LineBundleResource extends Resource
{
    protected static ?string $model = LineBundle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\UnitEnum|null $navigationGroup = 'محتوای سایت';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'باندل‌های خط';

    protected static ?string $modelLabel = 'باندل خط';

    protected static ?string $pluralModelLabel = 'باندل‌های خط';

    public static function form(Schema $schema): Schema
    {
        return LineBundleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LineBundlesTable::configure($table);
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
            'index' => ListLineBundles::route('/'),
            'create' => CreateLineBundle::route('/create'),
            'edit' => EditLineBundle::route('/{record}/edit'),
        ];
    }
}
