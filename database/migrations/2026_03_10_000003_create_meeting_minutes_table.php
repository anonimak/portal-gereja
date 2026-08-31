<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_minutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->date('meeting_date');
            $table->json('agenda')->nullable();
            $table->json('participants')->nullable();
            $table->longText('notes')->nullable();
            $table->json('decisions')->nullable();
            $table->json('attachments')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['church_id', 'meeting_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_minutes');
    }
};
