<?php

namespace App\Filament\Resources\SmsPackages\Pages;

use App\Filament\Resources\SmsPackages\SmsPackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSmsPackage extends EditRecord
{
    protected static string $resource = SmsPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
