<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Audit trail entry — append-only log untuk perubahan model sensitif.
 */
class AuditLog extends Model
{
    /**
     * Log audit bersifat append-only: tidak ada kolom updated_at.
     */
    public const UPDATED_AT = null;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'church_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Isolasi tenant audit (AC-TN-01/02): non-super_admin hanya melihat baris
     * audit gereja sendiri; super_admin melihat semua.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('church', function (Builder $builder): void {
            $user = auth()->user();

            if ($user && $user->role !== 'super_admin') {
                $builder->where('church_id', $user->church_id);
            }
        });
    }

    /**
     * Record yang diaudit (polimorfik).
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * User yang melakukan aksi (nullable untuk aksi dari console/seeder).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Gereja tempat aksi terjadi.
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
