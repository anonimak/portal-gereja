<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultFinanceSeeder extends Seeder
{
    /**
     * Seed default funds and financial categories for a church.
     */
    public function run(int $churchId): void
    {
        $now = now()->toDateTimeString();

        // Create default funds
        $defaultFunds = [
            'Kas Operasional',
            'Kas Pembangunan',
            'Kas Diakonia',
            'Kas Misi',
        ];

        foreach ($defaultFunds as $fundName) {
            DB::table('funds')->insert([
                'church_id' => $churchId,
                'name' => $fundName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Create default income categories
        $incomeCategories = [
            'Kolekte Ibadah Raya',
            'Persepuluhan',
            'Persembahan Syukur',
            'Persembahan Pembangunan',
            'Persembahan Diakonia',
            'Persembahan Kategorial',
            'Lain-lain',
        ];

        foreach ($incomeCategories as $categoryName) {
            DB::table('financial_categories')->insert([
                'church_id' => $churchId,
                'name' => $categoryName,
                'type' => 'debit',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Create default expense categories
        $expenseCategories = [
            'Gaji & Honorarium',
            'Operasional Utilitas (Listrik/Air)',
            'Administrasi & ATK',
            'Konsumsi',
            'Bantuan Diakonia',
            'Pemeliharaan Aset',
            'Subsidi Acara',
            'Misi & Penginjilan',
        ];

        foreach ($expenseCategories as $categoryName) {
            DB::table('financial_categories')->insert([
                'church_id' => $churchId,
                'name' => $categoryName,
                'type' => 'credit',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
