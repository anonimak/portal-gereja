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
 */
return new class extends Migration {
    public function up(): void
    {
        // ---------- member_sacraments ----------
        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->foreignId('church_id')->nullable()->after('member_id')->constrained('churches')->nullOnDelete();
        });

        // Backfill dari members
        DB::statement('UPDATE member_sacraments ms
            LEFT JOIN members m ON m.id = ms.member_id
            SET ms.church_id = m.church_id');

        // ---------- event_rosters ----------
        Schema::table('event_rosters', function (Blueprint $table): void {
            $table->foreignId('church_id')->nullable()->after('event_id')->constrained('churches')->nullOnDelete();
        });

        // Backfill dari events
        DB::statement('UPDATE event_rosters er
            LEFT JOIN events e ON e.id = er.event_id
            SET er.church_id = e.church_id');

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
