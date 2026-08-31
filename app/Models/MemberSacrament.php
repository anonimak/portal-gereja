<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberSacrament extends Model
{
    use BelongsToChurch, HasFactory;

    /**
     * Kolom FK yang harus satu gereja dengan sakramen ini (HIGH-2 Vera).
     *
     * @return array<string, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return [
            'member_id' => Member::class,
            'official_id' => Official::class,
        ];
    }

    /**
     * Church_id sakramen mengikuti gereja member-nya (untuk super_admin yang
     * menambah sakramen pada member gereja lain via RelationManager).
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
        'type',
        'sacrament_date',
        'official_id',
        'certificate_number',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sacrament_date' => 'date',
    ];

    /**
     * Member that this sacrament record belongs to.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Official that ministered this sacrament.
     */
    public function official(): BelongsTo
    {
        return $this->belongsTo(Official::class);
    }
}
