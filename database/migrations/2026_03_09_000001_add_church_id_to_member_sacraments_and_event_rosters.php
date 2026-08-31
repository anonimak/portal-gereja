<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * T1 — Isolasi church_id untuk member_sacraments & event_rosters.
 *
 * Kedua tabel sebelumnya TIDAK memiliki kolom church_id sehingga data sakramen
 * dan roster dapat bocor antar gereja (Critical K1).
 *
 * Strategi:
 *  - Tambah kolom church_id (nullable dulu) + FK + index.
 *  - Backfill aman dari parent (member → church_id, event → church_id).
 *  - Baris orphan / parent NULL dibiarkan NULL = unknown (tidak tampil di gereja mana pun).
 *  - Tidak ada data dihapus atau dipindah antar gereja.
 *
 * Catatan portabilitas: backfill dilakukan baris-per-baris di PHP agar berjalan
 * di MySQL DAN SQLite (sintaks UPDATE...JOIN gaya MySQL tidak didukung SQLite,
 * yang dipakai untuk unit/feature test dengan database :memory:).
 */
return new class extends Migration {
    public function up(): void
    {
        // ---------- member_sacraments ----------
        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->foreignId('church_id')->nullable()->after('member_id')->constrained('churches')->nullOnDelete();
        });

        // Backfill dari members (portabel lintas driver)
        $sacraments = DB::table('member_sacraments')->whereNull('church_id')->get(['id', 'member_id']);
        foreach ($sacraments as $sacrament) {
            $churchId = $sacrament->member_id
                ? DB::table('members')->where('id', $sacrament->member_id)->value('church_id')
                : null;

            if ($churchId !== null) {
                DB::table('member_sacraments')->where('id', $sacrament->id)->update(['church_id' => $churchId]);
            }
        }

        // ---------- event_rosters ----------
        Schema::table('event_rosters', function (Blueprint $table): void {
            $table->foreignId('church_id')->nullable()->after('event_id')->constrained('churches')->nullOnDelete();
        });

        // Backfill dari events (portabel lintas driver)
        $rosters = DB::table('event_rosters')->whereNull('church_id')->get(['id', 'event_id']);
        foreach ($rosters as $roster) {
            $churchId = $roster->event_id
                ? DB::table('events')->where('id', $roster->event_id)->value('church_id')
                : null;

            if ($churchId !== null) {
                DB::table('event_rosters')->where('id', $roster->id)->update(['church_id' => $churchId]);
            }
        }

        // Index untuk performa scope query
        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->index('church_id');
        });
        Schema::table('event_rosters', function (Blueprint $table): void {
            $table->index('church_id');
        });
    }

    public function down(): void
    {
        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->dropIndex(['church_id']);
            $table->dropForeign(['church_id']);
            $table->dropColumn('church_id');
        });
        Schema::table('event_rosters', function (Blueprint $table): void {
            $table->dropIndex(['church_id']);
            $table->dropForeign(['church_id']);
            $table->dropColumn('church_id');
        });
    }
};
