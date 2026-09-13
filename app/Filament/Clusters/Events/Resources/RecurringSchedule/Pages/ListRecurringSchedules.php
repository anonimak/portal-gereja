<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Events\Resources\RecurringSchedule\Pages;

use App\Filament\Clusters\Events\Resources\RecurringSchedule\RecurringScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecurringSchedules extends ListRecords
{
    protected static string $resource = RecurringScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
