<?php

namespace App\Filament\Resources\SiteFeatures;

use App\Filament\Resources\SiteFeatures\Pages\CreateSiteFeature;
use App\Filament\Resources\SiteFeatures\Pages\EditSiteFeature;
use App\Filament\Resources\SiteFeatures\Pages\ListSiteFeatures;
use App\Filament\Resources\SiteFeatures\Schemas\SiteFeatureForm;
use App\Filament\Resources\SiteFeatures\Tables\SiteFeaturesTable;
use App\Models\SiteFeature;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * The public marketing "امکانات" catalogue (SiteFeature) — feeds the landing
 * page's #features teaser and the standalone /features showcase page.
 * Distinct from FeatureResource, which drives the dashboard sidebar.
 */
class SiteFeatureResource extends Resource
{
    protected static ?string $model = SiteFeature::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\UnitEnum|null $navigationGroup = 'محتوای سایت';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'امکانات سایت';

    protected static ?string $modelLabel = 'امکان';

    protected static ?string $pluralModelLabel = 'امکانات سایت';

    public static function form(Schema $schema): Schema
    {
        return SiteFeatureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SiteFeaturesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSiteFeatures::route('/'),
            'create' => CreateSiteFeature::route('/create'),
            'edit' => EditSiteFeature::route('/{record}/edit'),
        ];
    }
}
