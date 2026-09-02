<?php

namespace App\Filament\Resources\UserGroups;

use App\Filament\Resources\UserGroups\Pages\CreateUserGroup;
use App\Filament\Resources\UserGroups\Pages\EditUserGroup;
use App\Filament\Resources\UserGroups\Pages\ListUserGroups;
use App\Filament\Resources\UserGroups\Schemas\UserGroupForm;
use App\Filament\Resources\UserGroups\Tables\UserGroupsTable;
use App\Models\UserGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Customer access groups (docs/starter.md §15) — a group is a bundle of panel
 * features. Assign a group to a user from UserResource; fine-tune per user with
 * the «استثناها» repeater there.
 */
class UserGroupResource extends Resource
{
    protected static ?string $model = UserGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'کاربران';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'گروه‌های کاربری';

    protected static ?string $modelLabel = 'گروه کاربری';

    protected static ?string $pluralModelLabel = 'گروه‌های کاربری';

    public static function form(Schema $schema): Schema
    {
        return UserGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserGroupsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserGroups::route('/'),
            'create' => CreateUserGroup::route('/create'),
            'edit' => EditUserGroup::route('/{record}/edit'),
        ];
    }
}
