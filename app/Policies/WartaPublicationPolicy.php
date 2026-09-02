<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WartaPublication;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy publikasi Warta (portal publik).
 *
 * RBAC konsisten matriks laporan/Warta:
 * - view/viewAny: role yang punya akses Warta (super_admin, church_admin,
 *   warta_editor, report_viewer).
 * - create/update/delete/publish: hanya super_admin, church_admin, warta_editor
 *   (yang menyusun & menerbitkan Warta). finance_admin/jemaat_admin ditolak.
 * - Church-scope: admin gereja hanya bisa kelola edisi gereja sendiri.
 */
class WartaPublicationPolicy
{
    use HandlesAuthorization;

    protected function canAccessChurch(User $user, mixed $record): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        return $record !== null
            && isset($record->church_id)
            && (int) $record->church_id === (int) $user->church_id;
    }

    protected function canManage(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'church_admin', 'warta_editor'], true);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('report.warta.view');
    }

    public function view(User $user, WartaPublication $publication): bool
    {
        return $this->viewAny($user) && $this->canAccessChurch($user, $publication);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, WartaPublication $publication): bool
    {
        return $this->canManage($user) && $this->canAccessChurch($user, $publication);
    }

    public function delete(User $user, WartaPublication $publication): bool
    {
        return $this->canManage($user) && $this->canAccessChurch($user, $publication);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function restore(User $user, WartaPublication $publication): bool
    {
        return $this->canManage($user) && $this->canAccessChurch($user, $publication);
    }

    public function forceDelete(User $user, WartaPublication $publication): bool
    {
        return $this->canManage($user) && $this->canAccessChurch($user, $publication);
    }
}
