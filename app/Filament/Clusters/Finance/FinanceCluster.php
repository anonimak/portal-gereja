<?php

namespace App\Filament\Clusters\Finance;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class FinanceCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowsRightLeft;
    protected static ?string $navigationLabel = 'Keuangan';
    protected static ?string $clusterBreadcrumb = 'Keuangan';
}
