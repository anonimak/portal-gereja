<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Resources\Official\Pages;

use App\Filament\Clusters\System\Resources\Official\OfficialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOfficial extends CreateRecord
{
    protected static string $resource = OfficialResource::class;
}
