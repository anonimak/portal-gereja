<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 3B T8 — Sidi/Baptis Dewasa.
 *
 * Tambah kolom program_id (bimbingan pra-sidi) pada member_sacraments agar
 * penerbitan sakramen sidi/baptis_dewasa dapat menautkan program bimbingan
 * yang diselesaikan (spec §2.9, AC-LC-06). Portabel SQLite & MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_sacraments', function (Blueprint $table) {
            $table->foreignId('program_id')
                ->nullable()
                ->after('official_id')
                ->constrained('guidance_programs')
                ->nullOnDelete()
                ->index('member_sacraments_program_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('member_sacraments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
        });
    }
};
