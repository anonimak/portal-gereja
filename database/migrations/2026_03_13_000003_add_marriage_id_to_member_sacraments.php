<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 3B T9 — Sakramen nikah.
 *
 * Tambah kolom marriage_id pada member_sacraments sebagai penanda sakramen
 * nikah yang dibuat otomatis dari record Marriage (spec §2.9).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_sacraments', function (Blueprint $table) {
            $table->foreignId('marriage_id')
                ->nullable()
                ->after('official_id')
                ->constrained('marriages')
                ->nullOnDelete();
            $table->index('marriage_id', 'member_sacraments_marriage_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('member_sacraments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('marriage_id');
        });
    }
};
