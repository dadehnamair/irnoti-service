<?php

namespace App\Filament\Resources\LineGroups\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LineGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('هویت صفحه')
                    ->columns(2)
                    ->schema([
                        TextInput::make('prefix')
                            ->label('پیش‌شماره')
                            ->required()
                            ->placeholder('3000')
                            ->helperText('باید با پیش‌شمارهٔ خطوط این خانواده یکی باشد؛ خطوط با همین مقدار خودکار به این صفحه وصل می‌شوند.')
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
                            ->unique(ignoreRecord: true)
                            ->helperText('در آدرس صفحه: /lines/{slug}'),

                        TextInput::make('title')
                            ->label('عنوان (H1)')
                            ->required()
                            ->columnSpanFull()
                            ->placeholder('خط اختصاصی ۳۰۰۰'),

                        TextInput::make('tagline')
                            ->label('زیرعنوان هیرو')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Section::make('محتوا')
                    ->schema([
                        Textarea::make('body')
                            ->label('توضیحات بلند (Markdown)')
                            ->rows(8)
                            ->columnSpanFull(),

                        TagsInput::make('features')
                            ->label('ویژگی‌های خط')
                            ->placeholder('یک ویژگی بنویسید و Enter بزنید')
                            ->columnSpanFull(),

                        TagsInput::make('use_cases')
                            ->label('مناسب چه کسب‌وکارهایی')
                            ->placeholder('یک کاربرد بنویسید و Enter بزنید')
                            ->columnSpanFull(),
                    ]),

                Section::make('سؤالات متداول مخصوص خط')
                    ->schema([
                        Repeater::make('faqs')
                            ->label('پرسش و پاسخ')
                            ->addActionLabel('افزودن پرسش')
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['q'] ?? null)
                            ->schema([
                                TextInput::make('q')
                                    ->label('پرسش')
                                    ->required(),

                                Textarea::make('a')
                                    ->label('پاسخ')
                                    ->rows(3)
                                    ->required(),
                            ]),
                    ]),

                Section::make('سئو')
                    ->columns(2)
                    ->schema([
                        TextInput::make('seo_title')
                            ->label('عنوان سئو')
                            ->maxLength(255),

                        TextInput::make('og_image')
                            ->label('تصویر OG (آدرس)')
                            ->maxLength(255),

                        Textarea::make('seo_description')
                            ->label('توضیحات متا')
                            ->rows(3)
                            ->maxLength(300)
                            ->columnSpanFull(),
                    ]),

                Section::make('وضعیت')
                    ->columns(2)
                    ->schema([
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
