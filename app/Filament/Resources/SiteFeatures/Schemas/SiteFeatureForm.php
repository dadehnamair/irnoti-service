<?php

namespace App\Filament\Resources\SiteFeatures\Schemas;

use App\Models\SiteFeature;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiteFeatureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان')
                            ->required()
                            ->maxLength(150),

                        Select::make('category')
                            ->label('دسته‌بندی')
                            ->options(SiteFeature::CATEGORIES)
                            ->required(),

                        TextInput::make('tagline')
                            ->label('عنوان فرعی')
                            ->maxLength(150)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('توضیح')
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('icon')
                            ->label('آیکون (اموجی)')
                            ->maxLength(10)
                            ->placeholder('🧩'),

                        TextInput::make('badge')
                            ->label('برچسب')
                            ->maxLength(40)
                            ->placeholder('جدید'),

                        TextInput::make('href')
                            ->label('لینک اطلاعات بیشتر')
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('sort')
                            ->label('ترتیب')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Toggle::make('is_featured')
                            ->label('نمایش ویژه (spotlight)'),

                        Toggle::make('is_active')
                            ->label('فعال')
                            ->default(true),
                    ]),
            ]);
    }
}
