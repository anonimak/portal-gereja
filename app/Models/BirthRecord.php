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
 * Fase 3B T5 — Kelahiran & Akta Lahir (SPEC §2.1).
 *
 * Satu member = satu birth record (UNIQUE member_id). `birth_date`/`birth_place_full`
 * adalah salinan untuk dokumen; sumber utama tetap `members.birth_date`.
 * `father_name`/`mother_name` diisi default dari keluarga (kepala + istri), editable.
 */
class BirthRecord extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'member_id',
        'birth_order',
        'birth_place_full',
        'birth_date',
        'father_name',
        'mother_name',
        'certificate_number',
        'issued_at',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'issued_at' => 'date',
    ];

    /**
     * FK yang wajib satu gereja dengan record ini (HIGH-2 Vera / AC-LC-09).
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return ['member_id' => Member::class];
    }

    /**
     * Turunkan church_id dari member induk (AC-LC-10: super_admin membuat record
     * pada gereja B → church_id = gereja B, bukan gereja aktor).
     */
    protected function deriveChurchIdFromParent(): ?int
    {
        if ($this->member_id) {
            return Member::query()
                ->withoutGlobalScopes()
                ->find($this->member_id)
                ?->church_id;
        }

        return null;
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Default nilai dokumen dari member + keluarga (AC-LC-07):
     * birth_date/birth_place_full dari member; father/mother dari kepala keluarga & istri.
     * Semua nullable & editable — fallback string kosong bila keluarga tak punya kepala/istri.
     *
     * @return array<string, mixed>
     */
    public static function defaultsFor(Member $member): array
    {
        $fatherName = null;
        $motherName = null;

        if ($member->family) {
            $head = $member->family->members()
                ->where('family_relation', 'kepala_keluarga')
                ->first();
            $wife = $member->family->members()
                ->where('family_relation', 'istri')
                ->first();

            $fatherName = $head?->full_name;
            $motherName = $wife?->full_name;
        }

        return [
            'birth_date' => $member->birth_date?->format('Y-m-d'),
            'birth_place_full' => $member->birth_place,
            'father_name' => $fatherName,
            'mother_name' => $motherName,
        ];
    }
}
