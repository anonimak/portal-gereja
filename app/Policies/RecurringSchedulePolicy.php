<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Policy untuk RecurringSchedule (Jadwal Berulang) berbasis permission event.
 */
class RecurringSchedulePolicy extends TenantPolicy
{
    protected static string $module = 'event';
}
