<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\GuidanceSession\Pages;

use App\Filament\Clusters\Lifecycle\Resources\GuidanceSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuidanceSessions extends ListRecords
{
    protected static string $resource = GuidanceSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
