<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Demographics\Resources\Family\Pages;

use App\Filament\Clusters\Demographics\Resources\Family\FamilyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFamily extends EditRecord
{
    protected static string $resource = FamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
