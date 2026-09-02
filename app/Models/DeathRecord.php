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
 * Kematian anggota (Surat Keterangan Kematian) — Fase 3B T11.
 *
 * Side effect (AC-LC-05): saat record dibuat -> member.status = 'meninggal'.
 * Restore/hapus tidak mengembalikan status (asumsi A8).
 */
class DeathRecord extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail, SoftDeletes;

    /**
     * FK yang harus satu gereja dengan catatan kematian (AC-LC-09).
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return [
            'member_id' => Member::class,
            'official_id' => Official::class,
            'event_id' => Event::class,
        ];
    }

    /**
     * Church_id mengikuti gereja member yang meninggal.
     */
    protected function deriveChurchIdFromParent(): ?int
    {
        if (! $this->member_id) {
            return null;
        }

        return Member::query()
            ->withoutGlobalScopes()
            ->whereKey($this->member_id)
            ->value('church_id');
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'member_id',
        'death_date',
        'burial_date',
        'burial_location',
        'official_id',
        'event_id',
        'certificate_number',
        'issued_at',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'death_date' => 'date',
        'burial_date' => 'date',
        'issued_at' => 'date',
    ];

    protected static function booted(): void
    {
        // AC-LC-05: status anggota berubah menjadi 'meninggal' saat dicatat.
        static::created(function (DeathRecord $record): void {
            if (! $record->member_id) {
                return;
            }

            Member::query()
                ->withoutGlobalScopes()
                ->whereKey($record->member_id)
                ->update(['status' => 'meninggal']);
        });
    }

    /**
     * Anggota yang meninggal.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * Pendeta yang melayani ibadah pemakaman.
     */
    public function official(): BelongsTo
    {
        return $this->belongsTo(Official::class, 'official_id');
    }

    /**
     * Event ibadah pemakaman (opsional).
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
