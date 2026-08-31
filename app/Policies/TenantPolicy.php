<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Base policy untuk resource tenant (multi-church).
 * Lapisan kedua di atas global scope BelongsToChurch: menutup IDOR lintas gereja
 * untuk jalur yang melewati Policy (view/update/delete/forceDelete).
 *
 * Role yang boleh mengakses modul ini ditentukan oleh $allowedRoles.
 * Default: super_admin + church_admin (resource NON-keuangan).
 * Resource keuangan (Transaction, Fund, FinancialCategory) menambahkan finance_admin
 * dengan meng-override $allowedRoles — lihat AC-T2-03 (BLOCK-1 Vera).
 */
class TenantPolicy
{
    use HandlesAuthorization;

    /**
     * Role yang diizinkan mengakses resource yang memakai policy ini.
     *
     * @var array<int, string>
     */
    protected array $allowedRoles = ['super_admin', 'church_admin'];

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

    protected function hasModuleAccess(User $user): bool
    {
        return in_array($user->role, $this->allowedRoles, true);
    }

    public function viewAny(User $user): bool
    {
        return $this->hasModuleAccess($user);
    }

    public function view(User $user, mixed $record): bool
    {
        return $this->hasModuleAccess($user) && $this->canAccessChurch($user, $record);
    }

    public function create(User $user): bool
    {
        return $this->hasModuleAccess($user);
    }

    public function update(User $user, mixed $record): bool
    {
        return $this->hasModuleAccess($user) && $this->canAccessChurch($user, $record);
    }

    public function delete(User $user, mixed $record): bool
    {
        return $this->hasModuleAccess($user) && $this->canAccessChurch($user, $record);
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasModuleAccess($user);
    }

    public function restore(User $user, mixed $record): bool
    {
        return $this->hasModuleAccess($user) && $this->canAccessChurch($user, $record);
    }

    public function forceDelete(User $user, mixed $record): bool
    {
        return $this->hasModuleAccess($user) && $this->canAccessChurch($user, $record);
    }
}
