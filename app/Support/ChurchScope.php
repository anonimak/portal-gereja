<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Helper scoping opsi form select antar gereja (Fase 2 Task 3 — backlog MED).
 *
 * - forActorSelect(): form resource TOP-LEVEL — super_admin melihat semua gereja,
 *   non-super_admin hanya gereja sendiri.
 * - forChurch($churchId): form yang diturunkan dari induk (roster/sakramen/
 *   attendance) — selalu ikut gereja OWNER RECORD, bukan gereja aktor.
 *
 * Helper hanya memengaruhi OPSI select; global scope BelongsToChurch tetap aktif
 * dan integritas FK lintas gereja tetap di-guard (abort 403) di trait.
 */
final class ChurchScope
{
    public static function forActorSelect(Builder $query): Builder
    {
        $user = auth()->user();
        if ($user && $user->role !== 'super_admin') {
            $query->where('church_id', $user->church_id);
        }

        return $query;
    }

    public static function forChurch(int $churchId, Builder $query): Builder
    {
        // forChurch mengikuti OWNER RECORD (AC-T3-13): lepaskan global scope
        // tenant yang memfilter ke gereja aktor sebelum menerapkan gereja owner.
        // Tanpa ini, query dari relationship yang sudah ter-scope akan bertabrakan
        // (where church_id = aktor AND church_id = owner) → opsi kosong.
        $query->withoutGlobalScope('church');

        return $query->where('church_id', $churchId);
    }
}
