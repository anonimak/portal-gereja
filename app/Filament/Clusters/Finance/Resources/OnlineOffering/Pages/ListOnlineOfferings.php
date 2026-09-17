<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Finance\Resources\OnlineOffering\Pages;

use App\Filament\Clusters\Finance\Resources\OnlineOffering\OnlineOfferingResource;
use Filament\Resources\Pages\ListRecords;

class ListOnlineOfferings extends ListRecords
{
    protected static string $resource = OnlineOfferingResource::class;
}
