<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinanceTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_tidak_ada_kategori_bertipe_in_out_setelah_migrasi(): void
    {
        // Membuat gereja memicu DefaultFinanceSeeder
        Church::factory()->create();

        $this->assertSame(
            0,
            DB::table('financial_categories')->whereIn('type', ['in', 'out'])->count()
        );
    }

    public function test_seeder_menghasilkan_kategori_debit_dan_credit(): void
    {
        $church = Church::factory()->create();

        $this->assertSame(
            7,
            DB::table('financial_categories')->where('church_id', $church->id)->where('type', 'debit')->count()
        );
        $this->assertSame(
            8,
            DB::table('financial_categories')->where('church_id', $church->id)->where('type', 'credit')->count()
        );
    }

    public function test_transaksi_dan_kategori_konsisten_menggunakan_debit_credit(): void
    {
        $church = Church::factory()->create();
        $category = FinancialCategory::factory()->create([
            'church_id' => $church->id,
            'type' => 'debit',
        ]);
        $fund = Fund::factory()->create(['church_id' => $church->id]);

        $tx = Transaction::factory()->create([
            'church_id' => $church->id,
            'fund_id' => $fund,
            'category_id' => $category,
            'type' => 'debit',
            'amount' => 250_000,
            'transaction_date' => now(),
        ]);

        $this->assertSame('debit', $tx->fresh()->type);
        $this->assertSame('debit', $category->fresh()->type);
        $this->assertSame(250_000, $tx->fresh()->amount);
    }

    public function test_tipe_kategori_menentukan_arah_arus_kas(): void
    {
        $church = Church::factory()->create();
        $income = FinancialCategory::factory()->income()->create(['church_id' => $church->id]);
        $expense = FinancialCategory::factory()->expense()->create(['church_id' => $church->id]);
        $fund = Fund::factory()->create(['church_id' => $church->id]);

        Transaction::factory()->create([
            'church_id' => $church->id,
            'fund_id' => $fund,
            'category_id' => $income,
            'type' => 'debit',
            'amount' => 300_000,
            'transaction_date' => now(),
        ]);
        Transaction::factory()->create([
            'church_id' => $church->id,
            'fund_id' => $fund,
            'category_id' => $expense,
            'type' => 'credit',
            'amount' => 80_000,
            'transaction_date' => now(),
        ]);

        $incomeSum = Transaction::where('church_id', $church->id)->where('type', 'debit')->sum('amount');
        $expenseSum = Transaction::where('church_id', $church->id)->where('type', 'credit')->sum('amount');

        $this->assertSame(300_000, (int) $incomeSum);
        $this->assertSame(80_000, (int) $expenseSum);
    }
}
