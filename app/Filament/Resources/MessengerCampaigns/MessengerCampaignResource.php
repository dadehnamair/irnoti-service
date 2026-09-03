<?php

namespace App\Filament\Resources\MessengerCampaigns;

use App\Filament\Resources\MessengerCampaigns\Pages\ListMessengerCampaigns;
use App\Filament\Resources\MessengerCampaigns\Tables\MessengerCampaignsTable;
use App\Models\MessengerCampaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;

/**
 * Read-only oversight of bulk messenger sends — بله / ایتا / واتساپ
 * (docs/starter.md §91). Campaigns are born on the customer dashboard; this is
 * just our log of what went out, how much of it succeeded, and the wallet cost.
 */
class MessengerCampaignResource extends Resource
{
    protected static ?string $model = MessengerCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string|\UnitEnum|null $navigationGroup = 'پیام‌رسان‌ها';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'کمپین‌های پیام‌رسان';

    protected static ?string $modelLabel = 'کمپین';

    protected static ?string $pluralModelLabel = 'کمپین‌های پیام‌رسان';

    public static function table(Table $table): Table
    {
        return MessengerCampaignsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessengerCampaigns::route('/'),
        ];
    }
}
