<?php

namespace App\Filament\Resources\DocArticles\Schemas;

use App\Models\DocCodeSample;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DocArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('article')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('محتوا')
                            ->schema(self::contentTab()),

                        Tab::make('پارامترها')
                            ->schema([self::parametersRepeater()]),

                        Tab::make('نمونه کد')
                            ->schema([self::codeSamplesRepeater()]),

                        Tab::make('سئو و انتشار')
                            ->schema(self::seoTab()),
                    ]),
            ]);
    }

    protected static function contentTab(): array
    {
        return [
            Section::make()
                ->columns(2)
                ->schema([
                    Select::make('doc_category_id')
                        ->label('دسته‌بندی')
                        ->relationship('category', 'title')
                        ->searchable()
                        ->preload()
                        ->required(),

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
                        ->helperText('یکتا در هر دسته — مثل: send-single'),

                    Select::make('http_method')
                        ->label('متد HTTP')
                        ->options([
                            'GET' => 'GET',
                            'POST' => 'POST',
                            'PUT' => 'PUT',
                            'PATCH' => 'PATCH',
                            'DELETE' => 'DELETE',
                        ])
                        ->nullable(),

                    TextInput::make('endpoint')
                        ->label('آدرس Endpoint')
                        ->placeholder('/api/v1/sms/send')
                        ->columnSpanFull(),

                    Textarea::make('excerpt')
                        ->label('خلاصه')
                        ->rows(2)
                        ->columnSpanFull(),

                    MarkdownEditor::make('body')
                        ->label('متن (Markdown)')
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected static function parametersRepeater(): Repeater
    {
        return Repeater::make('parameters')
            ->label('پارامترهای درخواست')
            ->relationship()
            ->orderColumn('sort')
            ->defaultItems(0)
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
            ->columns(4)
            ->schema([
                TextInput::make('name')
                    ->label('نام')
                    ->required(),

                TextInput::make('type')
                    ->label('نوع')
                    ->placeholder('string'),

                Toggle::make('is_required')
                    ->label('اجباری')
                    ->inline(false),

                TextInput::make('example')
                    ->label('نمونه مقدار'),

                Textarea::make('description')
                    ->label('توضیح')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    protected static function codeSamplesRepeater(): Repeater
    {
        return Repeater::make('codeSamples')
            ->label('نمونه‌کدها')
            ->relationship()
            ->orderColumn('sort')
            ->defaultItems(0)
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => isset($state['language'])
                ? (DocCodeSample::LANGUAGES[$state['language']] ?? $state['language'])
                : null)
            ->columns(2)
            ->schema([
                Select::make('language')
                    ->label('زبان')
                    ->options(DocCodeSample::LANGUAGES)
                    ->required(),

                TextInput::make('label')
                    ->label('برچسب (اختیاری)'),

                Textarea::make('code')
                    ->label('کد')
                    ->rows(10)
                    ->required()
                    ->columnSpanFull()
                    ->extraInputAttributes(['style' => 'font-family: ui-monospace, monospace; direction: ltr;']),
            ]);
    }

    protected static function seoTab(): array
    {
        return [
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('seo_title')
                        ->label('عنوان سئو')
                        ->maxLength(255),

                    TextInput::make('sort')
                        ->label('ترتیب نمایش')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    Textarea::make('seo_description')
                        ->label('توضیحات متا')
                        ->rows(3)
                        ->maxLength(300)
                        ->columnSpanFull(),

                    Toggle::make('is_published')
                        ->label('منتشر شده')
                        ->default(true),
                ]),
        ];
    }
}
