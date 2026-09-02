<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\Marriage\Pages;

use App\Filament\Clusters\Lifecycle\Resources\Marriage\MarriageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarriages extends ListRecords
{
    protected static string $resource = MarriageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
