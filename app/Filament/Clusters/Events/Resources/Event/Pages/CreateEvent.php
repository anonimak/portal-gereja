<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Events\Resources\Event\Pages;

use App\Filament\Clusters\Events\Resources\Event\EventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;
}
