<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Finance\Resources\Transaction\Pages;

use App\Filament\Clusters\Finance\Resources\Transaction\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
