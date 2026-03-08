<?php

namespace App\Filament\Clusters\MasterData;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class MasterDataCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
    protected static ?string $navigationLabel = 'Data Referensi';
    protected static ?string $clusterBreadcrumb = 'Data Referensi';
}
