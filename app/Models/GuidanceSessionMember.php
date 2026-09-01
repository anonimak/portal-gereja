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
 * Peserta per pertemuan bimbingan (pivot + kehadiran).
 * UNIQUE(session_id, member_id) × SoftDeletes → penambahan memakai
 * restore-or-create (pola AC-T2-17/18, AC-LC-03).
 */
class GuidanceSessionMember extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail, SoftDeletes;

    /**
     * FK yang harus satu gereja dengan pivot ini.
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return [
            'session_id' => GuidanceSession::class,
            'member_id' => Member::class,
        ];
    }

    /**
     * Church_id mengikuti gereja sesi-nya.
     */
    protected function deriveChurchIdFromParent(): ?int
    {
        if (! $this->session_id) {
            return null;
        }

        return GuidanceSession::query()
            ->withoutGlobalScopes()
            ->whereKey($this->session_id)
            ->value('church_id');
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'session_id',
        'member_id',
        'attended',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'attended' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GuidanceSession::class, 'session_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * Restore-or-create peserta (AC-LC-03 / pola T2 §1.4):
     * - record aktif sudah ada → lewati (kembalikan null, tidak ubah apa pun);
     * - record soft-deleted → restore dan isi ulang attended;
     * - belum ada → create baru.
     *
     * Pencarian SELALU dalam scope church_id (global scope BelongsToChurch).
     *
     * @return GuidanceSessionMember|null record aktif (baru/restored) atau null bila duplikat aktif.
     */
    public static function checkInOrRestore(int $sessionId, int $memberId, bool $attended = false, ?string $notes = null): ?self
    {
        $existing = static::query()
            ->withTrashed()
            ->where('session_id', $sessionId)
            ->where('member_id', $memberId)
            ->first();

        if ($existing && ! $existing->trashed()) {
            // Duplikat aktif — lewati tanpa perubahan.
            return null;
        }

        if ($existing && $existing->trashed()) {
            $existing->restore();
            $existing->update([
                'attended' => $attended,
                'notes' => $notes,
            ]);

            return $existing->fresh();
        }

        return static::create([
            'session_id' => $sessionId,
            'member_id' => $memberId,
            'attended' => $attended,
            'notes' => $notes,
        ]);
    }
}
