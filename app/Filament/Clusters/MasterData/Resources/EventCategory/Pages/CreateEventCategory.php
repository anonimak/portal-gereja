<?php

declare(strict_types=1);

namespace App\Filament\Clusters\MasterData\Resources\EventCategory\Pages;

use App\Filament\Clusters\MasterData\Resources\EventCategory\EventCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEventCategory extends CreateRecord
{
    protected static string $resource = EventCategoryResource::class;
}
