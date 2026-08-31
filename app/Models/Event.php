<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use App\Traits\RecordsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail, SoftDeletes;

    /**
     * Kolom FK yang harus satu gereja dengan event ini (HIGH-2 Vera).
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return ['category_id' => EventCategory::class];
    }

    /**
     * C2 Vera: soft delete/restore event ikut menimpa roster anak (event_rosters)
     * supaya tidak ada roster aktif tanpa event, dan kembali saat di-restore.
     */
    public static function booted(): void
    {
        static::deleted(function (Event $event): void {
            if ($event->isForceDeleting()) {
                return; // hard delete — cascade DB menangani anak.
            }

            $event->rosters()->withTrashed()->delete();
        });

        static::restored(function (Event $event): void {
            $event->rosters()->withTrashed()->restore();
        });
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'category_id',
        'title',
        'start_datetime',
        'end_datetime',
        'location',
        'attendance_male',
        'attendance_female',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
    ];

    /**
     * Event category that this event belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
    }

    /**
     * Rosters (duty assignments) for this event.
     */
    public function rosters(): HasMany
    {
        return $this->hasMany(EventRoster::class);
    }

    /**
     * Catatan kehadiran per anggota pada acara ini.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }

    /**
     * Total kehadiran acara (AC-T2-10): bila ada record EventAttendance status
     * 'hadir' → jumlah record; bila kosong → fallback data legacy
     * (attendance_male + attendance_female) supaya laporan lama tetap berfungsi.
     */
    public function getTotalAttendanceAttribute(): int
    {
        // AC-T2-10: bila event punya >=1 record attendance (berapapun statusnya,
        // termasuk semua tidak_hadir), total = jumlah record status 'hadir'
        // dan fallback legacy TIDAK dipakai. Fallback hanya saat tidak ada
        // record sama sekali (data lama manual L/P).
        if ($this->relationLoaded('attendances')) {
            $hasRecords = $this->attendances->isNotEmpty();
            $present = $this->attendances->where('status', 'hadir')->count();
        } else {
            $hasRecords = $this->attendances()->exists();
            $present = $this->attendances()->where('status', 'hadir')->count();
        }

        if ($hasRecords) {
            return $present;
        }

        return ($this->attendance_male ?? 0) + ($this->attendance_female ?? 0);
    }

    /**
     * Check-in massal (AC-T2-09): buat/restore record attendance untuk member.
     *
     * Blocker re-review Vera: member yang pernah di-soft-delete untuk event yang
     * sama TIDAK boleh dibuat ulang (melanggar UNIQUE(event_id, member_id)) —
     * record lama di-restore dan di-update (status/checked_in_at/checked_in_by).
     *
     * @param  array<int, int>  $memberIds
     * @return array{created: int, restored: int, skipped: int}
     */
    public function checkInMembers(array $memberIds): array
    {
        $memberIds = array_values(array_unique(array_map('intval', $memberIds)));

        $created = 0;
        $restored = 0;
        $skipped = 0;

        foreach ($memberIds as $memberId) {
            $existing = $this->attendances()
                ->withTrashed()
                ->where('member_id', $memberId)
                ->first();

            if (! $existing) {
                EventAttendance::checkInOrRestore([
                    'event_id' => $this->id,
                    'member_id' => $memberId,
                    'status' => 'hadir',
                ]);
                $created++;

                continue;
            }

            if ($existing->trashed()) {
                EventAttendance::checkInOrRestore([
                    'event_id' => $this->id,
                    'member_id' => $memberId,
                    'status' => 'hadir',
                ]);
                $restored++;

                continue;
            }

            $skipped++;
        }

        return [
            'created' => $created,
            'restored' => $restored,
            'skipped' => $skipped,
        ];
    }
}
