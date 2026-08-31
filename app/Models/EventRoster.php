<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\AuditsActivity;
use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventRoster extends Model
{
    use AuditsActivity, BelongsToChurch, HasFactory, SoftDeletes;

    /**
     * Kolom FK yang harus satu gereja dengan roster ini (HIGH-2 Vera).
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return [
            'event_id' => Event::class,
            'member_id' => Member::class,
            'role_id' => MinistryRole::class,
            'official_id' => Official::class,
        ];
    }

    /**
     * Church_id roster mengikuti gereja event-nya (untuk super_admin yang
     * menambah roster pada event gereja lain via form Repeater).
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
        'member_id',
        'role_id',
        'official_id',
    ];

    /**
     * Event that this roster assignment belongs to.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Member assigned to this roster.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Ministry role for this roster assignment.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(MinistryRole::class);
    }

    /**
     * Official assigned to this roster (if applicable).
     */
    public function official(): BelongsTo
    {
        return $this->belongsTo(Official::class);
    }
}
