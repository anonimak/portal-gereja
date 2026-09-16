<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->string('synod')->nullable()->after('name')
                ->comment('Nama Sinode / Klasis / Wilayah / Lembaga Gereja Induk');
            $table->string('email')->nullable()->after('phone')
                ->comment('Email resmi kantor / sekretariat gereja');
            $table->string('logo_path')->nullable()->after('email')
                ->comment('Path file logo di storage disk public (church-logos/...)');
            $table->string('website')->nullable()->after('logo_path')
                ->comment('Website resmi gereja (opsional)');
        });
    }

    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->dropColumn(['synod', 'email', 'logo_path', 'website']);
        });
    }
};
