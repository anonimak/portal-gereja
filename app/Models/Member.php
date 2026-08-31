<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use App\Traits\RecordsAuditTrail;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail, SoftDeletes;

    /**
     * Kolom FK yang harus satu gereja dengan member ini (HIGH-2 Vera).
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return ['family_id' => Family::class];
    }

    /**
     * C2 Vera: soft delete/restore member ikut menimpa sakramen anak (member_sacraments)
     * supaya tidak ada orphan aktif saat member dihapus, dan kembali saat di-restore.
     */
    public static function booted(): void
    {
        static::deleted(function (Member $member): void {
            if ($member->isForceDeleting()) {
                return; // hard delete — cascade DB menangani anak.
            }

            $member->sacraments()->withTrashed()->delete();
        });

        static::restored(function (Member $member): void {
            $member->sacraments()->withTrashed()->restore();
        });
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'family_id',
        'id_card_number',
        'full_name',
        'gender',
        'birth_place',
        'birth_date',
        'family_relation',
        'status',
        'custom_fields',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'custom_fields' => AsArrayObject::class,
    ];

    /**
     * Family that this member belongs to.
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * Sacraments (spiritual records) for this member.
     */
    public function sacraments(): HasMany
    {
        return $this->hasMany(MemberSacrament::class);
    }

    /**
     * Official position(s) held by this member (if Majelis).
     */
    public function official(): HasMany
    {
        return $this->hasMany(Official::class);
    }
}
