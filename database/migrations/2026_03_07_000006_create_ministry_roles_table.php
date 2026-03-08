<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ministry_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete()->index();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ministry_roles');
    }
};
