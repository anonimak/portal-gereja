<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Official extends Model
{
    use BelongsToChurch, HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
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
     * Get the display name of the official.
     * - For Pelayan Tamu: returns "external_name (origin_church)"
     * - For Majelis Lokal: returns member's full name
     * - For Pendeta Internal: returns external_name
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->type === 'pelayan_tamu' && $this->origin_church) {
            return "{$this->external_name} ({$this->origin_church})";
        }

        if ($this->member_id && $this->member) {
            return $this->member->full_name;
        }

        return $this->external_name ?? 'Unknown';
    }
}
