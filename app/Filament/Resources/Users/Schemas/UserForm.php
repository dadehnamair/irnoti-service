<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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

                Section::make('تأیید حساب')
                    ->description('حساب پس از تأیید مدارک و تأیید نهایی، امکانات پنل را باز می‌کند (docs/starter.md §39).')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('approved_at')
                            ->label('زمان تأیید حساب')
                            ->helperText('برای تأیید، از دکمهٔ «تأیید حساب» بالای صفحه استفاده کنید.'),

                        Select::make('documents_status')
                            ->label('وضعیت مدارک')
                            ->options(User::DOCUMENT_STATUSES)
                            ->default('pending')
                            ->required()
                            ->live(),

                        DateTimePicker::make('documents_reviewed_at')
                            ->label('زمان بررسی مدارک')
                            ->disabled()
                            ->dehydrated(false),

                        Textarea::make('documents_reject_reason')
                            ->label('دلیل رد مدارک')
                            ->rows(2)
                            ->visible(fn ($get) => $get('documents_status') === 'rejected')
                            ->columnSpanFull(),
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

                Section::make('احراز هویت و مدارک')
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

                Section::make('پنل پیامک کاربر')
                    ->description('اعتبار پنل ملی‌پیامکِ خودِ کاربر؛ پس از تأیید حساب پر شود تا پنل ما به پنل او وصل شود.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('sms_username')
                            ->label('نام کاربری پنل')
                            ->autocomplete(false),

                        TextInput::make('sms_password')
                            ->label('رمز عبور پنل')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->dehydrated(fn ($state) => filled($state))
                            ->helperText('برای حفظ رمز فعلی خالی بگذارید.'),

                        TextInput::make('sms_sender')
                            ->label('سرشمارهٔ پیش‌فرض')
                            ->placeholder('مثلاً 3000xxxx')
                            ->helperText('خط فرستندهٔ پیش‌فرض؛ کاربر خودش می‌تواند از پنل تغییرش دهد.'),

                        TagsInput::make('sms_numbers')
                            ->label('سرشماره‌های شناخته‌شده')
                            ->helperText('به‌صورت خودکار از ملی‌پیامک دریافت می‌شود.')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
