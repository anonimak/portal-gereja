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

        // ---------- Jadikan NOT NULL ----------
        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->unsignedBigInteger('church_id')->nullable(false)->change();
        });
        Schema::table('event_rosters', function (Blueprint $table): void {
            $table->unsignedBigInteger('church_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->unsignedBigInteger('church_id')->nullable()->change();
        });
        Schema::table('event_rosters', function (Blueprint $table): void {
            $table->unsignedBigInteger('church_id')->nullable()->change();
        });
    }
};
