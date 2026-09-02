<?php

namespace App\Filament\Resources\MarketplaceInstallations;

use App\Filament\Resources\MarketplaceInstallations\Pages\EditMarketplaceInstallation;
use App\Filament\Resources\MarketplaceInstallations\Pages\ListMarketplaceInstallations;
use App\Filament\Resources\MarketplaceInstallations\Schemas\MarketplaceInstallationForm;
use App\Filament\Resources\MarketplaceInstallations\Tables\MarketplaceInstallationsTable;
use App\Models\MarketplaceInstallation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Customer installations of «بازارچه» add-ons (docs/starter.md §15). Born on the
 * public site — the admin only reviews, suspends, or extends them.
 */
class MarketplaceInstallationResource extends Resource
{
    protected static ?string $model = MarketplaceInstallation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'token';

    protected static string|\UnitEnum|null $navigationGroup = 'بازارچه';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'نصب‌ها';

    protected static ?string $modelLabel = 'نصب افزونه';

    protected static ?string $pluralModelLabel = 'نصب‌های افزونه';

    public static function form(Schema $schema): Schema
    {
        return MarketplaceInstallationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketplaceInstallationsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::query()
            ->whereIn('status', ['pending', 'awaiting_payment'])
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMarketplaceInstallations::route('/'),
            'edit' => EditMarketplaceInstallation::route('/{record}/edit'),
        ];
    }
}
