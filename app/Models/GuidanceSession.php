<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use App\Traits\RecordsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Satu pertemuan bimbingan (penjadwalan, pembimbing, lokasi, topik).
 */
class GuidanceSession extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail, SoftDeletes;

    /**
     * FK yang harus satu gereja dengan sesi ini.
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return [
            'program_id' => GuidanceProgram::class,
            'official_id' => Official::class,
        ];
    }

    /**
     * Church_id mengikuti gereja program-nya.
     */
    protected function deriveChurchIdFromParent(): ?int
    {
        if (! $this->program_id) {
            return null;
        }

        return GuidanceProgram::query()
            ->withoutGlobalScopes()
            ->whereKey($this->program_id)
            ->value('church_id');
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'program_id',
        'title',
        'session_at',
        'location',
        'official_id',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'session_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(GuidanceProgram::class, 'program_id');
    }

    public function official(): BelongsTo
    {
        return $this->belongsTo(Official::class, 'official_id');
    }

    /**
     * Baris pivot peserta (dengan kehadiran).
     */
    public function participantRows(): HasMany
    {
        return $this->hasMany(GuidanceSessionMember::class, 'session_id');
    }

    /**
     * Anggota peserta sesi ini (via pivot).
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'guidance_session_members', 'session_id', 'member_id')
            ->withPivot(['attended', 'notes'])
            ->withTimestamps();
    }
}
