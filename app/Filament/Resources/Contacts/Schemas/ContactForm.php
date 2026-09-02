<?php

namespace App\Filament\Resources\Contacts\Schemas;

use App\Models\Contact;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('مخاطب')
                    ->columns(2)
                    ->schema([
                        TextInput::make('user.full_name')->label('کاربر')->disabled()->dehydrated(false),

                        TextInput::make('mobile')->label('موبایل')->required(),
                        TextInput::make('first_name')->label('نام'),
                        TextInput::make('last_name')->label('نام خانوادگی'),
                        TextInput::make('nickname')->label('نام مستعار'),
                        TextInput::make('company')->label('شرکت'),
                        TextInput::make('email')->label('ایمیل')->email(),

                        Select::make('gender')
                            ->label('جنسیت')
                            ->options(Contact::GENDERS),

                        DatePicker::make('birth_date')->label('تاریخ تولد'),

                        Select::make('groups')
                            ->label('گروه‌ها')
                            ->relationship('groups', 'name')
                            ->multiple()
                            ->preload()
                            ->columnSpanFull(),

                        Textarea::make('description')->label('توضیحات')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('همگام‌سازی با '.sms_provider_label())
                    ->columns(2)
                    ->schema([
                        Select::make('sync_status')
                            ->label('وضعیت')
                            ->options(Contact::SYNC_STATUSES),

                        TextInput::make('remote_id')->label('شناسهٔ سامانه')->numeric()->disabled()->dehydrated(false)->placeholder('—'),
                        TextInput::make('synced_at')->label('آخرین همگام‌سازی')->disabled()->dehydrated(false)->placeholder('—'),
                        Textarea::make('sync_error')->label('خطای همگام‌سازی')->rows(2)->disabled()->dehydrated(false)->placeholder('—'),
                    ]),
            ]);
    }
}
