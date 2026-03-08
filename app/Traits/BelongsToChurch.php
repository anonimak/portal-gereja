<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Church;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToChurch
{
    /**
     * Relationship to Church.
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * Boot the trait: add global scope and creating event.
     */
    public static function bootBelongsToChurch(): void
    {
        static::addGlobalScope('church', function (Builder $builder) {
            if (auth()->check() && auth()->user()->role !== 'super_admin') {
                $builder->where('church_id', auth()->user()->church_id);
            }
        });

        static::creating(function ($model) {
            // Auto-assign church_id from authenticated user if not already set
            if (auth()->check() && empty($model->church_id)) {
                $model->church_id = auth()->user()->church_id;
            }
        });
    }
}
