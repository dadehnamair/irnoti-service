<?php

namespace App\Filament\Resources\RepresentationTiers\Schemas;

use App\Models\RepresentationTier;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RepresentationTierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('نام تعرفه')
                            ->required()
                            ->maxLength(150)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation) {
                                if ($operation === 'create' || blank($get('slug'))) {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('نامک (slug)')
                            ->required()
                            ->alphaDash()
                            ->unique(RepresentationTier::class, 'slug', ignoreRecord: true),

                        TextInput::make('tagline')
                            ->label('عنوان فرعی')
                            ->maxLength(150)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('توضیح')
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('investment_amount')
                            ->label('سرمایه لازم (تومان)')
                            ->numeric()
                            ->placeholder('خالی = بدون نیاز به سرمایه'),

                        TextInput::make('commission_percent')
                            ->label('درصد کمیسیون')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required()
                            ->suffix('٪'),

                        TagsInput::make('benefits')
                            ->label('مزایا')
                            ->placeholder('هر مزیت را بنویسید و اینتر بزنید')
                            ->columnSpanFull(),

                        Textarea::make('requirements')
                            ->label('شرایط لازم')
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('sort')
                            ->label('ترتیب')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Toggle::make('is_featured')
                            ->label('پیشنهادی'),

                        Toggle::make('is_active')
                            ->label('فعال')
                            ->default(true),
                    ]),
            ]);
    }
}
