<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel recurring_schedules (Jadwal Berulang / Recurring Event Schedule).
     *
     * Menyimpan template jadwal berulang untuk ibadah dan acara gereja
     * (harian, mingguan, bulanan) dengan isolasi tenant (church_id).
     */
    public function up(): void
    {
        Schema::create('recurring_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->index('church_id');
            $table->foreignId('category_id')->constrained('event_categories')->cascadeOnDelete();
            $table->index('category_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('frequency'); // daily, weekly, monthly
            $table->unsignedInteger('interval')->default(1);
            $table->json('days_of_week')->nullable(); // e.g. [0, 6] (0 = Sunday, 6 = Saturday)
            $table->date('start_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('end_type')->default('until_date'); // until_date, count, never
            $table->date('until_date')->nullable();
            $table->unsignedInteger('repeat_count')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_generated_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['church_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_schedules');
    }
};
