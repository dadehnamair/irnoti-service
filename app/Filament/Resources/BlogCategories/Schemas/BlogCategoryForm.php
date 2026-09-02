<?php

namespace App\Filament\Resources\BlogCategories\Schemas;

use App\Models\BlogCategory;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('نام دسته')
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
                            ->unique(BlogCategory::class, 'slug', ignoreRecord: true),

                        Textarea::make('description')
                            ->label('توضیح')
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('meta_title')
                            ->label('عنوان متا')
                            ->maxLength(70),

                        TextInput::make('sort')
                            ->label('ترتیب')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Textarea::make('meta_description')
                            ->label('توضیح متا')
                            ->rows(2)
                            ->maxLength(160)
                            ->columnSpanFull(),

                        Toggle::make('is_visible')
                            ->label('نمایش در سایت')
                            ->default(true),
                    ]),
            ]);
    }
}
