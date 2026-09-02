<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Guard level model untuk User — berlaku untuk JALUR APA PUN
 * (Filament, tinker, mass assignment, dll). Ini lapisan terdalam dari
 * 3-lapis anti privilege escalation (form → mutation halaman → observer).
 */
class UserObserver
{
    /**
     * Whitelist role server-side (AC-T3-01): 6 role panel.
     *
     * @return array<int, string>
     */
    private static function allowedRoles(): array
    {
        return UserRole::panelRoles();
    }

    public function creating(User $user): void
    {
        $actor = auth()->user();

        // Non-super_admin tidak boleh membuat user super_admin
        if ($actor && $actor->role !== 'super_admin' && $user->role === 'super_admin') {
            abort(403, 'Tidak diizinkan membuat user Super Admin.');
        }

        // Non-super_admin hanya bisa membuat user untuk gereja sendiri
        if ($actor && $actor->role !== 'super_admin' && !empty($user->church_id) && $user->church_id !== $actor->church_id) {
            abort(403, 'Tidak diizinkan membuat user untuk gereja lain.');
        }

        // Paksa church_id ke gereja aktor jika tidak diisi (cegah user yatim NULL)
        if ($actor && $actor->role !== 'super_admin' && empty($user->church_id)) {
            $user->church_id = $actor->church_id;
        }

        $this->assertValidRole($user->role);
    }

    public function updating(User $user): void
    {
        $actor = auth()->user();

        // Role AKTOR — pakai nilai tersimpan (original) jika aktor adalah objek model
        // yang sama dengan user yang sedang diubah (kasus self-edit via model instance),
        // karena atribut role user sudah dirty (nilai baru) saat event updating berjalan.
        $actorRole = ($actor && $actor->id === $user->id)
            ? $user->getOriginal('role')
            : ($actor?->role ?? null);

        $isBecomingSuperAdmin = $user->role === 'super_admin';
        $wasSuperAdmin = $user->getOriginal('role') === 'super_admin';

        // Non-super_admin dilarang mengubah role apa pun (termasuk dirinya) menjadi super_admin
        if ($actor && $actorRole !== 'super_admin' && $isBecomingSuperAdmin) {
            abort(403, 'Tidak diizinkan mengubah user Super Admin.');
        }

        // Non-super_admin dilarang menyentuh super_admin yang sudah ada (selain dirinya sendiri)
        if ($actor && $actorRole !== 'super_admin' && $actor->id !== $user->id && $wasSuperAdmin) {
            abort(403, 'Tidak diizinkan mengubah user Super Admin.');
        }

        // Non-super_admin tidak boleh memindahkan user ke gereja lain
        if ($actor && $actorRole !== 'super_admin' && $user->church_id !== $actor->church_id) {
            abort(403, 'Tidak diizinkan memindahkan user ke gereja lain.');
        }

        // Super admin tidak boleh menurunkan role dirinya sendiri
        if ($actor && $actorRole === 'super_admin' && $user->id === $actor->id && $wasSuperAdmin && $user->role !== 'super_admin') {
            abort(403, 'Super Admin tidak dapat menurunkan role dirinya sendiri.');
        }

        $this->assertValidRole($user->role);
    }

    public function deleting(User $user): void
    {
        $actor = auth()->user();

        // Super admin tidak bisa menghapus akun sendiri
        if ($actor && $actor->id === $user->id) {
            abort(403, 'Tidak diizinkan menghapus akun sendiri.');
        }

        // Non-super_admin tidak bisa menghapus super_admin (defense-in-depth)
        if ($actor && $actor->role !== 'super_admin' && $user->role === 'super_admin') {
            abort(403, 'Tidak diizinkan menghapus user Super Admin.');
        }
    }

    private function assertValidRole(?string $role): void
    {
        if ($role !== null && !in_array($role, self::allowedRoles(), true)) {
            abort(422, "Role '{$role}' tidak valid.");
        }
    }
}
