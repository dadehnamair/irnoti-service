<?php

namespace App\Filament\Resources\SmsMessages;

use App\Filament\Resources\SmsMessages\Pages\ListSmsMessages;
use App\Filament\Resources\SmsMessages\Tables\SmsMessagesTable;
use App\Models\SmsMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Read-only oversight of the single SMS customers send from the panel
 * (docs/starter.md §12). Sends happen on the customer's own credentials; this is
 * just our log.
 */
class SmsMessageResource extends Resource
{
    protected static ?string $model = SmsMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|\UnitEnum|null $navigationGroup = 'کاربران';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'پیامک‌های کاربران';

    protected static ?string $modelLabel = 'پیامک';

    protected static ?string $pluralModelLabel = 'پیامک‌های کاربران';

    public static function table(Table $table): Table
    {
        return SmsMessagesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmsMessages::route('/'),
        ];
    }
}
