<?php

namespace App\Filament\Resources\SmsPackages\Schemas;

use App\Models\SmsPackage;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class SmsPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('مشخصات بسته')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('نام بسته')
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
                            ->unique(SmsPackage::class, 'slug', ignoreRecord: true),

                        TextInput::make('sms_count')
                            ->label('تعداد پیامک')
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        TextInput::make('badge_label')
                            ->label('برچسب (Badge)')
                            ->placeholder('پرفروش'),

                        Textarea::make('description')
                            ->label('توضیح کوتاه')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('قیمت‌گذاری (تومان)')
                    ->columns(2)
                    ->schema([
                        TextInput::make('price')
                            ->label('قیمت')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->suffix('تومان'),

                        TextInput::make('compare_at_price')
                            ->label('قیمت قبل از تخفیف')
                            ->numeric()
                            ->nullable()
                            ->suffix('تومان')
                            ->helperText('اگر پر شود، خط‌خورده کنار قیمت نمایش داده می‌شود.'),
                    ]),

                Section::make('نمایش')
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_featured')->label('بسته ویژه (برجسته)'),
                        Toggle::make('is_active')->label('فعال')->default(true),
                        TextInput::make('sort')->label('ترتیب نمایش')->numeric()->default(0)->required(),
                    ]),
            ]);
    }
}
