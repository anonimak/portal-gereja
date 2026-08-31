<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 Task 2: kehadiran ibadah per anggota (check-in).
 *
 * Tabel baru `event_attendances`:
 * - church_id NOT NULL + FK churches (cascadeOnDelete) — tenant (AC-T2-01/06).
 * - event_id FK events (cascadeOnDelete) + member_id FK members (cascadeOnDelete).
 * - status hadir/tidak_hadir (default hadir), checked_in_at, checked_in_by (audit-only),
 *   notes, deleted_at (SoftDeletes), timestamps.
 * - UNIQUE(event_id, member_id) — anti check-in ganda (AC-T2-01/09).
 *
 * Portabilitas: SQLite & MySQL didukung (foreignId + constrained + unique biasa;
 * string untuk status, bukan enum DB — konsisten dengan financial_categories.type).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_attendances', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('church_id')
                ->constrained('churches')
                ->cascadeOnDelete();
            $table->index('church_id');

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();
            $table->index('event_id');

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();
            $table->index('member_id');

            $table->string('status', 20)->default('hadir');
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->index('checked_in_by');
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['event_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendances');
    }
};
