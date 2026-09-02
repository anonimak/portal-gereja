<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\DeathRecord\Pages;

use App\Filament\Clusters\Lifecycle\Resources\DeathRecord\DeathRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeathRecords extends ListRecords
{
    protected static string $resource = DeathRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
