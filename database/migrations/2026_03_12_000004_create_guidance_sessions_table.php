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
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete()->index();
            $table->foreignId('program_id')->constrained('guidance_programs')->cascadeOnDelete()->index();
            $table->string('title')->nullable();
            $table->dateTime('session_at')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('official_id')->nullable()->constrained('officials')->nullOnDelete()->index();
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
