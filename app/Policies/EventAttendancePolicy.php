<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Policy kehadiran ibadah per anggota (Fase 2 Task 2/3).
 *
 * Modul `attendance`: viewAny/view memakai attendance.view; SEMUA aksi tulis
 * (create/update/delete/restore/forceDelete/deleteAny) memakai attendance.manage
 * (AC-T3-02) — sehingga church_admin/super_admin tetap bisa check-in tanpa
 * regresi, dan finance_admin tetap ditolak (AC-T2-12).
 */
class EventAttendancePolicy extends TenantPolicy
{
    protected static string $module = 'attendance';
}
