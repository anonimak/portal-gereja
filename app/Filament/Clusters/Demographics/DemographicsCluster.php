<?php

namespace App\Filament\Clusters\Demographics;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class DemographicsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
    protected static ?string $navigationLabel = 'Jemaat';
    protected static ?string $clusterBreadcrumb = 'Jemaat';
}
