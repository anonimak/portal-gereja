<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Resources\Official\Pages;

use App\Filament\Clusters\System\Resources\Official\OfficialResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditOfficial extends EditRecord
{
    protected static string $resource = OfficialResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
