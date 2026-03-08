<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Demographics\Resources\Members\Pages;

use App\Filament\Clusters\Demographics\Resources\Members\MemberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
