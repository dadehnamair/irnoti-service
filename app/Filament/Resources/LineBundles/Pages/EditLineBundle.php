<?php

namespace App\Filament\Resources\LineBundles\Pages;

use App\Filament\Resources\LineBundles\LineBundleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLineBundle extends EditRecord
{
    protected static string $resource = LineBundleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
