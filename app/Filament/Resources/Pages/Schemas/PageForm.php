<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Models\Page;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
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
                            ->maxLength(160)
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
                            ->unique(Page::class, 'slug', ignoreRecord: true)
                            ->helperText('آدرس نهایی: /{slug}'),

                        Textarea::make('excerpt')
                            ->label('خلاصه')
                            ->rows(2)
                            ->maxLength(300)
                            ->columnSpanFull(),

                        MarkdownEditor::make('body')
                            ->label('متن صفحه (Markdown)')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('seo_title')
                            ->label('عنوان سئو')
                            ->maxLength(70)
                            ->placeholder('خالی = عنوان صفحه'),

                        TextInput::make('sort')
                            ->label('ترتیب')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Textarea::make('seo_description')
                            ->label('توضیح متا')
                            ->rows(2)
                            ->maxLength(160)
                            ->columnSpanFull()
                            ->placeholder('خالی = برگرفته از متن صفحه'),

                        Toggle::make('is_published')
                            ->label('منتشر شده')
                            ->default(true),
                    ]),
            ]);
    }
}
