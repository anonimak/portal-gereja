<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Role pengguna (RBAC granular — Fase 2 Task 3).
 *
 * 3 role lama (super_admin, church_admin, finance_admin) + 3 role baru
 * (jemaat_admin, warta_editor, report_viewer). Satu-satunya sumber kebenaran
 * role → permission ada di App\Support\RoleRegistry.
 */
enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case ChurchAdmin = 'church_admin';
    case FinanceAdmin = 'finance_admin';
    case JemaatAdmin = 'jemaat_admin';
    case WartaEditor = 'warta_editor';
    case ReportViewer = 'report_viewer';

    /**
     * Semua role yang boleh masuk panel Filament (AC-T3-01).
     *
     * @return array<int, string>
     */
    public static function panelRoles(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }
}
