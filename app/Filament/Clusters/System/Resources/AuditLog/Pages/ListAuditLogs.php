<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Resources\AuditLog\Pages;

use App\Filament\Clusters\System\Resources\AuditLog\AuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;
}
