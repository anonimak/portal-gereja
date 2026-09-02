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

    /**
     * RBAC granular (AC-T3-08): Data Referensi dibuka jika user punya akses
     * salah satu master data (event ATAU finance).
     */
    public static function canAccess(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return auth()->user()->hasPermission('master.event.view')
            || auth()->user()->hasPermission('master.finance.view');
    }
}
