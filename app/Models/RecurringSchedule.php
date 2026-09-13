<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\RecurringEventService;
use App\Traits\BelongsToChurch;
use App\Traits\RecordsAuditTrail;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringSchedule extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail, SoftDeletes;

    /**
     * Kolom FK yang harus satu gereja dengan jadwal ini.
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return [
            'category_id' => EventCategory::class,
        ];
    }

    /**
     * Turunkan church_id dari kategori induk ketika church_id belum terisi.
     */
    protected function deriveChurchIdFromParent(): ?int
    {
        if ($this->category_id) {
            return EventCategory::withoutGlobalScopes()->find($this->category_id)?->church_id;
        }

        return null;
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'category_id',
        'title',
        'description',
        'location',
        'frequency',
        'interval',
        'days_of_week',
        'start_date',
        'start_time',
        'end_time',
        'end_type',
        'until_date',
        'repeat_count',
        'is_active',
        'last_generated_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'days_of_week' => 'array',
        'start_date' => 'date',
        'until_date' => 'date',
        'is_active' => 'boolean',
        'interval' => 'integer',
        'repeat_count' => 'integer',
        'last_generated_at' => 'datetime',
    ];

    /**
     * Kategori acara.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }

    /**
     * Daftar acara yang digenerate oleh jadwal berulang ini.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'recurring_schedule_id');
    }

    /**
     * Hitung jadwal kejadian (occurrences) tanpa menyimpannya ke database.
     *
     * @return array<int, array{start: \Carbon\Carbon, end: \Carbon\Carbon}>
     */
    public function calculateOccurrences(?CarbonInterface $until = null, int $maxLimit = 52): array
    {
        return app(RecurringEventService::class)->calculateOccurrences($this, $until, $maxLimit);
    }

    /**
     * Generate acara nyata (Event records) dari jadwal berulang ini.
     *
     * @return \Illuminate\Support\Collection<int, Event>
     */
    public function generateEvents(?CarbonInterface $until = null, int $maxLimit = 52): \Illuminate\Support\Collection
    {
        return app(RecurringEventService::class)->generateEvents($this, $until, $maxLimit);
    }
}

// Alias untuk fleksibilitas kompatibilitas penamaan
if (! class_exists(\App\Models\RecurringPattern::class, false)) {
    class_alias(RecurringSchedule::class, \App\Models\RecurringPattern::class);
}
