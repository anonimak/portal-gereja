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

/**
 * Pernikahan (Akta Nikah) — Fase 3B T9.
 *
 * Pencatatan pernikahan dua anggota (suami & istri) + dokumen Akta Nikah.
 * Saat record dibuat, otomatis membuat 2 baris member_sacraments type 'nikah'
 * (satu per pasangan) dengan marriage_id terisi (AC-LC-04) -> otomatis muncul
 * di Warta Jemaat & riwayat sakramen masing-masing.
 */
class Marriage extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail, SoftDeletes;

    /**
     * FK yang harus satu gereja dengan pernikahan (AC-LC-09).
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return [
            'husband_member_id' => Member::class,
            'wife_member_id' => Member::class,
            'official_id' => Official::class,
            'program_id' => GuidanceProgram::class,
        ];
    }

    /**
     * Church_id mengikuti gereja suami (deriveChurchIdFromParent).
     */
    protected function deriveChurchIdFromParent(): ?int
    {
        if (! $this->husband_member_id) {
            return null;
        }

        return Member::query()
            ->withoutGlobalScopes()
            ->whereKey($this->husband_member_id)
            ->value('church_id');
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'husband_member_id',
        'wife_member_id',
        'marriage_date',
        'official_id',
        'location',
        'witness_names',
        'program_id',
        'certificate_number',
        'issued_at',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'marriage_date' => 'date',
        'issued_at' => 'date',
        'witness_names' => 'array',
    ];

    protected static function booted(): void
    {
        // AC-LC-04: setiap pernikahan otomatis mencatat 2 sakramen 'nikah'
        // (suami & istri). Jalankan setelah create supaya id tersedia.
        static::created(function (Marriage $marriage): void {
            $marriage->syncSacraments();
        });

        // Saat marriage di-soft-delete, sakramen nikah-nya ikut di-soft-delete
        // (konsisten cascade soft-delete pola Member->MemberSacrament).
        static::deleted(function (Marriage $marriage): void {
            if ($marriage->isForceDeleting()) {
                return;
            }

            $marriage->sacraments()->get()->each->delete();
        });
    }

    /**
     * Relasi suami.
     */
    public function husband(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'husband_member_id');
    }

    /**
     * Relasi istri.
     */
    public function wife(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'wife_member_id');
    }

    /**
     * Pendeta pemberkatan.
     */
    public function official(): BelongsTo
    {
        return $this->belongsTo(Official::class, 'official_id');
    }

    /**
     * Program bimbingan pra-nikah yang diselesaikan (opsional).
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(GuidanceProgram::class, 'program_id');
    }

    /**
     * Sakramen nikah (2 baris — satu per pasangan).
     */
    public function sacraments(): HasMany
    {
        return $this->hasMany(MemberSacrament::class, 'marriage_id');
    }

    /**
     * Sinkronkan 2 baris member_sacraments type 'nikah' (AC-LC-04).
     *
     * Idempotent: updateOrCreate per (marriage_id, member_id).
     */
    public function syncSacraments(): void
    {
        $couple = [
            ['member_id' => $this->husband_member_id],
            ['member_id' => $this->wife_member_id],
        ];

        foreach ($couple as $row) {
            if (! $row['member_id']) {
                continue;
            }

            MemberSacrament::query()
                ->withoutGlobalScopes()
                ->updateOrCreate(
                    [
                        'marriage_id' => $this->id,
                        'member_id' => $row['member_id'],
                    ],
                    [
                        'church_id' => $this->church_id,
                        'type' => 'nikah',
                        'sacrament_date' => $this->marriage_date,
                        'official_id' => $this->official_id,
                        'certificate_number' => $this->certificate_number,
                        'issued_at' => $this->issued_at,
                    ]
                );
        }
    }
}
