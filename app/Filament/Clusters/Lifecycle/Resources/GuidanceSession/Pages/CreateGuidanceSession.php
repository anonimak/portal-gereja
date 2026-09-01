<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\GuidanceSession\Pages;

use App\Filament\Clusters\Lifecycle\Resources\GuidanceSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGuidanceSession extends CreateRecord
{
    protected static string $resource = GuidanceSessionResource::class;
}
