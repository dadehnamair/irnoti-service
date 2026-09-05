<?php

namespace App\Filament\Resources\RepresentationApplications;

use App\Filament\Resources\RepresentationApplications\Pages\EditRepresentationApplication;
use App\Filament\Resources\RepresentationApplications\Pages\ListRepresentationApplications;
use App\Filament\Resources\RepresentationApplications\Schemas\RepresentationApplicationForm;
use App\Filament\Resources\RepresentationApplications\Tables\RepresentationApplicationsTable;
use App\Models\RepresentationApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Leads submitted from the public /representation form — reviewed manually
 * by the admin. See docs/sales-representation.md.
 */
class RepresentationApplicationResource extends Resource
{
    protected static ?string $model = RepresentationApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static string|\UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'درخواست‌های نمایندگی';

    protected static ?string $modelLabel = 'درخواست نمایندگی';

    protected static ?string $pluralModelLabel = 'درخواست‌های نمایندگی';

    public static function form(Schema $schema): Schema
    {
        return RepresentationApplicationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RepresentationApplicationsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        // Applications are created from the public /representation form only.
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::query()->where('status', 'pending')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRepresentationApplications::route('/'),
            'edit' => EditRepresentationApplication::route('/{record}/edit'),
        ];
    }
}
