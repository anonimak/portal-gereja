<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\GuidanceProgram\Pages;

use App\Filament\Clusters\Lifecycle\Resources\GuidanceProgramResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGuidanceProgram extends CreateRecord
{
    protected static string $resource = GuidanceProgramResource::class;
}
