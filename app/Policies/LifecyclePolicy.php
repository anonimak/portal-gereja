<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Base policy modul lifecycle (Bimbingan Pra-Sidi/Pra-Nikah, dsb).
 *
 * Matriks akses (spec §7, fallback A11 — sinkron ke RBAC T3 bila sudah di-master):
 * - super_admin / church_admin : view + create/update/delete (gereja sendiri utk church_admin)
 * - warta_editor / report_viewer : view (read-only) — aksi tulis ditolak
 * - finance_admin / jemaat_admin : ditolak total
 *
 * Church admin tetap ter-isolasi church_id via TenantPolicy::canAccessChurch.
 */
class LifecyclePolicy extends TenantPolicy
{
    /**
     * Role yang boleh MELIHAT data lifecycle (read-only untuk warta/report).
     *
     * @var array<int, string>
     */
    protected array $viewerRoles = ['super_admin', 'church_admin', 'warta_editor', 'report_viewer'];

    /**
     * Role yang boleh MENULIS (create/update/delete) data lifecycle.
     *
     * @var array<int, string>
     */
    protected array $writerRoles = ['super_admin', 'church_admin'];

    public function viewAny(User $user): bool
    {
        return in_array($user->role, $this->viewerRoles, true);
    }

    public function view(User $user, mixed $record): bool
    {
        return in_array($user->role, $this->viewerRoles, true)
            && $this->canAccessChurch($user, $record);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, $this->writerRoles, true);
    }

    public function update(User $user, mixed $record): bool
    {
        return in_array($user->role, $this->writerRoles, true)
            && $this->canAccessChurch($user, $record);
    }

    public function delete(User $user, mixed $record): bool
    {
        return in_array($user->role, $this->writerRoles, true)
            && $this->canAccessChurch($user, $record);
    }

    public function deleteAny(User $user): bool
    {
        return in_array($user->role, $this->writerRoles, true);
    }

    public function restore(User $user, mixed $record): bool
    {
        return in_array($user->role, $this->writerRoles, true)
            && $this->canAccessChurch($user, $record);
    }

    public function forceDelete(User $user, mixed $record): bool
    {
        return in_array($user->role, $this->writerRoles, true)
            && $this->canAccessChurch($user, $record);
    }
}
