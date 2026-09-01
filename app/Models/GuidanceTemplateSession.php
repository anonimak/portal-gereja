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
 * Satu baris topik milik template (session_number = urutan pertemuan 1..N).
 */
class GuidanceTemplateSession extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail, SoftDeletes;

    /**
     * FK yang harus satu gereja dengan baris template ini.
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return ['template_id' => GuidanceTemplate::class];
    }

    /**
     * Church_id mengikuti gereja template-nya.
     */
    protected function deriveChurchIdFromParent(): ?int
    {
        if (! $this->template_id) {
            return null;
        }

        return GuidanceTemplate::query()
            ->withoutGlobalScopes()
            ->whereKey($this->template_id)
            ->value('church_id');
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'template_id',
        'session_number',
        'topic',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'session_number' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(GuidanceTemplate::class, 'template_id');
    }
}
