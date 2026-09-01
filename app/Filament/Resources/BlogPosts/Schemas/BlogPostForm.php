<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Models\BlogPost;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('post')
                    ->persistTabInQueryString()
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('محتوا')->schema(self::contentTab()),
                        Tab::make('سئو')->schema(self::seoTab()),
                        Tab::make('انتشار')->schema(self::publishTab()),
                    ]),
            ]);
    }

    protected static function contentTab(): array
    {
        return [
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
                        })
                        ->helperText('برای سئو بین ۴۰ تا ۶۰ کاراکتر بهتر است.'),

                    TextInput::make('slug')
                        ->label('نامک (slug)')
                        ->required()
                        ->alphaDash()
                        ->unique(BlogPost::class, 'slug', ignoreRecord: true),

                    Select::make('blog_category_id')
                        ->label('دسته‌بندی')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            TextInput::make('name')->label('نام')->required(),
                        ]),

                    Select::make('tags')
                        ->label('برچسب‌ها')
                        ->relationship('tags', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            TextInput::make('name')->label('نام')->required(),
                        ]),

                    Textarea::make('excerpt')
                        ->label('خلاصه')
                        ->rows(3)
                        ->maxLength(300)
                        ->columnSpanFull()
                        ->helperText('در کارت‌های فهرست و به‌عنوان توضیح متای پیش‌فرض استفاده می‌شود.'),

                    FileUpload::make('cover_image')
                        ->label('تصویر شاخص')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('blog')
                        ->visibility('public')
                        ->columnSpanFull()
                        ->helperText('نسبت پیشنهادی ۱۶:۹ — حدود ۱۲۰۰×۶۳۰ پیکسل.'),

                    MarkdownEditor::make('body')
                        ->label('متن مقاله (Markdown)')
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected static function seoTab(): array
    {
        return [
            Section::make('متادیتا')
                ->columns(2)
                ->schema([
                    TextInput::make('meta_title')
                        ->label('عنوان متا')
                        ->maxLength(70)
                        ->placeholder('خالی = عنوان مقاله')
                        ->helperText('حداکثر ۶۰ کاراکتر برای نمایش کامل در گوگل.'),

                    TextInput::make('canonical_url')
                        ->label('آدرس Canonical')
                        ->url()
                        ->placeholder('خالی = همین صفحه'),

                    Textarea::make('meta_description')
                        ->label('توضیح متا')
                        ->rows(3)
                        ->maxLength(160)
                        ->columnSpanFull()
                        ->helperText('خالی = خلاصهٔ مقاله. حدود ۱۲۰ تا ۱۵۵ کاراکتر.'),

                    FileUpload::make('og_image')
                        ->label('تصویر اشتراک‌گذاری (OG)')
                        ->image()
                        ->disk('public')
                        ->directory('blog/og')
                        ->visibility('public')
                        ->columnSpanFull()
                        ->helperText('خالی = تصویر شاخص. ۱۲۰۰×۶۳۰ پیکسل.'),

                    Toggle::make('noindex')
                        ->label('نمایه‌نشدن در موتور جستجو (noindex)')
                        ->helperText('برای پیش‌نویس‌ها یا صفحات کم‌ارزش.'),
                ]),
        ];
    }

    protected static function publishTab(): array
    {
        return [
            Section::make()
                ->columns(2)
                ->schema([
                    Toggle::make('is_published')
                        ->label('منتشر شده')
                        ->live(),

                    DateTimePicker::make('published_at')
                        ->label('تاریخ انتشار')
                        ->seconds(false)
                        ->helperText('خالی بگذارید تا هنگام انتشار، همین حالا ثبت شود. تاریخ آینده = زمان‌بندی.'),

                    Select::make('author_id')
                        ->label('نویسنده')
                        ->relationship('author', 'name')
                        ->searchable()
                        ->preload()
                        ->default(fn () => auth()->id()),
                ]),
        ];
    }
}
