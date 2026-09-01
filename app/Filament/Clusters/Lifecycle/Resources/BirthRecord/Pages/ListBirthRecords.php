<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\BirthRecord\Pages;

use App\Filament\Clusters\Lifecycle\Resources\BirthRecord\BirthRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBirthRecords extends ListRecords
{
    protected static string $resource = BirthRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
