<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan foreign key recurring_schedule_id ke tabel events.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->foreignId('recurring_schedule_id')
                ->nullable()
                ->constrained('recurring_schedules')
                ->nullOnDelete();
            $table->index('recurring_schedule_id');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropForeign(['recurring_schedule_id']);
            $table->dropIndex(['recurring_schedule_id']);
            $table->dropColumn('recurring_schedule_id');
        });
    }
};
