<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\EditContactMessage;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Schemas\ContactMessageForm;
use App\Filament\Resources\ContactMessages\Tables\ContactMessagesTable;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Submissions from the public /contact form. Born on the public site; only
 * ever reviewed (never created) from the admin panel.
 */
class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'پیام‌های تماس';

    protected static ?string $modelLabel = 'پیام تماس';

    protected static ?string $pluralModelLabel = 'پیام‌های تماس';

    public static function form(Schema $schema): Schema
    {
        return ContactMessageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactMessagesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        // Messages are created from the public /contact form only.
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $new = static::getModel()::query()->where('status', 'new')->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
            'edit' => EditContactMessage::route('/{record}/edit'),
        ];
    }
}
