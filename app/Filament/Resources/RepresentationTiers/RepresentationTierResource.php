<?php

namespace App\Filament\Resources\RepresentationTiers;

use App\Filament\Resources\RepresentationTiers\Pages\CreateRepresentationTier;
use App\Filament\Resources\RepresentationTiers\Pages\EditRepresentationTier;
use App\Filament\Resources\RepresentationTiers\Pages\ListRepresentationTiers;
use App\Filament\Resources\RepresentationTiers\Schemas\RepresentationTierForm;
use App\Filament\Resources\RepresentationTiers\Tables\RepresentationTiersTable;
use App\Models\RepresentationTier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Admin-defined sales-representation tiers shown on the public /representation
 * page. See docs/sales-representation.md.
 */
class RepresentationTierResource extends Resource
{
    protected static ?string $model = RepresentationTier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'تعرفه‌های نمایندگی';

    protected static ?string $modelLabel = 'تعرفه نمایندگی';

    protected static ?string $pluralModelLabel = 'تعرفه‌های نمایندگی';

    public static function form(Schema $schema): Schema
    {
        return RepresentationTierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RepresentationTiersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRepresentationTiers::route('/'),
            'create' => CreateRepresentationTier::route('/create'),
            'edit' => EditRepresentationTier::route('/{record}/edit'),
        ];
    }
}
