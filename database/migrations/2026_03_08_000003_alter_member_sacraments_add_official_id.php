<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->dropColumn('minister_name');
            $table->foreignId('official_id')->nullable()->after('type')->constrained('officials')->cascadeOnDelete()->index();
        });
    }

    public function down(): void
    {
        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->dropForeignIdFor(\App\Models\Official::class);
            $table->dropColumn('official_id');
            $table->string('minister_name')->nullable()->after('type');
        });
    }
};
