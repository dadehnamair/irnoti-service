<?php

namespace App\Filament\Resources\Features;

use App\Filament\Resources\Features\Pages\EditFeature;
use App\Filament\Resources\Features\Pages\ListFeatures;
use App\Filament\Resources\Features\Schemas\FeatureForm;
use App\Filament\Resources\Features\Tables\FeaturesTable;
use App\Models\Feature;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * The customer dashboard mega-menu catalogue (docs/starter.md §15). Rows are
 * born from the seeder — admins only switch «بزودی» items live and grant them
 * to access groups. See UserGroupResource for the group ↔ feature assignment.
 */
class FeatureResource extends Resource
{
    protected static ?string $model = Feature::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $recordTitleAttribute = 'label';

    protected static string|\UnitEnum|null $navigationGroup = 'کاربران';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'امکانات پنل';

    protected static ?string $modelLabel = 'امکان پنل';

    protected static ?string $pluralModelLabel = 'امکانات پنل';

    public static function form(Schema $schema): Schema
    {
        return FeatureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeaturesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeatures::route('/'),
            'edit' => EditFeature::route('/{record}/edit'),
        ];
    }
}
