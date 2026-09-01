<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 3B T6 — Baptis Anak: member_sacraments butuh issued_at + document_path
 * untuk penerbitan Dokumen Baptis Anak (spec Fase 3B §2.9).
 *
 * Hanya menambah kolom nullable — tidak destruktif, aman untuk SQLite & MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->date('issued_at')->nullable()->after('certificate_number');
            $table->string('document_path')->nullable()->after('issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('member_sacraments', function (Blueprint $table): void {
            $table->dropColumn(['issued_at', 'document_path']);
        });
    }
};
