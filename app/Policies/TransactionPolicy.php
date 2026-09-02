<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Policy untuk model tenant yang mengikuti aturan base TenantPolicy.
 *
 * (AC-T2-03 — BLOCK-1 Vera).
 */
class TransactionPolicy extends TenantPolicy
{

    protected static string $module = 'finance';
}
