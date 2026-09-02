<?php

namespace App\Filament\Resources\BankReceipts;

use App\Filament\Resources\BankReceipts\Pages\EditBankReceipt;
use App\Filament\Resources\BankReceipts\Pages\ListBankReceipts;
use App\Filament\Resources\BankReceipts\Schemas\BankReceiptForm;
use App\Filament\Resources\BankReceipts\Tables\BankReceiptsTable;
use App\Models\BankReceipt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Bank receipts submitted by customers (docs/starter.md §22). Reviewed here —
 * "تأیید" runs the matching domain effect once via BankReceiptService.
 */
class BankReceiptResource extends Resource
{
    protected static ?string $model = BankReceipt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?string $recordTitleAttribute = 'tracking_code';

    protected static string|\UnitEnum|null $navigationGroup = 'مالی';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'فیش‌های بانکی';

    protected static ?string $modelLabel = 'فیش بانکی';

    protected static ?string $pluralModelLabel = 'فیش‌های بانکی';

    public static function form(Schema $schema): Schema
    {
        return BankReceiptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankReceiptsTable::configure($table);
    }

    public static function canCreate(): bool
    {
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
            'index' => ListBankReceipts::route('/'),
            'edit' => EditBankReceipt::route('/{record}/edit'),
        ];
    }
}
