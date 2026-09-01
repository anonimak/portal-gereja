<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guidance_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->index('church_id', 'guidance_templates_church_id_index');
            $table->string('type'); // pra_sidi | pra_nikah
            $table->string('name');
            $table->unsignedInteger('session_count')->default(12);
            $table->boolean('is_default')->default(false);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guidance_templates');
    }
};
