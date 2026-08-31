<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * C3 Vera: FK referensi historis yang masih cascadeOnDelete harus di-restrict,
 * agar forceDelete (hard delete) Fund/FinancialCategory TIDAK menghapus transaksi
 * permanen. Migrasi BARU — migrasi lama tidak diubah.
 *
 * Portabilitas: SQLite & MySQL didukung via dropForeign + re-add restrictOnDelete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropForeign(['fund_id']);
            $table->dropForeign(['category_id']);
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->foreign('fund_id')->references('id')->on('funds')->restrictOnDelete();
            $table->foreign('category_id')->references('id')->on('financial_categories')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropForeign(['fund_id']);
            $table->dropForeign(['category_id']);
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->foreign('fund_id')->references('id')->on('funds')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('financial_categories')->cascadeOnDelete();
        });
    }
};
