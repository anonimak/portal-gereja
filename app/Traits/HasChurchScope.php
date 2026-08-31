<?php

declare(strict_types=1);

namespace App\Traits;

use App\Support\ChurchContext;

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
}
