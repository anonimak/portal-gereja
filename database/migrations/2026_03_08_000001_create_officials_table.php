<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('officials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->index('church_id');
            $table->enum('type', ['majelis_lokal', 'pendeta_internal', 'pelayan_tamu']);
            $table->foreignId('member_id')->nullable()->constrained('members')->cascadeOnDelete();
            $table->index('member_id');
            $table->string('external_name')->nullable();
            $table->string('origin_church')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('officials');
    }
};
