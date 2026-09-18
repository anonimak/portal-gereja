<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Reporting\Resources\MeetingMinutesResource\Pages;

use App\Filament\Clusters\Reporting\Resources\MeetingMinutesResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMeetingMinutes extends ViewRecord
{
    protected static string $resource = MeetingMinutesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
