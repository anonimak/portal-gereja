<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Resources\User\Pages;

use App\Filament\Clusters\System\Resources\User\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Guard server-side sebelum data disimpan (lapisan ke-2 dari 3).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $actor = auth()->user();

        // Non-super_admin dilarang membuat user super_admin
        if ($actor && $actor->role !== 'super_admin' && ($data['role'] ?? null) === 'super_admin') {
            abort(403, 'Tidak diizinkan membuat user Super Admin.');
        }

        // Non-super_admin: paksa church_id ke gereja sendiri
        if ($actor && $actor->role !== 'super_admin') {
            $data['church_id'] = $actor->church_id;
        }

        return $data;
    }
}
