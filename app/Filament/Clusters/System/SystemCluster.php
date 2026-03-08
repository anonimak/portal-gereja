<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class SystemCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'System';

    protected static ?string $clusterBreadcrumb = 'System';
}
