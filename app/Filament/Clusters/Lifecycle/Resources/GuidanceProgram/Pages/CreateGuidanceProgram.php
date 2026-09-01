<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\GuidanceProgram\Pages;

use App\Filament\Clusters\Lifecycle\Resources\GuidanceProgramResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGuidanceProgram extends CreateRecord
{
    protected static string $resource = GuidanceProgramResource::class;

    /**
     * Auto-instantiate sesi 1..N dari template saat program dibuat (AC-LC-18 penuh di UI).
     * instantiateFromTemplate() idempotent: sesi yang sudah ada tidak diduplikasi.
     */
    protected function afterCreate(): void
    {
        $this->record->instantiateFromTemplate();
    }
}
