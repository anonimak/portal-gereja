<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Resources\Church\Pages;

use App\Filament\Clusters\System\Resources\Church\ChurchResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChurch extends CreateRecord
{
    protected static string $resource = ChurchResource::class;
}
