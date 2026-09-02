<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use App\Traits\RecordsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Official extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail;

    /**
     * Kolom FK yang harus satu gereja dengan official ini (HIGH-2 Vera).
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return ['member_id' => Member::class];
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'type',
        'member_id',
        'external_name',
        'origin_church',
        'start_date',
        'end_date',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Member that this official is assigned to (for Majelis Lokal only).
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Status keaktifan jabatan (LOW-4 / AC-T3-16..18):
     *
     * - end_date terisi dan sudah lewat / hari ini → Nonaktif.
     * - Untuk majelis_lokal: member terkait di-soft-delete → Nonaktif.
     * - Selain itu → Aktif.
     */
    public function getIsActiveAttribute(): bool
    {
        // LOW-4: begitu end_date diisi (termasuk hari ini saat member dihapus),
        // jabatan dianggap sudah berakhir — restore member TIDAK mengaktifkan lagi.
        if ($this->end_date !== null && ! $this->end_date->isFuture()) {
            return false;
        }

        if ($this->type === 'majelis_lokal' && $this->member_id) {
            $member = $this->member()->withTrashed()->first();

            if ($member === null || $member->trashed()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the display name of the official.
     * - For Pelayan Tamu: returns "external_name (origin_church)"
     * - For Majelis Lokal: returns member's full name (+ "(Nonaktif)" jika trashed)
     * - For Pendeta Internal: returns external_name
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->type === 'pelayan_tamu' && $this->origin_church) {
            return "{$this->external_name} ({$this->origin_church})";
        }

        if ($this->member_id) {
            $member = $this->member()->withTrashed()->first();

            if ($member) {
                $suffix = $member->trashed() ? ' (Nonaktif)' : '';

                return $member->full_name . $suffix;
            }
        }

        return $this->external_name ?? 'Unknown';
    }
}
