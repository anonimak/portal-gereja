<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Policy DeathRecord (Kematian / Surat Keterangan Kematian) — modul lifecycle.
 *
 * Mengikuti LifecyclePolicy: super_admin/church_admin tulis (gereja sendiri
 * utk church_admin), warta_editor/report_viewer read-only,
 * finance_admin/jemaat_admin ditolak.
 */
class DeathPolicy extends LifecyclePolicy
{
}
