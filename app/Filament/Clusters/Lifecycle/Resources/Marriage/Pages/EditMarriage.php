<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\Marriage\Pages;

use App\Filament\Clusters\Lifecycle\Resources\Marriage\MarriageResource;
use Filament\Resources\Pages\EditRecord;

class EditMarriage extends EditRecord
{
    protected static string $resource = MarriageResource::class;
}
