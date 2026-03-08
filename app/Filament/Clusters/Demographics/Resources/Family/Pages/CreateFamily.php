<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Demographics\Resources\Family\Pages;

use App\Filament\Clusters\Demographics\Resources\Family\FamilyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFamily extends CreateRecord
{
    protected static string $resource = FamilyResource::class;
}
