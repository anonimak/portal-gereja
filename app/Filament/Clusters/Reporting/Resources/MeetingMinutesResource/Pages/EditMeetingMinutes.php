<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Reporting\Resources\MeetingMinutesResource\Pages;

use App\Filament\Clusters\Reporting\Resources\MeetingMinutesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMeetingMinutes extends EditRecord
{
    protected static string $resource = MeetingMinutesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
