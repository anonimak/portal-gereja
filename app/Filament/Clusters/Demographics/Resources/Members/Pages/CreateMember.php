<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Demographics\Resources\Members\Pages;

use App\Filament\Clusters\Demographics\Resources\Members\MemberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;
}
