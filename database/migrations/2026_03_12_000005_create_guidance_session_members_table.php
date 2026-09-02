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
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->index('church_id', 'guidance_session_members_church_id_index');
            $table->foreignId('session_id')->constrained('guidance_sessions')->cascadeOnDelete();
            $table->index('session_id', 'guidance_session_members_session_id_index');
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->index('member_id', 'guidance_session_members_member_id_index');
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
