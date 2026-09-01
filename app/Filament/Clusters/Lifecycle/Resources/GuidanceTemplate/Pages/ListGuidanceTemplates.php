<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\GuidanceTemplate\Pages;

use App\Filament\Clusters\Lifecycle\Resources\GuidanceTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuidanceTemplates extends ListRecords
{
    protected static string $resource = GuidanceTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
