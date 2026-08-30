<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_dialihkan_ke_login(): void
    {
        $this->post('/admin/laporan-rapat/export-excel', [
            'period_type' => 'monthly',
            'month' => now()->month,
            'year' => now()->year,
        ])->assertStatus(302);
    }

    public function test_church_admin_dapat_mengunduh_laporan_gereja_sendiri(): void
    {
        $churchA = Church::factory()->create(['name' => 'Gereja Export A']);
        $churchB = Church::factory()->create(['name' => 'Gereja Export B']);

        $adminA = User::factory()->create([
            'church_id' => $churchA->id,
            'role' => 'church_admin',
        ]);

        // Data gereja A
        $categoryA = FinancialCategory::factory()->create([
            'church_id' => $churchA->id,
            'type' => 'debit',
            'name' => 'Kolekte A',
        ]);
        $fundA = Fund::factory()->create(['church_id' => $churchA->id]);
        Transaction::factory()->create([
            'church_id' => $churchA->id,
            'fund_id' => $fundA,
            'category_id' => $categoryA,
            'type' => 'debit',
            'amount' => 100_000,
            'transaction_date' => now(),
        ]);

        // Data gereja B (rahasia, tidak boleh bocor ke export A)
        $categoryB = FinancialCategory::factory()->create([
            'church_id' => $churchB->id,
            'type' => 'debit',
            'name' => 'Kolekte Rahasia B',
        ]);
        $fundB = Fund::factory()->create(['church_id' => $churchB->id]);
        Transaction::factory()->create([
            'church_id' => $churchB->id,
            'fund_id' => $fundB,
            'category_id' => $categoryB,
            'type' => 'debit',
            'amount' => 999_999,
            'transaction_date' => now(),
        ]);

        $this->actingAs($adminA);

        $response = $this->post('/admin/laporan-rapat/export-excel', [
            'period_type' => 'monthly',
            'month' => now()->month,
            'year' => now()->year,
        ]);

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Gereja Export A', $content);
        $this->assertStringContainsString('Kolekte A', $content);
        $this->assertStringContainsString('100.000', $content);
        $this->assertStringNotContainsString('Gereja Export B', $content);
        $this->assertStringNotContainsString('Kolekte Rahasia B', $content);
        $this->assertStringNotContainsString('999.999', $content);
    }

    public function test_super_admin_dapat_mengunduh_laporan(): void
    {
        $church = Church::factory()->create(['name' => 'Gereja Super']);
        $superAdmin = User::factory()->create([
            'church_id' => $church->id,
            'role' => 'super_admin',
        ]);

        $this->actingAs($superAdmin);

        $this->post('/admin/laporan-rapat/export-excel', [
            'period_type' => 'monthly',
            'month' => now()->month,
            'year' => now()->year,
        ])->assertOk();
    }
}
