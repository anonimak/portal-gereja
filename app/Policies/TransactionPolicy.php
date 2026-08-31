<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Policy untuk model tenant yang mengikuti aturan base TenantPolicy.
 *
 * finance_admin diizinkan mengelola transaksi keuangan gereja sendiri
 * (AC-T2-03 — BLOCK-1 Vera).
 */
class TransactionPolicy extends TenantPolicy
{
    protected array $allowedRoles = ['super_admin', 'church_admin', 'finance_admin'];
}
