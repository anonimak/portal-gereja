<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Finance\Resources\Transaction\Pages;

use App\Filament\Clusters\Finance\Resources\Transaction\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
