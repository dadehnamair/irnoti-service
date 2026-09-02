<?php

namespace App\Filament\Resources\DocCategories\Schemas;

use App\Models\DocCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DocCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('دسته‌بندی')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان')
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
                            ->unique(DocCategory::class, 'slug', ignoreRecord: true)
                            ->helperText('در آدرس صفحه استفاده می‌شود، مثل: send-sms'),

                        Select::make('parent_id')
                            ->label('دستهٔ والد')
                            ->relationship(
                                name: 'parent',
                                titleAttribute: 'title',
                                modifyQueryUsing: fn ($query, ?DocCategory $record) => $record
                                    ? $query->whereKeyNot($record->getKey())
                                    : $query,
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        TextInput::make('icon')
                            ->label('آیکون (Heroicon)')
                            ->placeholder('heroicon-o-paper-airplane')
                            ->helperText('نام آیکون Heroicon — اختیاری'),

                        Textarea::make('description')
                            ->label('توضیح کوتاه')
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('sort')
                            ->label('ترتیب نمایش')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Toggle::make('is_published')
                            ->label('منتشر شده')
                            ->default(true),
                    ]),
            ]);
    }
}
