<?php

declare(strict_types=1);

namespace App\Filament\Clusters\MasterData\Resources\MinistryRole\Pages;

use App\Filament\Clusters\MasterData\Resources\MinistryRole\MinistryRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMinistryRole extends CreateRecord
{
    protected static string $resource = MinistryRoleResource::class;
}
