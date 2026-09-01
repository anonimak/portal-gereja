<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guidance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->index('church_id', 'guidance_sessions_church_id_index');
            $table->foreignId('program_id')->constrained('guidance_programs')->cascadeOnDelete();
            $table->index('program_id', 'guidance_sessions_program_id_index');
            $table->string('title')->nullable();
            $table->dateTime('session_at')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('official_id')->nullable()->constrained('officials')->nullOnDelete();
            $table->index('official_id', 'guidance_sessions_official_id_index');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guidance_sessions');
    }
};
