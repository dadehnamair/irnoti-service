<?php

namespace App\Filament\Resources\SmsPackages;

use App\Filament\Resources\SmsPackages\Pages\CreateSmsPackage;
use App\Filament\Resources\SmsPackages\Pages\EditSmsPackage;
use App\Filament\Resources\SmsPackages\Pages\ListSmsPackages;
use App\Filament\Resources\SmsPackages\Schemas\SmsPackageForm;
use App\Filament\Resources\SmsPackages\Tables\SmsPackagesTable;
use App\Models\SmsPackage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/** SMS credit bundles for sale (docs/starter.md §12). */
class SmsPackageResource extends Resource
{
    protected static ?string $model = SmsPackage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'محتوای سایت';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'بسته‌های پیامکی';

    protected static ?string $modelLabel = 'بسته پیامکی';

    protected static ?string $pluralModelLabel = 'بسته‌های پیامکی';

    public static function form(Schema $schema): Schema
    {
        return SmsPackageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SmsPackagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmsPackages::route('/'),
            'create' => CreateSmsPackage::route('/create'),
            'edit' => EditSmsPackage::route('/{record}/edit'),
        ];
    }
}
