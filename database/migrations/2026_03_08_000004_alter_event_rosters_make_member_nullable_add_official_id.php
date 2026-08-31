<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('event_rosters', function (Blueprint $table): void {
            $table->foreignId('member_id')->nullable()->change();
            $table->foreignId('official_id')->nullable()->after('role_id')->constrained('officials')->cascadeOnDelete();
            $table->index('official_id');
        });
    }

    public function down(): void
    {
        Schema::table('event_rosters', function (Blueprint $table): void {
            $table->dropForeignIdFor(\App\Models\Official::class);
            $table->dropColumn('official_id');
            $table->foreignId('member_id')->required()->change();
        });
    }
};
