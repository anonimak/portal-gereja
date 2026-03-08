<?php

namespace App\Filament\Clusters\Events;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class EventsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;
    protected static ?string $navigationLabel = 'Acara';
    protected static ?string $clusterBreadcrumb = 'Acara';
}
