<?php

namespace App\Filament\Resources\MarketplaceApps\Schemas;

use App\Marketplace\AppRegistry;
use App\Models\Feature;
use App\Models\MarketplaceApp;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MarketplaceAppForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معرفی افزونه')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('نام افزونه')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, string $operation) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('نامک (slug)')
                            ->required()
                            ->alphaDash()
                            ->unique(MarketplaceApp::class, 'slug', ignoreRecord: true),

                        TextInput::make('vendor')
                            ->label('ارائه‌دهنده')
                            ->placeholder('ایرپلاس'),

                        Select::make('category')
                            ->label('دسته')
                            ->options(MarketplaceApp::CATEGORIES)
                            ->default('integration')
                            ->required(),

                        TextInput::make('tagline')
                            ->label('توضیح یک‌خطی')
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('توضیح کامل (Markdown)')
                            ->rows(4)
                            ->columnSpanFull(),

                        FileUpload::make('icon')
                            ->label('آیکون / لوگو')
                            ->image()
                            ->disk('public')
                            ->directory('marketplace')
                            ->visibility('public'),

                        ColorPicker::make('accent_color')
                            ->label('رنگ اختصاصی (اختیاری)'),

                        TextInput::make('docs_url')
                            ->label('لینک راهنما')
                            ->url()
                            ->columnSpanFull(),
                    ]),

                Section::make('رفتار')
                    ->columns(2)
                    ->schema([
                        Select::make('handler')
                            ->label('نوع افزونه (Handler)')
                            ->options(fn () => app(AppRegistry::class)->options())
                            ->required()
                            ->live()
                            ->helperText('کلاسی که رفتار افزونه را اجرا می‌کند.'),

                        Select::make('capabilities')
                            ->label('قابلیت‌هایی که باز می‌کند')
                            ->multiple()
                            ->options(fn () => Feature::query()->orderBy('group_label')->orderBy('sort')->pluck('label', 'key'))
                            ->helperText('برای افزونه‌های داخلی (کارت ویزیت، منشی): کلید ردیف‌های منوی پنل که با نصب فعال می‌شوند.'),
                    ]),

                Section::make('قیمت‌گذاری')
                    ->columns(3)
                    ->schema([
                        Select::make('billing_type')
                            ->label('نوع پرداخت')
                            ->options(MarketplaceApp::BILLING_TYPES)
                            ->default('free')
                            ->required()
                            ->live(),

                        TextInput::make('price')
                            ->label('قیمت (تومان)')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->suffix('تومان')
                            ->visible(fn (Get $get) => $get('billing_type') !== 'free'),

                        Select::make('billing_period')
                            ->label('دوره اشتراک')
                            ->options(MarketplaceApp::BILLING_PERIODS)
                            ->visible(fn (Get $get) => $get('billing_type') === 'subscription')
                            ->required(fn (Get $get) => $get('billing_type') === 'subscription'),

                        TextInput::make('trial_days')
                            ->label('روزهای آزمایشی رایگان')
                            ->numeric()
                            ->nullable()
                            ->visible(fn (Get $get) => $get('billing_type') === 'subscription'),
                    ]),

                Section::make('فرم اتصال (فیلدهای اعتبار API)')
                    ->description('برای افزونه‌های اتصال به سرویس بیرونی. کاربر هنگام نصب این فیلدها را پر می‌کند.')
                    ->schema([
                        Repeater::make('config_schema')
                            ->label('')
                            ->addActionLabel('افزودن فیلد')
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? $state['key'] ?? null)
                            ->columns(3)
                            ->schema([
                                TextInput::make('key')->label('کلید')->required()->alphaDash(),
                                TextInput::make('label')->label('عنوان نمایشی')->required(),
                                Select::make('type')->label('نوع')->options([
                                    'text' => 'متن',
                                    'textarea' => 'متن بلند',
                                    'number' => 'عدد',
                                ])->default('text'),
                                Toggle::make('required')->label('اجباری')->inline(false),
                                Toggle::make('secret')->label('محرمانه (رمز)')->inline(false),
                                TextInput::make('help')->label('راهنما')->columnSpanFull(),
                            ]),
                    ]),

                Section::make('نمایش')
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')->label('فعال (نمایش در بازارچه)'),
                        Toggle::make('is_featured')->label('افزونه ویژه'),
                        TextInput::make('sort')->label('ترتیب نمایش')->numeric()->default(0)->required(),
                    ]),
            ]);
    }
}
