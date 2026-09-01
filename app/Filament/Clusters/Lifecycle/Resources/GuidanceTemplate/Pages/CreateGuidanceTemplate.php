<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\GuidanceTemplate\Pages;

use App\Filament\Clusters\Lifecycle\Resources\GuidanceTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGuidanceTemplate extends CreateRecord
{
    protected static string $resource = GuidanceTemplateResource::class;
}
