<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Finance\Resources\Transaction\Pages;

use App\Filament\Clusters\Finance\Resources\Transaction\TransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;
}
