<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\User;

/**
 * Peta role → permission (RBAC granular — Fase 2 Task 3).
 *
 * TANPA paket Spatie: array lookup di-memory, konsisten dengan TenantPolicy.
 * super_admin = wildcard (semua permission + lintas gereja).
 */
final class RoleRegistry
{
    /**
     * Permission yang dimiliki sebuah role.
     *
     * @return array<int, Permission>
     */
    public static function permissionsFor(UserRole $role): array
    {
        return match ($role) {
            UserRole::SuperAdmin => Permission::cases(),

            UserRole::ChurchAdmin => [
                Permission::MemberView,
                Permission::MemberCreate,
                Permission::MemberUpdate,
                Permission::MemberDelete,
                Permission::EventView,
                Permission::EventCreate,
                Permission::EventUpdate,
                Permission::EventDelete,
                Permission::AttendanceView,
                Permission::AttendanceManage,
                Permission::FinanceView,
                Permission::FinanceCreate,
                Permission::FinanceUpdate,
                Permission::FinanceDelete,
                Permission::MasterFinanceView,
                Permission::MasterFinanceCreate,
                Permission::MasterFinanceUpdate,
                Permission::MasterFinanceDelete,
                Permission::MasterEventView,
                Permission::MasterEventCreate,
                Permission::MasterEventUpdate,
                Permission::MasterEventDelete,
                Permission::LifecycleView,
                Permission::LifecycleCreate,
                Permission::LifecycleUpdate,
                Permission::LifecycleDelete,
                Permission::ReportWartaView,
                Permission::ReportRapatView,
            ],

            UserRole::FinanceAdmin => [
                Permission::FinanceView,
                Permission::FinanceCreate,
                Permission::FinanceUpdate,
                Permission::FinanceDelete,
                Permission::MasterFinanceView,
                Permission::MasterFinanceCreate,
                Permission::MasterFinanceUpdate,
                Permission::MasterFinanceDelete,
                Permission::ReportRapatView,
            ],

            UserRole::JemaatAdmin => [
                Permission::MemberView,
                Permission::MemberCreate,
                Permission::MemberUpdate,
                Permission::MemberDelete,
            ],

            UserRole::WartaEditor => [
                Permission::ReportWartaView,
                Permission::MemberView,
                Permission::EventView,
                Permission::AttendanceView,
                Permission::FinanceView,
                Permission::MasterEventView,
                Permission::MasterFinanceView,
            ],

            UserRole::ReportViewer => [
                Permission::ReportRapatView,
                Permission::ReportWartaView,
                Permission::MemberView,
                Permission::EventView,
                Permission::FinanceView,
                Permission::MasterFinanceView,
            ],
        };
    }

    /**
     * Cek apakah user punya sebuah permission.
     *
     * super_admin selalu true (wildcard). Role tak dikenal → false.
     */
    public static function has(User $user, string|Permission $permission): bool
    {
        if ($user->role === UserRole::SuperAdmin->value) {
            return true;
        }

        if (! $permission instanceof Permission) {
            $permission = Permission::tryFrom($permission);
        }

        if ($permission === null) {
            return false;
        }

        $role = UserRole::tryFrom($user->role);
        if ($role === null) {
            return false;
        }

        return in_array($permission, self::permissionsFor($role), true);
    }

    /**
     * Hanya super_admin yang boleh lintas gereja (AC-T3-11 & §3.2).
     */
    public static function isCrossChurch(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin->value;
    }
}
