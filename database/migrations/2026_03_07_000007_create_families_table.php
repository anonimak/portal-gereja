<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete()->index();
            $table->string('family_number');
            $table->string('name');
            $table->text('address');
            $table->timestamps();

            $table->unique(['church_id', 'family_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};
