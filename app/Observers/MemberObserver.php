<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Member;
use App\Models\Official;

/**
 * LOW-4 (Fase 2 Task 3): saat Member di-soft-delete, jabatan Official
 * (majelis_lokal) yang masih aktif (end_date null) otomatis diakhiri.
 *
 * Restore Member TIDAK mengembalikan end_date secara otomatis (asumsi spec §9.8
 * — admin mengatur manual). Force-delete tidak memicu penonaktifan karena FK
 * officials.member_id di-restrict (migrasi baru).
 */
class MemberObserver
{
    public function deleted(Member $member): void
    {
        if ($member->isForceDeleting()) {
            return;
        }

        Official::query()
            ->where('member_id', $member->id)
            ->whereNull('end_date')
            ->update(['end_date' => now()->toDateString()]);
    }
}
