<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Resources\Official\Pages;

use App\Filament\Clusters\System\Resources\Official\OfficialResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListOfficials extends ListRecords
{
    protected static string $resource = OfficialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
