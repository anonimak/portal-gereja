<?php

namespace App\Filament\Clusters\Reporting;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class ReportingCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;
    protected static ?string $navigationLabel = 'Laporan';
    protected static ?string $clusterBreadcrumb = 'Laporan';

    /**
     * RBAC granular (AC-T3-08): Laporan dibuka jika user punya akses salah satu
     * halaman laporan (Warta ATAU Rapat).
     */
    public static function canAccess(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return auth()->user()->hasPermission('report.warta.view')
            || auth()->user()->hasPermission('report.rapat.view');
    }
}
