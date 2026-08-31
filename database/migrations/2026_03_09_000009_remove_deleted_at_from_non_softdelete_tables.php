<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H2 Vera + keputusan Nova: soft delete HANYA untuk 5 model historis inti
 * (Member, Transaction, Event, MemberSacrament, EventRoster).
 * Migrasi ini menghapus kolom deleted_at (yang ditambahkan 000006) dari tabel
 * yang tidak lagi soft-delete: families, funds, financial_categories,
 * event_categories, ministry_roles, officials.
 *
 * Tidak ada data hilang: kolom deleted_at berisi NULL (belum pernah soft-delete
 * di lingkungan ini) dan tidak dipakai query apa pun setelah trait dilepas.
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private const TABLES = [
        'families',
        'funds',
        'financial_categories',
        'event_categories',
        'ministry_roles',
        'officials',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasColumn($tableName, 'deleted_at')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }
};
