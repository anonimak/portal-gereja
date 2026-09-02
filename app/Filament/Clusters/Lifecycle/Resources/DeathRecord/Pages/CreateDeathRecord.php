<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\DeathRecord\Pages;

use App\Filament\Clusters\Lifecycle\Resources\DeathRecord\DeathRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDeathRecord extends CreateRecord
{
    protected static string $resource = DeathRecordResource::class;
}
