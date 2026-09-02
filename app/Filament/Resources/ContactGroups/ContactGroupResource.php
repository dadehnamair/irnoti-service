<?php

namespace App\Filament\Resources\ContactGroups;

use App\Filament\Resources\ContactGroups\Pages\EditContactGroup;
use App\Filament\Resources\ContactGroups\Pages\ListContactGroups;
use App\Filament\Resources\ContactGroups\Schemas\ContactGroupForm;
use App\Filament\Resources\ContactGroups\Tables\ContactGroupsTable;
use App\Models\ContactGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Read-mostly oversight of customer phonebook groups (docs/starter.md §17).
 * Created from the customer panel and mirrored to each customer's own
 * Melipayamak account.
 */
class ContactGroupResource extends Resource
{
    protected static ?string $model = ContactGroup::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'کاربران';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'گروه‌های مخاطبین';

    protected static ?string $modelLabel = 'گروه مخاطبین';

    protected static ?string $pluralModelLabel = 'گروه‌های مخاطبین';

    public static function form(Schema $schema): Schema
    {
        return ContactGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactGroupsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactGroups::route('/'),
            'edit' => EditContactGroup::route('/{record}/edit'),
        ];
    }
}
