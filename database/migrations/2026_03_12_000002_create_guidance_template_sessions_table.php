<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guidance_template_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete()->index();
            $table->foreignId('template_id')->constrained('guidance_templates')->cascadeOnDelete()->index();
            $table->unsignedInteger('session_number');
            $table->string('topic');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['template_id', 'session_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guidance_template_sessions');
    }
};
