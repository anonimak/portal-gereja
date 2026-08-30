<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Base policy untuk resource tenant (multi-church).
 * Lapisan kedua di atas global scope BelongsToChurch: menutup IDOR lintas gereja
 * untuk jalur yang melewati Policy (view/update/delete/forceDelete).
 */
class TenantPolicy
{
    use HandlesAuthorization;

    /**
     * Super admin dapat mengakses semua record semua gereja.
     */
    protected function canAccessChurch(User $user, mixed $record): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        return $record !== null
            && isset($record->church_id)
            && $record->church_id === $user->church_id;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'church_admin', 'finance_admin'], true);
    }

    public function view(User $user, mixed $record): bool
    {
        return $this->canAccessChurch($user, $record);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'church_admin'], true);
    }

    public function update(User $user, mixed $record): bool
    {
        return $this->canAccessChurch($user, $record);
    }

    public function delete(User $user, mixed $record): bool
    {
        return $this->canAccessChurch($user, $record);
    }

    public function deleteAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'church_admin'], true);
    }

    public function restore(User $user, mixed $record): bool
    {
        return $this->canAccessChurch($user, $record);
    }

    public function forceDelete(User $user, mixed $record): bool
    {
        return $this->canAccessChurch($user, $record);
    }
}
