<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('حساب کاربری')
                    ->columns(2)
                    ->schema([
                        TextInput::make('mobile')
                            ->label('موبایل')
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('status')
                            ->label('وضعیت حساب')
                            ->options(User::STATUSES)
                            ->required(),

                        Select::make('plan_id')
                            ->label('پلن')
                            ->relationship('plan', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('بدون پلن'),

                        DateTimePicker::make('plan_expires_at')
                            ->label('انقضای پلن'),

                        DateTimePicker::make('mobile_verified_at')
                            ->label('تأیید موبایل')
                            ->disabled()
                            ->dehydrated(false),

                        DateTimePicker::make('profile_completed_at')
                            ->label('تکمیل اطلاعات')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Section::make('اطلاعات فردی')
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')->label('نام'),
                        TextInput::make('last_name')->label('نام خانوادگی'),
                        TextInput::make('company')->label('شرکت'),
                        TextInput::make('email')->label('ایمیل')->email(),
                        TextInput::make('phone')->label('شماره تماس'),
                    ]),

                Section::make('موقعیت و آدرس')
                    ->columns(2)
                    ->schema([
                        TextInput::make('country')->label('کشور'),
                        TextInput::make('province')->label('استان'),
                        TextInput::make('city')->label('شهر'),
                        TextInput::make('postal_code')->label('کد پستی'),
                        Textarea::make('address')->label('آدرس')->rows(2)->columnSpanFull(),
                        Textarea::make('description')->label('توضیحات')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('احراز هویت')
                    ->columns(2)
                    ->schema([
                        TextInput::make('national_code')->label('کد ملی'),
                        TextInput::make('birth_cert_number')->label('شماره شناسنامه'),

                        FileUpload::make('national_card_image')
                            ->label('تصویر کارت ملی')
                            ->image()
                            ->disk('local')
                            ->directory('identity')
                            ->visibility('private')
                            ->openable()
                            ->downloadable(),

                        FileUpload::make('national_card_back_image')
                            ->label('تصویر پشت کارت ملی')
                            ->image()
                            ->disk('local')
                            ->directory('identity')
                            ->visibility('private')
                            ->openable()
                            ->downloadable(),

                        FileUpload::make('identity_doc_image')
                            ->label('تصویر احراز هویت')
                            ->image()
                            ->disk('local')
                            ->directory('identity')
                            ->visibility('private')
                            ->openable()
                            ->downloadable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
