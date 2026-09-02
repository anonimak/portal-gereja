<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\DeathRecord\Pages;

use App\Filament\Clusters\Lifecycle\Resources\DeathRecord\DeathRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditDeathRecord extends EditRecord
{
    protected static string $resource = DeathRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
