<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Policy modul Lifecycle — Kelahiran (Fase 3B T5, SPEC §7).
 *
 * Matriks lifecycle (fallback A11 — T3 permission-based belum di master):
 * - super_admin & church_admin: view+create+update+delete (gereja sendiri utk
 *   church_admin, lintas gereja utk super_admin).
 * - finance_admin / jemaat_admin / warta_editor / report_viewer: DITOLAK.
 *
 * TenantPolicy sudah menjamin isolasi church_id per record (IDOR lintas gereja → 403).
 */
class BirthRecordPolicy extends TenantPolicy
{
    /**
     * Modul permission lifecycle (AC-T3 blocker Vera): tanpa ini policy mewarisi
     * `member.*` dari TenantPolicy sehingga jemaat_admin/warta_editor/report_viewer
     * bisa CRUD BirthRecord. Hanya super_admin & church_admin yang punya
     * lifecycle.* (lihat RoleRegistry).
     */
    protected static string $module = 'lifecycle';
}
