<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Feature;
use App\Models\User;
use App\Models\UserFeatureOverride;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
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

                Section::make('گروه کاربری و امکانات پنل')
                    ->description('گروه، مجموعه‌ای از امکانات منوی پنل است (docs/starter.md §15). با «استثناها» می‌توان جدا از گروه، امکانی را برای همین کاربر فعال یا محدود کرد.')
                    ->columns(2)
                    ->schema([
                        Select::make('user_group_id')
                            ->label('گروه کاربری')
                            ->relationship('userGroup', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('بدون گروه'),

                        Repeater::make('featureOverrides')
                            ->label('استثناهای این کاربر')
                            ->relationship()
                            ->schema([
                                Select::make('feature_id')
                                    ->label('امکان')
                                    ->relationship('feature', 'label')
                                    ->getOptionLabelFromRecordUsing(fn (Feature $record) => $record->group_label.' › '.$record->label)
                                    ->options(fn () => Feature::query()->ordered()->get()
                                        ->mapWithKeys(fn (Feature $f) => [$f->id => $f->group_label.' › '.$f->label])
                                        ->all())
                                    ->searchable()
                                    ->required(),

                                Select::make('mode')
                                    ->label('نوع')
                                    ->options(UserFeatureOverride::MODES)
                                    ->default('grant')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('افزودن استثنا')
                            ->defaultItems(0)
                            ->columnSpanFull(),
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
                        Select::make('account_type')
                            ->label('نوع حساب')
                            ->options(User::ACCOUNT_TYPES)
                            ->default('individual')
                            ->required()
                            ->live(),

                        TextInput::make('company')
                            ->label(fn ($get) => $get('account_type') === 'legal' ? 'نام کامل شرکت' : 'شرکت'),

                        TextInput::make('first_name')
                            ->label(fn ($get) => $get('account_type') === 'legal' ? 'نام نماینده' : 'نام'),
                        TextInput::make('last_name')
                            ->label(fn ($get) => $get('account_type') === 'legal' ? 'نام خانوادگی نماینده' : 'نام خانوادگی'),

                        TextInput::make('rep_role')
                            ->label('سمت نماینده')
                            ->visible(fn ($get) => $get('account_type') === 'legal'),

                        TextInput::make('email')->label('ایمیل')->email(),
                        TextInput::make('phone')->label('شماره تماس'),
                    ]),

                Section::make('مشخصات و اطلاعات ثبتی شرکت')
                    ->description('فقط برای حساب حقوقی (docs/starter.md §26).')
                    ->columns(2)
                    ->visible(fn ($get) => $get('account_type') === 'legal')
                    ->schema([
                        Select::make('company_type')
                            ->label('نوع شخصیت حقوقی')
                            ->options([
                                'سهامی خاص' => 'سهامی خاص',
                                'سهامی عام' => 'سهامی عام',
                                'مسئولیت محدود' => 'مسئولیت محدود',
                                'تعاونی' => 'تعاونی',
                                'تضامنی' => 'تضامنی',
                                'مؤسسه غیرتجاری' => 'مؤسسه غیرتجاری',
                                'نسبی' => 'نسبی',
                                'دولتی / عمومی' => 'دولتی / عمومی',
                                'سایر' => 'سایر',
                            ]),
                        TextInput::make('company_national_id')->label('شناسه ملی شرکت'),
                        TextInput::make('company_registration_number')->label('شماره ثبت'),
                        TextInput::make('company_registered_at')->label('تاریخ ثبت')->placeholder('۱۴۰۲/۰۵/۱۷'),
                        TextInput::make('company_economic_code')->label('کد اقتصادی'),
                        TextInput::make('company_phone')->label('تلفن شرکت'),
                        TextInput::make('company_postal_code')->label('کد پستی شرکت'),
                        Textarea::make('company_address')->label('نشانی شرکت')->rows(2)->columnSpanFull(),

                        FileUpload::make('company_registration_doc')
                            ->label('آگهی تأسیس / روزنامه رسمی')
                            ->disk('local')
                            ->directory('identity')
                            ->visibility('private')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->openable()
                            ->downloadable(),

                        FileUpload::make('company_changes_doc')
                            ->label('آگهی آخرین تغییرات')
                            ->disk('local')
                            ->directory('identity')
                            ->visibility('private')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->openable()
                            ->downloadable(),

                        FileUpload::make('company_extra_docs')
                            ->label('مدارک اضافه')
                            ->multiple()
                            ->disk('local')
                            ->directory('identity')
                            ->visibility('private')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->openable()
                            ->downloadable()
                            ->columnSpanFull(),
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
                    ->description('اعتبار پنل پیامکِ خودِ کاربر؛ پس از تأیید حساب پر شود تا پنل ما به پنل او وصل شود.')
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
                            ->helperText('به‌صورت خودکار از '.sms_provider_label().' دریافت می‌شود.')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
