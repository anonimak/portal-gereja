<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Base policy untuk resource tenant (multi-church) — RBAC granular (Fase 2 Task 3).
 *
 * Mengganti $allowedRoles dengan permission keys per modul:
 * - viewAny/view        → user->hasPermission("{$module}.view")
 * - create/update/delete/restore/forceDelete/deleteAny
 *                       → user->hasPermission("{$module}.<ability>")
 * - KECUALI modul `attendance`: semua aksi tulis memakai `attendance.manage`
 *   (permission create/update/delete tidak ada untuk attendance).
 *
 * canAccessChurch() (scope gereja) tetap — menutup IDOR lintas gereja.
 */
class TenantPolicy
{
    use HandlesAuthorization;

    /**
     * Modul permission untuk resource ini. Subclass wajib set.
     */
    protected static string $module = 'member';

    /**
     * Super admin dapat mengakses semua record semua gereja; admin gereja
     * hanya record gereja sendiri.
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

    protected function hasViewPermission(User $user): bool
    {
        return $user->hasPermission(static::$module . '.view');
    }

    protected function hasWritePermission(User $user, string $ability): bool
    {
        // Pengecualian attendance (AC-T3-02): semua aksi tulis memakai
        // attendance.manage, bukan create/update/delete.
        if (static::$module === 'attendance') {
            return $user->hasPermission('attendance.manage');
        }

        return $user->hasPermission(static::$module . '.' . $ability);
    }

    public function viewAny(User $user): bool
    {
        return $this->hasViewPermission($user);
    }

    public function view(User $user, mixed $record): bool
    {
        return $this->hasViewPermission($user) && $this->canAccessChurch($user, $record);
    }

    public function create(User $user): bool
    {
        return $this->hasWritePermission($user, 'create');
    }

    public function update(User $user, mixed $record): bool
    {
        return $this->hasWritePermission($user, 'update') && $this->canAccessChurch($user, $record);
    }

    public function delete(User $user, mixed $record): bool
    {
        return $this->hasWritePermission($user, 'delete') && $this->canAccessChurch($user, $record);
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasWritePermission($user, 'delete');
    }

    public function restore(User $user, mixed $record): bool
    {
        return $this->hasWritePermission($user, 'update') && $this->canAccessChurch($user, $record);
    }

    public function forceDelete(User $user, mixed $record): bool
    {
        return $this->hasWritePermission($user, 'delete') && $this->canAccessChurch($user, $record);
    }
}
