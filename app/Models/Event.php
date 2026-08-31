<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\AuditsActivity;
use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use AuditsActivity, BelongsToChurch, HasFactory, SoftDeletes;

    /**
     * Kolom FK yang harus satu gereja dengan event ini (HIGH-2 Vera).
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return ['category_id' => EventCategory::class];
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'category_id',
        'title',
        'start_datetime',
        'end_datetime',
        'location',
        'attendance_male',
        'attendance_female',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
    ];

    /**
     * Event category that this event belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
    }

    /**
     * Rosters (duty assignments) for this event.
     */
    public function rosters(): HasMany
    {
        return $this->hasMany(EventRoster::class);
    }

    /**
     * Calculate total attendance from male and female attendees.
     */
    public function getTotalAttendanceAttribute(): int
    {
        return ($this->attendance_male ?? 0) + ($this->attendance_female ?? 0);
    }
}
