<?php

namespace App\Filament\Resources\SmsLines;

use App\Filament\Resources\SmsLines\Pages\CreateSmsLine;
use App\Filament\Resources\SmsLines\Pages\EditSmsLine;
use App\Filament\Resources\SmsLines\Pages\ListSmsLines;
use App\Filament\Resources\SmsLines\Schemas\SmsLineForm;
use App\Filament\Resources\SmsLines\Tables\SmsLinesTable;
use App\Models\SmsLine;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SmsLineResource extends Resource
{
    protected static ?string $model = SmsLine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?string $recordTitleAttribute = 'prefix';

    protected static string|\UnitEnum|null $navigationGroup = 'محتوای سایت';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'خطوط اختصاصی';

    protected static ?string $modelLabel = 'خط';

    protected static ?string $pluralModelLabel = 'خطوط اختصاصی';

    public static function form(Schema $schema): Schema
    {
        return SmsLineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SmsLinesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmsLines::route('/'),
            'create' => CreateSmsLine::route('/create'),
            'edit' => EditSmsLine::route('/{record}/edit'),
        ];
    }
}
