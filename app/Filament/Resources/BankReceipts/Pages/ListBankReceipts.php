<?php

namespace App\Filament\Resources\BankReceipts\Pages;

use App\Filament\Resources\BankReceipts\BankReceiptResource;
use Filament\Resources\Pages\ListRecords;

class ListBankReceipts extends ListRecords
{
    protected static string $resource = BankReceiptResource::class;
}
