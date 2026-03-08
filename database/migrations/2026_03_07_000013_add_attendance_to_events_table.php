<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->integer('attendance_male')->nullable()->default(0)->after('location');
            $table->integer('attendance_female')->nullable()->default(0)->after('attendance_male');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn(['attendance_male', 'attendance_female']);
        });
    }
};
