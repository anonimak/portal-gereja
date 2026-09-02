<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 3B T11 — Kematian (Surat Keterangan Kematian).
 *
 * Spec §2.8: satu anggota maksimal satu catatan kematian (member_id UNIQUE).
 * Side effect: saat record dibuat -> member.status = 'meninggal' (AC-LC-05);
 * restore/hapus tidak mengembalikan status (asumsi A8).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_deaths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->index('church_id', 'member_deaths_church_id_index');
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->unique('member_id', 'member_deaths_member_id_unique');
            $table->date('death_date');
            $table->date('burial_date')->nullable();
            $table->string('burial_location')->nullable();
            $table->foreignId('official_id')->nullable()->constrained('officials')->restrictOnDelete();
            $table->index('official_id', 'member_deaths_official_id_index');
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->index('event_id', 'member_deaths_event_id_index');
            $table->string('certificate_number')->nullable();
            $table->date('issued_at')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_deaths');
    }
};
