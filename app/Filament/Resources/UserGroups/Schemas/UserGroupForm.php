<?php

namespace App\Filament\Resources\UserGroups\Schemas;

use App\Models\Feature;
use App\Models\UserGroup;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class UserGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('گروه کاربری')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('نام گروه')
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
                            ->unique(UserGroup::class, 'slug', ignoreRecord: true),

                        Toggle::make('is_default')
                            ->label('گروه پیش‌فرض کاربران جدید')
                            ->helperText('کاربران تازه به‌صورت خودکار در این گروه قرار می‌گیرند.'),

                        TextInput::make('sort')
                            ->label('ترتیب نمایش')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Textarea::make('description')
                            ->label('توضیح')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('امکانات این گروه')
                    ->description('امکاناتی که کاربران این گروه در منوی پنل می‌بینند. آیتم باید در «امکانات پنل» هم فعال شده باشد تا به‌جای «بزودی» به‌صورت لینک واقعی دیده شود.')
                    ->schema([
                        CheckboxList::make('features')
                            ->label('')
                            ->relationship('features', 'label')
                            ->getOptionLabelFromRecordUsing(fn (Feature $record) => $record->group_label.' › '.$record->label)
                            ->options(fn () => Feature::query()->ordered()->get()
                                ->mapWithKeys(fn (Feature $f) => [$f->id => $f->group_label.' › '.$f->label])
                                ->all())
                            ->bulkToggleable()
                            ->searchable()
                            ->columns(2),
                    ]),
            ]);
    }
}
