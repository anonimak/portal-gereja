<?php

declare(strict_types=1);

namespace App\Filament\Clusters\MasterData\Resources\Fund\Pages;

use App\Filament\Clusters\MasterData\Resources\Fund\FundResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFund extends CreateRecord
{
    protected static string $resource = FundResource::class;
}
