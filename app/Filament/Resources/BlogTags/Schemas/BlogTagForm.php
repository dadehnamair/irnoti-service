<?php

namespace App\Filament\Resources\BlogTags\Schemas;

use App\Models\BlogTag;
use Filament\Forms\Components\TextInput;
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
            ]);
    }
}
