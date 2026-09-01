<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Policy GuidanceSession — modul lifecycle (matriks §7).
 * view: super_admin/church_admin/warta_editor/report_viewer (read-only);
 * tulis: super_admin/church_admin; finance_admin & jemaat_admin ditolak.
 */
class GuidanceSessionPolicy extends LifecyclePolicy {}
