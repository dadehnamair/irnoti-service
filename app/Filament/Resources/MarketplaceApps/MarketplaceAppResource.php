<?php

namespace App\Filament\Resources\MarketplaceApps;

use App\Filament\Resources\MarketplaceApps\Pages\CreateMarketplaceApp;
use App\Filament\Resources\MarketplaceApps\Pages\EditMarketplaceApp;
use App\Filament\Resources\MarketplaceApps\Pages\ListMarketplaceApps;
use App\Filament\Resources\MarketplaceApps\Schemas\MarketplaceAppForm;
use App\Filament\Resources\MarketplaceApps\Tables\MarketplaceAppsTable;
use App\Models\MarketplaceApp;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/** «بازارچه» catalogue — the add-ons customers can install (docs/starter.md §15). */
class MarketplaceAppResource extends Resource
{
    protected static ?string $model = MarketplaceApp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'بازارچه';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'افزونه‌ها';

    protected static ?string $modelLabel = 'افزونه';

    protected static ?string $pluralModelLabel = 'افزونه‌ها';

    public static function form(Schema $schema): Schema
    {
        return MarketplaceAppForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketplaceAppsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMarketplaceApps::route('/'),
            'create' => CreateMarketplaceApp::route('/create'),
            'edit' => EditMarketplaceApp::route('/{record}/edit'),
        ];
    }
}
