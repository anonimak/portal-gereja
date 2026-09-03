<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 3B T9 — Pernikahan (Akta Nikah).
 *
 * Tabel marriages: pencatatan pernikahan dua anggota (suami & istri) + data
 * dokumen Akta Nikah. Saat dibuat, sistem otomatis membuat 2 baris
 * member_sacraments type 'nikah' (AC-LC-04). Portabel SQLite & MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marriages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->index('church_id', 'marriages_church_id_index');
            $table->foreignId('husband_member_id')->constrained('members')->restrictOnDelete();
            $table->index('husband_member_id', 'marriages_husband_member_id_index');
            $table->foreignId('wife_member_id')->constrained('members')->restrictOnDelete();
            $table->index('wife_member_id', 'marriages_wife_member_id_index');
            $table->date('marriage_date');
            $table->foreignId('official_id')->nullable()->constrained('officials')->nullOnDelete();
            $table->index('official_id', 'marriages_official_id_index');
            $table->string('location')->nullable();
            $table->json('witness_names')->nullable();
            $table->foreignId('program_id')->nullable()->constrained('guidance_programs')->nullOnDelete();
            $table->index('program_id', 'marriages_program_id_index');
            $table->string('certificate_number')->nullable();
            $table->date('issued_at')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marriages');
    }
};
