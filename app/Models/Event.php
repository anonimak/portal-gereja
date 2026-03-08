<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Attributes\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use BelongsToChurch, HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
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
    #[Attribute]
    protected function totalAttendance(): Attribute
    {
        return Attribute::make(
            get: fn($value): int => ($this->attendance_male ?? 0) + ($this->attendance_female ?? 0),
        );
    }
}
