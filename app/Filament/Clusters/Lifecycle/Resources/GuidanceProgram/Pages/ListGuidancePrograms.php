<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\GuidanceProgram\Pages;

use App\Filament\Clusters\Lifecycle\Resources\GuidanceProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuidancePrograms extends ListRecords
{
    protected static string $resource = GuidanceProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
