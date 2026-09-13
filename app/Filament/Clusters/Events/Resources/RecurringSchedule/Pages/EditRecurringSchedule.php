<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Events\Resources\RecurringSchedule\Pages;

use App\Filament\Clusters\Events\Resources\RecurringSchedule\RecurringScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditRecurringSchedule extends EditRecord
{
    protected static string $resource = RecurringScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
