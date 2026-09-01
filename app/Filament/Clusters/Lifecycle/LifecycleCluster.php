<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

/**
 * Fase 3B — Siklus hidup gereja (Kelahiran → Baptis → Sidi → Nikah → Kematian).
 * T5: modul Kelahiran + Akta Lahir. T7: Bimbingan Pra-Sidi/Pra-Nikah.
 */
class LifecycleCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Lifecycle';

    protected static ?string $clusterBreadcrumb = 'Lifecycle';

    protected static ?int $navigationSort = 6;
}
