<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Resources\Church\Pages;

use App\Filament\Clusters\System\Resources\Church\ChurchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChurches extends ListRecords
{
    protected static string $resource = ChurchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
