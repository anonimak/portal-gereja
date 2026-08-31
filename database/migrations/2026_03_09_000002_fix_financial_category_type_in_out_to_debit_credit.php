<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * T5 — Normalisasi nilai type kategori keuangan: 'in'/'out' → 'debit'/'credit'.
 *
 * Kategori dibuat dengan 'in'/'out' di form, sementara transaksi & seeder memakai
 * 'debit'/'credit' → kategori UI tak pernah cocok dengan filter transaksi.
 * Kolom `financial_categories.type` adalah string(10) (bukan enum DB), jadi UPDATE
 * aman di semua driver. Tidak menghapus data; baris null/unknown tidak disentuh.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('financial_categories')->where('type', 'in')->update(['type' => 'debit']);
        DB::table('financial_categories')->where('type', 'out')->update(['type' => 'credit']);
    }

    public function down(): void
    {
        // Tidak reversible: data baru 'debit'/'credit' tidak boleh dikembalikan ke 'in'/'out'.
    }
};
