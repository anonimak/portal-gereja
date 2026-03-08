<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Events\Resources\Event\Pages;

use App\Filament\Clusters\Events\Resources\Event\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
