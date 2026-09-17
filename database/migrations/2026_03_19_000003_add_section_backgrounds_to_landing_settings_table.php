<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('landing_settings', function (Blueprint $table) {
            $table->string('warta_bg_path')->nullable()->after('hero_banner_path');
            $table->string('branches_bg_path')->nullable()->after('warta_bg_path');
            $table->string('worship_bg_path')->nullable()->after('branches_bg_path');
            $table->string('profile_bg_path')->nullable()->after('worship_bg_path');
            $table->string('portal_bg_path')->nullable()->after('profile_bg_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_settings', function (Blueprint $table) {
            $table->dropColumn([
                'warta_bg_path',
                'branches_bg_path',
                'worship_bg_path',
                'profile_bg_path',
                'portal_bg_path',
            ]);
        });
    }
};
