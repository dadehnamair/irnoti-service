<?php

namespace App\Filament\Resources\BlogTags\Schemas;

use App\Models\BlogTag;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogTagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام برچسب')
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
                    ->unique(BlogTag::class, 'slug', ignoreRecord: true),

                Section::make('متادیتا')
                    ->columns(2)
                    ->schema([
                        TextInput::make('meta_title')
                            ->label('عنوان متا')
                            ->maxLength(70)
                            ->placeholder('خالی = «برچسب: نام برچسب»'),

                        Textarea::make('meta_description')
                            ->label('توضیح متا')
                            ->rows(2)
                            ->maxLength(160)
                            ->columnSpanFull(),

                        FileUpload::make('og_image')
                            ->label('تصویر اشتراک‌گذاری (OG)')
                            ->image()
                            ->disk('public')
                            ->directory('blog/og')
                            ->visibility('public')
                            ->columnSpanFull()
                            ->helperText('خالی = تصویر پیش‌فرض سایت. ۱۲۰۰×۶۳۰ پیکسل.'),
                    ]),
            ]);
    }
}
