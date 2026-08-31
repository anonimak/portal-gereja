<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LOW-4 (Fase 2 Task 3): ubah FK officials.member_id dari cascadeOnDelete
 * menjadi restrictOnDelete — forceDelete Member TIDAK menghapus data jabatan
 * historis (AC-T3-19).
 *
 * Migrasi BARU — migrasi lama (2026_03_08_000001) tidak diubah.
 * Portabel SQLite & MySQL (pola sama dengan 2026_03_09_000008).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('officials', function (Blueprint $table): void {
            $table->dropForeign(['member_id']);
        });

        Schema::table('officials', function (Blueprint $table): void {
            $table->foreign('member_id')->references('id')->on('members')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('officials', function (Blueprint $table): void {
            $table->dropForeign(['member_id']);
        });

        Schema::table('officials', function (Blueprint $table): void {
            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
        });
    }
};
