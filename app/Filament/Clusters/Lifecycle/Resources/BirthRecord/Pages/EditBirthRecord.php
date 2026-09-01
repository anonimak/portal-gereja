<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\BirthRecord\Pages;

use App\Filament\Clusters\Lifecycle\Resources\BirthRecord\BirthRecordResource;
use Filament\Resources\Pages\EditRecord;

class EditBirthRecord extends EditRecord
{
    protected static string $resource = BirthRecordResource::class;
}
