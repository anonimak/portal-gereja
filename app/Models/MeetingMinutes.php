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
 * Notulen rapat substantif (Fase 3A §8).
 */
class MeetingMinutes extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail, SoftDeletes;

    /**
     * FK yang wajib satu gereja dengan notulen ini.
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return ['event_id' => Event::class];
    }

    /**
     * church_id notulen mengikuti gereja event-nya.
     */
    protected function deriveChurchIdFromParent(): ?int
    {
        if (! $this->event_id) {
            return null;
        }

        return Event::query()
            ->withoutGlobalScopes()
            ->whereKey($this->event_id)
            ->value('church_id');
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'event_id',
        'title',
        'meeting_date',
        'agenda',
        'participants',
        'notes',
        'decisions',
        'attachments',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'meeting_date' => 'date',
        'agenda' => 'array',
        'participants' => 'array',
        'decisions' => 'array',
        'attachments' => 'array',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
