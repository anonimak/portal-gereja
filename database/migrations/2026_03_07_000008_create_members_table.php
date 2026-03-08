<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete()->index();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete()->index();
            $table->string('id_card_number')->nullable();
            $table->string('full_name');
            $table->enum('gender', ['m', 'f'])->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('family_relation', ['kepala_keluarga', 'istri', 'anak', 'lainnya'])->default('lainnya');
            $table->enum('status', ['aktif', 'titipan', 'pindah', 'meninggal'])->default('aktif');
            $table->json('custom_fields')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
