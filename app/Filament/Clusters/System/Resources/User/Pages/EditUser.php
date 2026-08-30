<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Resources\User\Pages;

use App\Filament\Clusters\System\Resources\User\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Guard server-side sebelum data disimpan (lapisan ke-2 dari 3).
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $actor = auth()->user();
        $record = $this->record;

        // Non-super_admin dilarang mengubah user super_admin
        if ($actor && $actor->role !== 'super_admin' && ($record->role === 'super_admin' || ($data['role'] ?? null) === 'super_admin')) {
            abort(403, 'Tidak diizinkan mengubah user Super Admin.');
        }

        // Non-super_admin: paksa church_id ke gereja sendiri
        if ($actor && $actor->role !== 'super_admin') {
            $data['church_id'] = $actor->church_id;
        }

        // Super admin tidak boleh menurunkan role dirinya sendiri
        if ($actor && $actor->role === 'super_admin' && $record->id === $actor->id && ($data['role'] ?? null) !== 'super_admin') {
            abort(403, 'Super Admin tidak dapat menurunkan role dirinya sendiri.');
        }

        return $data;
    }
}
