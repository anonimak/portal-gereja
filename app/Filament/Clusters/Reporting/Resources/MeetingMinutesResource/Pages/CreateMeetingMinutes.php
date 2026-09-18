<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Reporting\Resources\MeetingMinutesResource\Pages;

use App\Filament\Clusters\Reporting\Resources\MeetingMinutesResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMeetingMinutes extends CreateRecord
{
    protected static string $resource = MeetingMinutesResource::class;
}
