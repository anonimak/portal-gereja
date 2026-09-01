<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 3B T5 — Kelahiran & Akta Lahir (SPEC-FASE3B-LIFECYCLE §2.1).
 *
 * Tabel `birth_records`:
 * - church_id NOT NULL + FK churches — tenant (AC-LC-01/11).
 * - member_id FK members + UNIQUE — 1 member = 1 akta lahir (AC-LC-01).
 * - birth_order, birth_place_full, birth_date (salinan utk dokumen, sumber utama
 *   tetap members.birth_date), father_name/mother_name (default dari keluarga,
 *   editable utk dokumen), certificate_number, issued_at, notes.
 * - deleted_at (SoftDeletes) + timestamps.
 *
 * Portabilitas: SQLite & MySQL (foreignId + constrained + unique biasa).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('birth_records', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('church_id')
                ->constrained('churches')
                ->cascadeOnDelete();
            $table->index('church_id');

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();
            $table->index('member_id');

            $table->unsignedTinyInteger('birth_order')->nullable();
            $table->string('birth_place_full', 255)->nullable();
            $table->date('birth_date');
            $table->string('father_name', 255)->nullable();
            $table->string('mother_name', 255)->nullable();
            $table->string('certificate_number', 100)->nullable();
            $table->date('issued_at')->nullable();
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('birth_records');
    }
};
