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
 * Portabilitas: backfill baris-per-baris di PHP (berjalan di MySQL & SQLite),
 * NOT NULL via change() (didukung Laravel 12 di SQLite/MySQL).
 *
 * FIX (MySQL error 1830): FK yang dibuat di 2026_03_09_000001 memakai
 * nullOnDelete() (ON DELETE SET NULL) — MySQL MENOLAK kolom NOT NULL yang masih
 * terikat FK SET NULL. Solusi: drop FK terlebih dahulu, ubah kolom menjadi NOT
 * NULL, lalu pasang kembali FK dengan RESTRICT (kompatibel dengan kolom NOT NULL,
 * mencegah orphan). Tidak terdeteksi di CI karena test memakai SQLite yang tidak
 * menegakkan aturan ini seketat MySQL.
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

        // ---------- Drop FK (ON DELETE SET NULL tidak kompatibel dengan NOT NULL di MySQL) ----------
        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->dropForeign(['church_id']);
        });
        Schema::table('event_rosters', function (Blueprint $table): void {
            $table->dropForeign(['church_id']);
        });

        // ---------- Jadikan NOT NULL ----------
        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->unsignedBigInteger('church_id')->nullable(false)->change();
        });
        Schema::table('event_rosters', function (Blueprint $table): void {
            $table->unsignedBigInteger('church_id')->nullable(false)->change();
        });

        // ---------- Pasang kembali FK dengan RESTRICT (kompatibel NOT NULL, cegah orphan) ----------
        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->foreign('church_id')->references('id')->on('churches')->restrictOnDelete();
        });
        Schema::table('event_rosters', function (Blueprint $table): void {
            $table->foreign('church_id')->references('id')->on('churches')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Drop FK RESTRICT dulu agar kolom bisa dikembalikan ke nullable
        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->dropForeign(['church_id']);
        });
        Schema::table('event_rosters', function (Blueprint $table): void {
            $table->dropForeign(['church_id']);
        });

        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->unsignedBigInteger('church_id')->nullable()->change();
        });
        Schema::table('event_rosters', function (Blueprint $table): void {
            $table->unsignedBigInteger('church_id')->nullable()->change();
        });

        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->foreign('church_id')->references('id')->on('churches')->nullOnDelete();
        });
        Schema::table('event_rosters', function (Blueprint $table): void {
            $table->foreign('church_id')->references('id')->on('churches')->nullOnDelete();
        });
    }
};
