<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\RosterConflictException;
use App\Models\Event;
use App\Models\EventRoster;
use Illuminate\Database\Eloquent\Collection;

/**
 * Deteksi bentrok jadwal pelayan (roster).
 *
 * Aturan:
 *  1. Satu orang (member ATAU official) tidak boleh dijadwalkan pada DUA event
 *     berbeda yang waktunya tumpang tindih — overlap [start, end).
 *  2. Satu orang tidak boleh didaftarkan dua kali pada event yang sama
 *     (duplikat assignment), walau waktunya tidak tumpang tindih.
 *
 * Guard dipasang di model EventRoster::saving (level model — berlaku untuk
 * jalur apa pun: Filament Repeater, tinker, import, dsb). UI Repeater di
 * EventResource menampilkan peringatan helperText via conflictNote().
 */
class RosterConflictService
{
    /**
     * Cari roster lain yang berbentrok dengan roster kandidat (guard penuh:
     * termasuk duplikat pada event yang sama).
     *
     * @return Collection<int, EventRoster>
     */
    public static function conflictsForRoster(EventRoster $roster, ?int $excludeRosterId = null): Collection
    {
        $event = $roster->event()->withoutGlobalScopes()->first();

        if ($event === null || $event->start_datetime === null) {
            return new Collection();
        }

        return self::conflictsForCandidate(
            eventId: $event->id,
            start: $event->start_datetime,
            end: $event->end_datetime ?? $event->start_datetime,
            memberId: $roster->member_id,
            officialId: $roster->official_id,
            excludeRosterId: $excludeRosterId,
            checkSameEvent: true,
        );
    }

    /**
     * Cari roster berbentrok untuk kandidat.
     *
     * @param  bool  $checkSameEvent  true = termasuk duplikat pada event yang sama
     *                                (guard model); false = hanya event LAIN yang
     *                                overlap (peringatan UI — menghindari false
     *                                positive terhadap baris roster itu sendiri).
     * @return Collection<int, EventRoster>
     */
    public static function conflictsForCandidate(
        int $eventId,
        mixed $start,
        mixed $end,
        ?int $memberId = null,
        ?int $officialId = null,
        ?int $excludeRosterId = null,
        bool $checkSameEvent = true,
    ): Collection {
        if ($memberId === null && $officialId === null) {
            return new Collection();
        }

        $query = EventRoster::query()
            ->withoutGlobalScopes()
            ->where(function ($q) use ($eventId, $start, $end, $checkSameEvent): void {
                // 1. Event LAIN yang waktunya overlap [start, end).
                $q->whereHas('event', function ($q2) use ($eventId, $start, $end): void {
                    $q2->where('events.id', '!=', $eventId)
                        ->where('start_datetime', '<', $end)
                        ->where(function ($q3) use ($start): void {
                            $q3->whereNull('end_datetime')
                                ->orWhere('end_datetime', '>', $start);
                        });
                });

                // 2. Duplikat pada event yang sama (hanya untuk guard).
                if ($checkSameEvent) {
                    $q->orWhere('event_id', $eventId);
                }
            });

        if ($memberId !== null) {
            $query->where('member_id', $memberId);
        } elseif ($officialId !== null) {
            $query->where('official_id', $officialId);
        }

        if ($excludeRosterId !== null) {
            $query->where('id', '!=', $excludeRosterId);
        }

        return $query
            ->with(['event', 'member', 'official'])
            ->get();
    }

    /**
     * Bentrok → lempar RosterConflictException (dipakai guard model).
     */
    public static function assertNoConflict(EventRoster $roster, ?int $excludeRosterId = null): void
    {
        $conflicts = self::conflictsForRoster($roster, $excludeRosterId);

        if ($conflicts->isNotEmpty()) {
            throw RosterConflictException::forConflicts(self::summarize($conflicts));
        }
    }

    /**
     * Pesan helperText untuk UI (null = tidak ada bentrok lintas-event).
     */
    public static function conflictNote(
        int $eventId,
        mixed $start,
        mixed $end,
        ?int $memberId = null,
        ?int $officialId = null,
    ): ?string {
        $conflicts = self::conflictsForCandidate(
            eventId: $eventId,
            start: $start,
            end: $end,
            memberId: $memberId,
            officialId: $officialId,
            excludeRosterId: null,
            checkSameEvent: false,
        );

        if ($conflicts->isEmpty()) {
            return null;
        }

        return '⚠ Bentrok jadwal: '.implode('; ', self::summarize($conflicts));
    }

    /**
     * Ringkasan bentrok yang terbaca manusia, mis. "Senin 07:00 — Kebaktian Minggu".
     *
     * @return array<int, string>
     */
    public static function summarize(Collection $conflicts): array
    {
        return $conflicts
            ->map(function (EventRoster $roster): string {
                $person = $roster->official_id
                    ? trim((string) ($roster->official?->display_name ?? 'Petugas'))
                    : trim((string) ($roster->member?->full_name ?? 'Anggota'));

                $time = $roster->event?->start_datetime
                    ? $roster->event->start_datetime->format('d/m H:i')
                    : 'waktu tidak diketahui';

                return sprintf('%s — %s (%s)', $person, $roster->event?->title ?? 'acara lain', $time);
            })
            ->values()
            ->all();
    }
}
