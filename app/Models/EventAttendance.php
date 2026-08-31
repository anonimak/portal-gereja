<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use App\Traits\RecordsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Kehadiran ibadah per anggota (check-in) — Fase 2 Task 2.
 *
 * Mengikuti pola anak EventRoster:
 * - BelongsToChurch (global scope + FK consistency + derive church dari induk).
 * - SoftDeletes + RecordsAuditTrail (konsisten Task 1).
 * - checked_in_by bersifat audit-only → TIDAK masuk churchForeignKeyMap (AC-T2-03).
 * - Restore-or-create: check-in ulang member yang pernah soft-deleted memakai
 *   record lama (restore) supaya tidak melanggar UNIQUE(event_id, member_id).
 */
class EventAttendance extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail, SoftDeletes;

    /**
     * FK yang harus satu gereja dengan record ini (AC-T2-02/04).
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return [
            'event_id' => Event::class,
            'member_id' => Member::class,
        ];
    }

    /**
     * church_id mengikuti gereja event (AC-T2-05) — super_admin yang check-in
     * event gereja lain mendapat church_id gereja event, bukan gereja aktor.
     */
    protected function deriveChurchIdFromParent(): ?int
    {
        if (! $this->event_id) {
            return null;
        }

        return Event::query()
            ->withoutGlobalScopes()
            ->whereKey($this->event_id)
            ->value('church_id');
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'event_id',
        'member_id',
        'status',
        'checked_in_at',
        'checked_in_by',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    /**
     * Auto-fill check-in (AC-T2-08): status default 'hadir', checked_in_at = now(),
     * dan checked_in_by = user aktor ketika dibuat lewat jalur mana pun (form
     * Filament, model create, helper checkInMembers). Form TIDAK menerima field
     * ini dari input — nilai dari request SELALU ditimpa server-side.
     */
    public static function booted(): void
    {
        static::creating(function (EventAttendance $attendance): void {
            $actor = auth()->user();

            $attendance->status ??= 'hadir';

            // AC-T2-08: checked_in_at & checked_in_by selalu di-set server-side
            // (bukan fallback ??=) sehingga input form yang mencoba mengirim nilai
            // seenaknya diabaikan. Tanpa aktor (console/seeder/factory) checked_in_by
            // tetap null.
            $attendance->checked_in_by = $actor?->id;
            $attendance->checked_in_at = now();
        });
    }

    /**
     * Check-in satu anggota dengan strategi restore-or-create (blocker re-review
     * Vera PR #4):
     *
     * - Belum ada record (event, member)        → buat baru (created).
     * - Ada record AKTIF                          → dilewati tanpa perubahan
     *   (AC-T2-18) — duplikat tidak pernah error dan tidak mengubah data lama.
     * - Ada record SOFT-DELETED                   → restore + update (restored),
     *   bukan insert baru, supaya tidak melanggar UNIQUE(event_id, member_id).
     *
     * Audit trail ditangani otomatis oleh RecordsAuditTrail: create → 'created',
     * restore → 'restored', update lanjutan → 'updated'.
     *
     * @param  array<string, mixed>  $data
     */
    public static function checkInOrRestore(array $data): self
    {
        $eventId = (int) ($data['event_id'] ?? 0);
        $memberId = (int) ($data['member_id'] ?? 0);

        $existing = static::withTrashed()
            ->where('event_id', $eventId)
            ->where('member_id', $memberId)
            ->first();

        if (! $existing) {
            return static::create($data);
        }

        // AC-T2-18: duplikat AKTIF dilewati tanpa error dan tanpa mengubah record.
        if (! $existing->trashed()) {
            return $existing;
        }

        // AC-T2-17: record soft-deleted di-restore lalu diperbarui ke nilai check-in
        // terbaru (status, checked_in_at, checked_in_by, notes).
        $existing->restore(); // audit 'restored'

        $existing->fill([
            'status' => $data['status'] ?? 'hadir',
            'checked_in_at' => now(),
            'checked_in_by' => auth()->id(),
            'notes' => $data['notes'] ?? $existing->notes,
        ]);
        $existing->save();

        return $existing;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Member yang hadir/tidak hadir.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * User yang melakukan check-in (audit-only, nullable).
     */
    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
