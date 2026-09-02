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

    /**
     * RBAC granular (Fase 2 Task 3 / AC-T3-08): cluster Jemaat dibuka untuk
     * siapa pun dengan permission member.view (defense in depth — bukan sekadar
     * menyembunyikan menu).
     */
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('member.view');
    }
}
