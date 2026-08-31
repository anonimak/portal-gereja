<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * User yang melakukan aksi (nullable untuk aksi dari console/seeder).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
