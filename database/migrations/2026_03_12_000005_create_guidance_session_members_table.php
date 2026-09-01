<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guidance_session_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete()->index();
            $table->foreignId('session_id')->constrained('guidance_sessions')->cascadeOnDelete()->index();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete()->index();
            $table->boolean('attended')->default(false);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['session_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guidance_session_members');
    }
};
