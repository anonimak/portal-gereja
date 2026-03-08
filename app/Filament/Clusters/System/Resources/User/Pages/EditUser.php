<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Resources\User\Pages;

use App\Filament\Clusters\System\Resources\User\UserResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
