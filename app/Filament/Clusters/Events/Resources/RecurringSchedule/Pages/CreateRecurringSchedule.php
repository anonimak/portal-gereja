<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Events\Resources\RecurringSchedule\Pages;

use App\Filament\Clusters\Events\Resources\RecurringSchedule\RecurringScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRecurringSchedule extends CreateRecord
{
    protected static string $resource = RecurringScheduleResource::class;
}
