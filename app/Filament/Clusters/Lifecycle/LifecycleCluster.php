<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class LifecycleCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $navigationLabel = 'Lifecycle';

    protected static ?string $clusterBreadcrumb = 'Lifecycle';

    protected static ?int $navigationSort = 6;
}
