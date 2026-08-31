<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M6 Vera: penamaan kolom audit diseragamkan — kolom ip diganti menjadi
 * ip_address + user_agent.
 * 1. Tambah ip_address & user_agent.
 * 2. Backfill ip_address dari kolom ip lama.
 * 3. Drop kolom ip.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('ip_address', 45)->nullable()->after('ip');
            $table->string('user_agent', 255)->nullable()->after('ip_address');
        });

        DB::table('audit_logs')->whereNotNull('ip')->update(['ip_address' => DB::raw('ip')]);

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropColumn('ip');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('ip', 45)->nullable()->after('ip_address');
        });

        DB::table('audit_logs')->whereNotNull('ip_address')->update(['ip' => DB::raw('ip_address')]);

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropColumn(['ip_address', 'user_agent']);
        });
    }
};
