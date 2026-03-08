<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Resources\User\Pages;

use App\Filament\Clusters\System\Resources\User\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
