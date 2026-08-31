<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * User hanya dikelola Super Admin.
 * Tambahan: tidak boleh menghapus akun sendiri, dan non-super_admin tidak boleh
 * membuat/mengedit user dengan role super_admin.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'super_admin';
    }

    public function view(User $user, mixed $record): bool
    {
        return $user->role === 'super_admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'super_admin';
    }

    public function update(User $user, mixed $record): bool
    {
        return $user->role === 'super_admin';
    }

    public function delete(User $user, mixed $record): bool
    {
        if ($user->role !== 'super_admin') {
            return false;
        }

        // Jangan izinkan menghapus akun sendiri
        return $record === null || $record->id !== $user->id;
    }

    public function deleteAny(User $user): bool
    {
        return $user->role === 'super_admin';
    }
}
