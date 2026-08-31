<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Policy kehadiran ibadah per anggota (Fase 2 Task 2).
 *
 * Default TenantPolicy: super_admin + church_admin. finance_admin TIDAK diberi
 * akses kehadiran (modul Events/Demographics di luar scope-nya) — AC-T2-12.
 */
class EventAttendancePolicy extends TenantPolicy {}
