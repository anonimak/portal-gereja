<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Policy master data keuangan (FinancialCategory) — RBAC granular (Fase 2 Task 3).
 *
 * Modul `master.finance`: finance_admin diizinkan (AC-T2-03 — BLOCK-1 Vera).
 */
class FinancialCategoryPolicy extends TenantPolicy
{
    protected static string $module = 'master.finance';
}
