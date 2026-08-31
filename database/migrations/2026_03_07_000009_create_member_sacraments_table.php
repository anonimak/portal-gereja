<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('member_sacraments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->index('member_id');
            $table->enum('type', ['penyerahan', 'baptis_anak', 'sidi', 'baptis_dewasa', 'nikah']);
            $table->date('sacrament_date');
            $table->string('minister_name')->nullable();
            $table->string('certificate_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_sacraments');
    }
};
