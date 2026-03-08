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
}
