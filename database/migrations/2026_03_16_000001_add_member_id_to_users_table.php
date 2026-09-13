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
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('member_id')->nullable()->after('church_id')->constrained('members')->nullOnDelete();
            $table->index('member_id');
            $table->string('api_token', 80)->nullable()->unique()->after('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('member_id');
            $table->dropColumn('api_token');
        });
    }
};
