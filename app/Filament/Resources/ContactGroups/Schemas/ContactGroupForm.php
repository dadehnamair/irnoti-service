<?php

namespace App\Filament\Resources\ContactGroups\Schemas;

use App\Models\ContactGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('گروه')
                    ->columns(2)
                    ->schema([
                        TextInput::make('user.full_name')->label('کاربر')->disabled()->dehydrated(false),
                        TextInput::make('name')->label('نام گروه')->required(),
                        TextInput::make('description')->label('توضیحات')->columnSpanFull(),
                        Toggle::make('show_to_child')->label('نمایش به زیرمجموعه‌ها'),
                    ]),

                Section::make('همگام‌سازی با ملی‌پیامک')
                    ->columns(2)
                    ->schema([
                        TextInput::make('sync_status')
                            ->label('وضعیت')
                            ->formatStateUsing(fn (?string $state) => ContactGroup::SYNC_STATUSES[$state] ?? $state)
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('remote_id')->label('شناسهٔ ملی‌پیامک')->numeric()->disabled()->dehydrated(false)->placeholder('—'),
                        TextInput::make('synced_at')->label('آخرین همگام‌سازی')->disabled()->dehydrated(false)->placeholder('—'),
                        Textarea::make('sync_error')->label('خطای همگام‌سازی')->rows(2)->disabled()->dehydrated(false)->placeholder('—'),
                    ]),
            ]);
    }
}
