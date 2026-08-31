<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AC-T1-08 (MED — Vera): events.church_id dibuat nullable di migrasi awal,
 * padahal semua tabel tenant lain NOT NULL. Migrasi ini:
 * 1. Backfill events.church_id yang NULL (dari roster terkait bila ada, fallback
 *    aman ke gereja pertama — TANPA memindahkan data antar gereja; baris NULL
 *    hanyalah data orphan/unknown).
 * 2. Ubah kolom menjadi NOT NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Backfill dari roster (event yang punya petugas → ikut gereja roster).
        DB::statement('
            UPDATE events
            SET church_id = (
                SELECT MIN(r.church_id)
                FROM event_rosters AS r
                WHERE r.event_id = events.id
                  AND r.church_id IS NOT NULL
            )
            WHERE church_id IS NULL
        ');

        // 2. Sisa NULL (event tanpa roster): kelompokkan aman ke gereja pertama.
        //    Tidak mungkin di-backfill dari data; NULL harus diisi agar NOT NULL.
        $firstChurchId = DB::table('churches')->orderBy('id')->value('id');
        if ($firstChurchId !== null) {
            DB::table('events')
                ->whereNull('church_id')
                ->update(['church_id' => $firstChurchId]);
        }

        // 3. Jadikan NOT NULL.
        Schema::table('events', function (Blueprint $table): void {
            $table->unsignedBigInteger('church_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->unsignedBigInteger('church_id')->nullable()->change();
        });
    }
};
