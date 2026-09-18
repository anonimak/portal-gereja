<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Reporting\Resources\MeetingMinutesResource\Pages;

use App\Filament\Clusters\Reporting\Resources\MeetingMinutesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMeetingMinutes extends ListRecords
{
    protected static string $resource = MeetingMinutesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
