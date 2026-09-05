<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بررسی پیام')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('وضعیت')
                            ->options(\App\Models\ContactMessage::STATUSES)
                            ->required(),

                        Textarea::make('admin_note')
                            ->label('یادداشت داخلی')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('اطلاعات فرستنده')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('نام و نام‌خانوادگی')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('mobile')
                            ->label('موبایل')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('email')
                            ->label('ایمیل')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('—'),

                        TextInput::make('subject')
                            ->label('موضوع')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('—'),

                        Textarea::make('message')
                            ->label('متن پیام')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
