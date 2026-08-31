<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft delete (Fase 2): tambah kolom deleted_at ke semua tabel tenant yang
 * menyimpan data historis (jemaat, keuangan, acara, referensi). Church & User
 * sengaja TIDAK di-soft-delete (identitas & akses).
 */
return new class extends Migration
{
    /**
     * Tabel tenant yang dilindungi soft delete.
     *
     * @var array<int, string>
     */
    private const TABLES = [
        'families',
        'members',
        'member_sacraments',
        'transactions',
        'funds',
        'financial_categories',
        'event_categories',
        'ministry_roles',
        'events',
        'event_rosters',
        'officials',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
