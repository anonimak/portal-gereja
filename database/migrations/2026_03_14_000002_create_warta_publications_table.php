<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Publikasi Warta Jemaat ke portal publik.
     *
     * Setiap baris = snapshot edisi Warta periode tertentu untuk satu gereja,
     * yang sengaja dipublikasikan admin (super_admin / church_admin /
     * warta_editor). Konten disimpan sebagai JSON (bukan query live) supaya
     * halaman publik ringan & tidak bocor data gereja lain.
     */
    public function up(): void
    {
        Schema::create('warta_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->json('content')->nullable();
            $table->string('status')->default('published'); // draft | published
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['church_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warta_publications');
    }
};
