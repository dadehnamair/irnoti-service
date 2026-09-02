<?php

namespace App\Filament\Resources\Plans\Schemas;

use App\Models\Plan;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('مشخصات پلن')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('نام پلن')
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
                            ->unique(Plan::class, 'slug', ignoreRecord: true),

                        Textarea::make('description')
                            ->label('توضیح کوتاه')
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('badge_label')
                            ->label('برچسب (Badge)')
                            ->placeholder('پرفروش'),

                        Select::make('badge_style')
                            ->label('رنگ برچسب')
                            ->options([
                                'neutral' => 'خنثی',
                                'primary' => 'اصلی (برند)',
                                'dark' => 'تیره',
                            ])
                            ->default('neutral'),
                    ]),

                Section::make('قیمت‌گذاری (تومان)')
                    ->columns(2)
                    ->schema([
                        TextInput::make('price_monthly')
                            ->label('قیمت ماهانه')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->suffix('تومان'),

                        TextInput::make('compare_at_monthly')
                            ->label('قیمت ماهانه قبل از تخفیف')
                            ->numeric()
                            ->nullable()
                            ->suffix('تومان')
                            ->helperText('اگر پر شود، خط‌خورده کنار قیمت نمایش داده می‌شود.'),

                        TextInput::make('price_yearly')
                            ->label('قیمت سالانه')
                            ->numeric()
                            ->nullable()
                            ->suffix('تومان')
                            ->helperText('خالی بگذارید تا خودکار = ۱۰ برابر ماهانه شود.'),

                        TextInput::make('compare_at_yearly')
                            ->label('قیمت سالانه قبل از تخفیف')
                            ->numeric()
                            ->nullable()
                            ->suffix('تومان'),
                    ]),

                Section::make('سهمیه‌ها')
                    ->columns(4)
                    ->schema([
                        TextInput::make('duration_days')
                            ->label('مدت اعتبار (روز)')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('sms_count')
                            ->label('تعداد پیامک')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('lines_count')
                            ->label('تعداد خط')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('users_count')
                            ->label('تعداد کاربر')
                            ->numeric()
                            ->nullable(),
                    ]),

                Section::make('امکانات و دکمه')
                    ->columns(2)
                    ->schema([
                        TagsInput::make('features')
                            ->label('فهرست امکانات')
                            ->placeholder('یک ویژگی بنویسید و Enter بزنید')
                            ->columnSpanFull(),

                        TextInput::make('cta_label')
                            ->label('متن دکمه')
                            ->default('انتخاب پلن'),

                        Select::make('cta_style')
                            ->label('استایل دکمه')
                            ->options([
                                'btn-primary' => 'اصلی',
                                'btn-secondary' => 'ثانویه',
                            ])
                            ->default('btn-secondary'),

                        TextInput::make('cta_url')
                            ->label('لینک دکمه')
                            ->placeholder('#cta')
                            ->helperText('خالی = #cta'),

                        ColorPicker::make('color')
                            ->label('رنگ اختصاصی (اختیاری)'),
                    ]),

                Section::make('نمایش')
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_featured')
                            ->label('پلن ویژه (برجسته)'),

                        Toggle::make('is_active')
                            ->label('فعال')
                            ->default(true),

                        TextInput::make('sort')
                            ->label('ترتیب نمایش')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),
            ]);
    }
}
