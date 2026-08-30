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
            $actor = auth()->user();

            // Non-super_admin DIPAKSA menulis ke gereja sendiri — menutup celah
            // mass-assignment yang mengarahkan church_id ke gereja lain.
            if ($actor && $actor->role !== 'super_admin') {
                $model->church_id = $actor->church_id;
            }

            // Tanpa aktor terautentikasi: isi otomatis jika kosong (fallback).
            if (empty($model->church_id) && $actor) {
                $model->church_id = $actor->church_id;
            }
        });
    }
}
