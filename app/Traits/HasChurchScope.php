<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Church;
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
        if (isset($this->churchSelect) && $this->churchSelect !== null) {
            return (int) $this->churchSelect;
        }

        return ChurchContext::activeChurchId();
    }

    /**
     * Nama gereja aktif untuk kop laporan.
     */
    protected function activeChurchName(): string
    {
        $active = $this->activeChurchId();
        if ($active !== null) {
            $church = Church::query()->withoutGlobalScopes()->find($active);
            return $church?->name ?? 'Gereja';
        }

        return ChurchContext::churchName();
    }

    /**
     * Model Church aktif untuk kop dokumen cetak/PDF.
     */
    protected function activeChurchModel(): ?Church
    {
        $active = $this->activeChurchId();

        if ($active === null) {
            return null;
        }

        return Church::query()->withoutGlobalScopes()->find($active);
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
            $builder->where($builder->getModel()->getTable() . '.church_id', $active);
        }

        return $builder;
    }
}
