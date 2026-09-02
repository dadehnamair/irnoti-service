<?php

namespace App\Filament\Resources\SmsPackages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SmsPackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')->label('نام')->searchable()->sortable(),
                TextColumn::make('sms_count')->label('پیامک')->numeric(locale: 'en')->sortable(),
                TextColumn::make('price')->label('قیمت')->toman()->sortable(),
                TextColumn::make('badge_label')->label('برچسب')->badge()->placeholder('—'),
                IconColumn::make('is_featured')->label('ویژه')->boolean(),
                IconColumn::make('is_active')->label('فعال')->boolean(),
                TextColumn::make('sort')->label('ترتیب')->numeric()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('وضعیت'),
                TernaryFilter::make('is_featured')->label('ویژه'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
