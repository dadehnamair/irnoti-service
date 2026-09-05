<?php

namespace App\Filament\Resources\BusinessCards;

use App\Filament\Resources\BusinessCards\Pages\EditBusinessCard;
use App\Filament\Resources\BusinessCards\Pages\ListBusinessCards;
use App\Filament\Resources\BusinessCards\Schemas\BusinessCardForm;
use App\Filament\Resources\BusinessCards\Tables\BusinessCardsTable;
use App\Models\BusinessCard;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BusinessCardResource extends Resource
{
    protected static ?string $model = BusinessCard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $recordTitleAttribute = 'code';

    protected static string|\UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'کارت‌های ویزیت دیجیتال';

    protected static ?string $modelLabel = 'کارت ویزیت';

    protected static ?string $pluralModelLabel = 'کارت‌های ویزیت دیجیتال';

    public static function form(Schema $schema): Schema
    {
        return BusinessCardForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BusinessCardsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        // Cards are created from the customer dashboard — admin only oversees them.
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::query()->where('status', 'awaiting_payment')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusinessCards::route('/'),
            'edit' => EditBusinessCard::route('/{record}/edit'),
        ];
    }
}
