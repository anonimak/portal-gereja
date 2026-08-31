<?php

declare(strict_types=1);

namespace App\Traits;

use App\Support\ChurchContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dipakai halaman laporan (Fase 3A): resolver gereja aktif + helper nama.
 */
trait HasChurchScope
{
    /**
     * Gereja aktif (int) atau null = All (super_admin).
     */
    protected function activeChurchId(): ?int
    {
        return ChurchContext::activeChurchId();
    }

    /**
     * Nama gereja aktif untuk kop laporan.
     */
    protected function activeChurchName(): string
    {
        return ChurchContext::churchName();
    }

    /**
     * Terapkan scope ke gereja aktif pada query laporan.
     *
     * Pemilih gereja super_admin (§9) HANYA berlaku untuk query laporan
     * (bukan resource CRUD) — global scope BelongsToChurch tidak ikut session.
     *
     * - super_admin pilih gereja → filter ke gereja itu.
     * - super_admin "All" (null) → tanpa filter (lihat semua).
     * - role lain → gereja sendiri (redundan dgn global scope, aman).
     */
    protected function scopeToActiveChurch(Builder $builder): Builder
    {
        $active = $this->activeChurchId();

        if ($active !== null) {
            $builder->where('church_id', $active);
        }

        return $builder;
    }
}
