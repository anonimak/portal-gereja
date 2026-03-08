<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_rosters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete()->index();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete()->index();
            $table->foreignId('role_id')->constrained('ministry_roles')->cascadeOnDelete()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_rosters');
    }
};
