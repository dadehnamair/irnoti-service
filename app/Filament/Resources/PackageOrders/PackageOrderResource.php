<?php

namespace App\Filament\Resources\PackageOrders;

use App\Filament\Resources\PackageOrders\Pages\EditPackageOrder;
use App\Filament\Resources\PackageOrders\Pages\ListPackageOrders;
use App\Filament\Resources\PackageOrders\Schemas\PackageOrderForm;
use App\Filament\Resources\PackageOrders\Tables\PackageOrdersTable;
use App\Models\PackageOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/** SMS bundle purchases (docs/starter.md §12). Created from the customer panel. */
class PackageOrderResource extends Resource
{
    protected static ?string $model = PackageOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $recordTitleAttribute = 'package_name';

    protected static string|\UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'خرید بسته پیامکی';

    protected static ?string $modelLabel = 'سفارش بسته';

    protected static ?string $pluralModelLabel = 'سفارش‌های بسته';

    public static function form(Schema $schema): Schema
    {
        return PackageOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PackageOrdersTable::configure($table);
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
            'index' => ListPackageOrders::route('/'),
            'edit' => EditPackageOrder::route('/{record}/edit'),
        ];
    }
}
