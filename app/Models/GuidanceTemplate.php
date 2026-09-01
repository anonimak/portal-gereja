<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use App\Traits\RecordsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Template Topik Bimbingan — SATU template = SATU kumpulan topik pertemuan 1..N
 * (mis. Pra-Sidi 12 sesi). Data gereja (church_id), bukan global.
 */
class GuidanceTemplate extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'type',
        'name',
        'session_count',
        'is_default',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'session_count' => 'integer',
        'is_default' => 'boolean',
    ];

    /**
     * Topik-topik template berurutan (session_number 1..N).
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(GuidanceTemplateSession::class, 'template_id')
            ->orderBy('session_number');
    }

    /**
     * Program yang memakai template ini (informasional).
     */
    public function programs(): HasMany
    {
        return $this->hasMany(GuidanceProgram::class, 'template_id');
    }
}
