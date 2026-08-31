<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MED Vera (re-review PR #1): church_id pada member_sacraments & event_rosters
 * masih nullable (migrasi 2026_03_09_000001 menambah kolom nullable). Semua tabel
 * tenant lain NOT NULL. Migrasi ini:
 * 1. Backfill church_id yang masih NULL dari parent (member → church_id, event → church_id),
 *    baris orphan/unknown dikelompokkan aman ke gereja pertama (TANPA memindahkan
 *    data antar gereja — sama seperti fix events.church_id di 2026_03_09_000003).
 * 2. Ubah kolom menjadi NOT NULL.
 *
 * Portabilitas: backfill baris-per-baris di PHP (berjalan di MySQL & SQLite).
 *
 * Fix MySQL Error 1830 ("Column 'church_id' cannot be NOT NULL: needed in a
 * foreign key constraint"): MySQL melarang kolom yang dipakai FK diubah jadi
 * NOT NULL selama FK masih terpasang dengan referential action SET NULL.
 * Pola aman & portabel (SQLite/MySQL):
 *   drop FK → alter kolom NOT NULL → re-add FK dengan semantik yang benar.
 *
 * Catatan: FK re-add memakai restrictOnDelete() (ON DELETE RESTRICT), BUKAN
 * nullOnDelete() — karena kolom sudah NOT NULL, action SET NULL akan kontradiktif
 * dan gagal saat parent dihapus. RESTRICT menjaga integritas (mencegah orphan).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------- Backfill member_sacraments ----------
        $sacraments = DB::table('member_sacraments')->whereNull('church_id')->get(['id', 'member_id']);
        foreach ($sacraments as $sacrament) {
            $churchId = $sacrament->member_id
                ? DB::table('members')->where('id', $sacrament->member_id)->value('church_id')
                : null;

            if ($churchId !== null) {
                DB::table('member_sacraments')->where('id', $sacrament->id)->update(['church_id' => $churchId]);
            }
        }

        // ---------- Backfill event_rosters ----------
        $rosters = DB::table('event_rosters')->whereNull('church_id')->get(['id', 'event_id']);
        foreach ($rosters as $roster) {
            $churchId = $roster->event_id
                ? DB::table('events')->where('id', $roster->event_id)->value('church_id')
                : null;

            if ($churchId !== null) {
                DB::table('event_rosters')->where('id', $roster->id)->update(['church_id' => $churchId]);
            }
        }

        // ---------- Sisa NULL (orphan tanpa parent): kelompokkan aman ke gereja pertama ----------
        $firstChurchId = DB::table('churches')->orderBy('id')->value('id');
        if ($firstChurchId !== null) {
            DB::table('member_sacraments')->whereNull('church_id')->update(['church_id' => $firstChurchId]);
            DB::table('event_rosters')->whereNull('church_id')->update(['church_id' => $firstChurchId]);
        }

        // ---------- Jadikan NOT NULL: drop FK → change → re-add FK (MySQL Error 1830 fix) ----------
        $this->makeNotNullWithForeignKey('member_sacraments');
        $this->makeNotNullWithForeignKey('event_rosters');
    }

    /**
     * Ubah kolom church_id menjadi NOT NULL pada tabel child yang memiliki FK
     * ke churches. MySQL melarang alter NOT NULL selama FK aktif (terutama FK
     * dengan ON DELETE SET NULL), jadi FK dilepas dulu lalu dipasang ulang
     * dengan action RESTRICT (aman untuk kolom NOT NULL).
     */
    private function makeNotNullWithForeignKey(string $table): void
    {
        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->dropForeign(['church_id']);
        });

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->unsignedBigInteger('church_id')->nullable(false)->change();
        });

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->foreign('church_id')->references('id')->on('churches')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Balikkan: drop FK RESTRICT → kolom nullable → re-add FK SET NULL (semantik awal)
        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->dropForeign(['church_id']);
            $table->unsignedBigInteger('church_id')->nullable()->change();
            $table->foreign('church_id')->references('id')->on('churches')->nullOnDelete();
        });
        Schema::table('event_rosters', function (Blueprint $table): void {
            $table->dropForeign(['church_id']);
            $table->unsignedBigInteger('church_id')->nullable()->change();
            $table->foreign('church_id')->references('id')->on('churches')->nullOnDelete();
        });
    }
};
