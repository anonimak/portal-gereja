<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Official (Pelayan Gereja) hanya dikelola Super Admin (cluster System).
 */
class OfficialPolicy
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
        return $user->role === 'super_admin';
    }

    public function deleteAny(User $user): bool
    {
        return $user->role === 'super_admin';
    }
}
