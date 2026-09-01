<?php

namespace App\Filament\Resources\LineOrders;

use App\Filament\Resources\LineOrders\Pages\EditLineOrder;
use App\Filament\Resources\LineOrders\Pages\ListLineOrders;
use App\Filament\Resources\LineOrders\Schemas\LineOrderForm;
use App\Filament\Resources\LineOrders\Tables\LineOrdersTable;
use App\Models\LineOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LineOrderResource extends Resource
{
    protected static ?string $model = LineOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $recordTitleAttribute = 'line_label';

    protected static string|\UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'سفارش‌های خط';

    protected static ?string $modelLabel = 'سفارش خط';

    protected static ?string $pluralModelLabel = 'سفارش‌های خط';

    public static function form(Schema $schema): Schema
    {
        return LineOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LineOrdersTable::configure($table);
    }

    public static function canCreate(): bool
    {
        // Orders are created from the public /lines page — admin only processes them.
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
            'index' => ListLineOrders::route('/'),
            'edit' => EditLineOrder::route('/{record}/edit'),
        ];
    }
}
