<?php

namespace App\Filament\Resources\LineBundles\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LineBundleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('باندل')
                    ->columns(2)
                    ->schema([
                        Select::make('line_group_id')
                            ->label('صفحهٔ خط')
                            ->relationship('group', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('sms_line_id')
                            ->label('گونهٔ خط (اختیاری)')
                            ->relationship('smsLine', 'prefix')
                            ->getOptionLabelFromRecordUsing(fn ($record) => trim($record->prefix.' — '.$record->digits.' رقمی'.($record->is_rond ? ' — رند' : '')))
                            ->searchable()
                            ->nullable()
                            ->helperText('اگر خالی باشد، باندل عمومی همین پیش‌شماره است.'),

                        TextInput::make('title')
                            ->label('عنوان')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, string $operation) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('نامک (slug)')
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true),

                        Textarea::make('description')
                            ->label('توضیح کوتاه')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('محتوای باندل')
                    ->columns(2)
                    ->schema([
                        TextInput::make('sms_credit')
                            ->label('اعتبار پیامک (عدد)')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        TextInput::make('validity_days')
                            ->label('مدت اعتبار (روز)')
                            ->numeric()
                            ->nullable(),

                        TagsInput::make('features')
                            ->label('امکانات باندل')
                            ->placeholder('یک ویژگی بنویسید و Enter بزنید')
                            ->columnSpanFull(),
                    ]),

                Section::make('قیمت‌گذاری (تومان)')
                    ->columns(2)
                    ->schema([
                        TextInput::make('price')
                            ->label('قیمت')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->suffix('تومان'),

                        TextInput::make('compare_at_price')
                            ->label('قیمت قبل از تخفیف')
                            ->numeric()
                            ->nullable()
                            ->suffix('تومان'),
                    ]),

                Section::make('نمایش و وضعیت')
                    ->columns(2)
                    ->schema([
                        TextInput::make('badge_label')
                            ->label('برچسب (Badge)')
                            ->placeholder('پرفروش'),

                        TextInput::make('badge_style')
                            ->label('کلاس استایل برچسب (اختیاری)'),

                        TextInput::make('sort')
                            ->label('ترتیب نمایش')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Toggle::make('is_active')
                            ->label('فعال')
                            ->default(true),
                    ]),
            ]);
    }
}
