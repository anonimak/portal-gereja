<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Permission keys RBAC granular (Fase 2 Task 3) — satu sumber kebenaran.
 *
 * Format: `<modul>.<kapabilitas>` dengan kapabilitas view/create/update/delete.
 * Modul attendance memakai `attendance.view` (baca) dan `attendance.manage`
 * (semua aksi tulis: check-in, ubah status, koreksi/hapus) — bukan
 * create/update/delete (key tsb tidak ada untuk attendance).
 */
enum Permission: string
{
    // Jemaat (Family, Member, MemberSacrament)
    case MemberView = 'member.view';
    case MemberCreate = 'member.create';
    case MemberUpdate = 'member.update';
    case MemberDelete = 'member.delete';

    // Acara (Event, EventRoster)
    case EventView = 'event.view';
    case EventCreate = 'event.create';
    case EventUpdate = 'event.update';
    case EventDelete = 'event.delete';

    // Kehadiran (EventAttendance)
    case AttendanceView = 'attendance.view';
    case AttendanceManage = 'attendance.manage';

    // Keuangan (Transaction)
    case FinanceView = 'finance.view';
    case FinanceCreate = 'finance.create';
    case FinanceUpdate = 'finance.update';
    case FinanceDelete = 'finance.delete';

    // Master data keuangan (Fund, FinancialCategory)
    case MasterFinanceView = 'master.finance.view';
    case MasterFinanceCreate = 'master.finance.create';
    case MasterFinanceUpdate = 'master.finance.update';
    case MasterFinanceDelete = 'master.finance.delete';

    // Master data acara (EventCategory, MinistryRole)
    case MasterEventView = 'master.event.view';
    case MasterEventCreate = 'master.event.create';
    case MasterEventUpdate = 'master.event.update';
    case MasterEventDelete = 'master.event.delete';

    // Halaman laporan
    case ReportWartaView = 'report.warta.view';
    case ReportRapatView = 'report.rapat.view';

    // System (Church, User, Official)
    case SystemView = 'system.view';
    case SystemCreate = 'system.create';
    case SystemUpdate = 'system.update';
    case SystemDelete = 'system.delete';
}
