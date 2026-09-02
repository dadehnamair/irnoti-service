<?php

namespace App\Filament\Resources\MarketplaceApps\Tables;

use App\Models\MarketplaceApp;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MarketplaceAppsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('icon')->label('')->disk('public')->circular()->toggleable(),

                TextColumn::make('name')->label('نام')->searchable()->sortable()->description(fn (MarketplaceApp $r) => $r->vendor),

                TextColumn::make('category')
                    ->label('دسته')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => MarketplaceApp::CATEGORIES[$state] ?? $state),

                TextColumn::make('billing_type')
                    ->label('پرداخت')
                    ->badge()
                    ->color(fn (string $state) => $state === 'free' ? 'success' : 'gray')
                    ->formatStateUsing(fn (MarketplaceApp $r) => $r->price_label),

                TextColumn::make('installations_count')
                    ->label('نصب‌ها')
                    ->counts('installations')
                    ->numeric(locale: 'en'),

                IconColumn::make('is_featured')->label('ویژه')->boolean()->toggleable(),
                IconColumn::make('is_active')->label('فعال')->boolean(),
                TextColumn::make('sort')->label('ترتیب')->numeric()->sortable()->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('وضعیت'),
                SelectFilter::make('category')->label('دسته')->options(MarketplaceApp::CATEGORIES),
                SelectFilter::make('billing_type')->label('نوع پرداخت')->options(MarketplaceApp::BILLING_TYPES),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
