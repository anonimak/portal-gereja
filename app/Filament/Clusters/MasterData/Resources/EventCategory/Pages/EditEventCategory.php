<?php

declare(strict_types=1);

namespace App\Filament\Clusters\MasterData\Resources\EventCategory\Pages;

use App\Filament\Clusters\MasterData\Resources\EventCategory\EventCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEventCategory extends EditRecord
{
    protected static string $resource = EventCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
