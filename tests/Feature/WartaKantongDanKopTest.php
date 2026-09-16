<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Clusters\Reporting\Pages\WartaJemaat;
use App\Models\Church;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WartaPublication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WartaKantongDanKopTest extends TestCase
{
    use RefreshDatabase;

    private Church $churchA;

    private Church $churchB;

    private User $superAdmin;

    private User $churchAdminA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->churchA = Church::factory()->create([
            'code' => 'GKSBS-A',
            'name' => 'Jemaat A',
            'synod' => 'Sinode GKSBS',
            'address' => 'Jl. Gereja No. 1',
            'phone' => '081234567890',
            'email' => 'admin@jemaat-a.org',
            'website' => 'https://jemaat-a.org',
        ]);

        $this->churchB = Church::factory()->create([
            'code' => 'GKSBS-B',
            'name' => 'Jemaat B',
        ]);

        $this->superAdmin = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'super_admin',
        ]);

        $this->churchAdminA = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'church_admin',
        ]);
    }

    public function test_church_identity_columns_and_accessors(): void
    {
        $this->assertEquals('Sinode GKSBS', $this->churchA->synod);
        $this->assertEquals('admin@jemaat-a.org', $this->churchA->email);
        $this->assertEquals('https://jemaat-a.org', $this->churchA->website);
        $this->assertNull($this->churchA->logo_url);
        $this->assertNull($this->churchA->logo_base64);

        // Simulasi logo tersimpan di public disk
        Storage::fake('public');
        Storage::disk('public')->put('church-logos/logo.png', 'fake-image-content');
        $this->churchA->update(['logo_path' => 'church-logos/logo.png']);

        $this->assertNotNull($this->churchA->fresh()->logo_url);
        $this->assertStringContainsString('Telp: 081234567890', $this->churchA->formatted_contact);
        $this->assertStringContainsString('Email: admin@jemaat-a.org', $this->churchA->formatted_contact);
    }

    public function test_warta_jemaat_calculates_cash_flow_per_fund(): void
    {
        $kasUmum = Fund::create(['church_id' => $this->churchA->id, 'name' => 'Kas Umum']);
        $pembangunan = Fund::create(['church_id' => $this->churchA->id, 'name' => 'Pembangunan']);

        $kolekte = FinancialCategory::create(['church_id' => $this->churchA->id, 'name' => 'Kolekte', 'type' => 'debit']);
        $listrik = FinancialCategory::create(['church_id' => $this->churchA->id, 'name' => 'Listrik', 'type' => 'credit']);
        $semen = FinancialCategory::create(['church_id' => $this->churchA->id, 'name' => 'Semen', 'type' => 'credit']);

        $startDate = Carbon::create(2026, 9, 14);
        $endDate = Carbon::create(2026, 9, 20);

        // Transaksi Saldo Awal (sebelum 14 Sep)
        Transaction::create([
            'church_id' => $this->churchA->id,
            'fund_id' => $kasUmum->id,
            'category_id' => $kolekte->id,
            'amount' => 500000,
            'type' => 'debit',
            'description' => 'Saldo awal persembahan',
            'transaction_date' => '2026-09-10',
        ]);

        // Transaksi dalam periode warta
        Transaction::create([
            'church_id' => $this->churchA->id,
            'fund_id' => $kasUmum->id,
            'category_id' => $kolekte->id,
            'amount' => 200000,
            'type' => 'debit',
            'description' => 'Kolekte Minggu',
            'transaction_date' => '2026-09-15',
        ]);
        Transaction::create([
            'church_id' => $this->churchA->id,
            'fund_id' => $kasUmum->id,
            'category_id' => $listrik->id,
            'amount' => 100000,
            'type' => 'credit',
            'description' => 'Tagihan Listrik',
            'transaction_date' => '2026-09-16',
        ]);
        Transaction::create([
            'church_id' => $this->churchA->id,
            'fund_id' => $pembangunan->id,
            'category_id' => $semen->id,
            'amount' => 150000,
            'type' => 'credit',
            'description' => 'Beli Semen',
            'transaction_date' => '2026-09-17',
        ]);

        $page = new WartaJemaat();
        $page->churchSelect = $this->churchA->id;
        $page->startDate = $startDate;
        $page->endDate = $endDate;

        $report = $page->getReportData();

        // 1. Cek backward compatibility
        $this->assertEquals(500000, $report['openingBalance']);
        $this->assertEquals(200000, $report['totalIncome']);
        $this->assertEquals(250000, $report['totalExpenses']);
        $this->assertEquals(450000, $report['closingBalance']);

        // 2. Cek rincian per kantong (fundBreakdowns)
        $this->assertArrayHasKey('fundBreakdowns', $report);
        $this->assertCount(2, $report['fundBreakdowns']);

        $kasUmumReport = collect($report['fundBreakdowns'])->firstWhere('id', $kasUmum->id);
        $this->assertEquals(500000, $kasUmumReport['opening_balance']);
        $this->assertEquals(200000, $kasUmumReport['income']['total']);
        $this->assertEquals(100000, $kasUmumReport['expense']['total']);
        $this->assertEquals(600000, $kasUmumReport['closing_balance']);

        $pembangunanReport = collect($report['fundBreakdowns'])->firstWhere('id', $pembangunan->id);
        $this->assertEquals(0, $pembangunanReport['opening_balance']);
        $this->assertEquals(0, $pembangunanReport['income']['total']);
        $this->assertEquals(150000, $pembangunanReport['expense']['total']);
        $this->assertEquals(-150000, $pembangunanReport['closing_balance']);
    }

    public function test_publish_warta_snapshots_church_and_funds(): void
    {
        Fund::create(['church_id' => $this->churchA->id, 'name' => 'Kas Umum']);

        $response = $this->actingAs($this->churchAdminA)->postJson('/admin/warta/publish', [
            'church_id' => $this->churchA->id,
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-20',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'publication' => ['id', 'title', 'church_id', 'url']]);

        $publication = WartaPublication::where('church_id', $this->churchA->id)->latest()->first();
        $this->assertNotNull($publication);
        $this->assertEquals('Sinode GKSBS', $publication->content['church']['synod'] ?? null);
        $this->assertArrayHasKey('funds', $publication->content['finance'] ?? []);
    }

    public function test_public_warta_page_renders_with_letterhead_and_funds(): void
    {
        $pub = WartaPublication::create([
            'church_id' => $this->churchA->id,
            'title' => 'Warta Minggu Kasih',
            'period_start' => '2026-09-14',
            'period_end' => '2026-09-20',
            'content' => [
                'church_name' => $this->churchA->name,
                'church' => [
                    'name' => $this->churchA->name,
                    'synod' => $this->churchA->synod,
                    'address' => $this->churchA->address,
                    'phone' => $this->churchA->phone,
                    'email' => $this->churchA->email,
                    'logo_url' => null,
                ],
                'period_label' => '14 – 20 September 2026',
                'finance' => [
                    'opening_balance' => 500000,
                    'total_income' => 200000,
                    'total_expenses' => 100000,
                    'closing_balance' => 600000,
                    'funds' => [
                        [
                            'id' => 1,
                            'name' => 'Kas Umum',
                            'opening_balance' => 500000,
                            'income' => [
                                'total' => 200000,
                                'items' => [['category' => 'Kolekte', 'amount' => 200000]],
                            ],
                            'expense' => [
                                'total' => 100000,
                                'items' => [['category' => 'Listrik', 'amount' => 100000]],
                            ],
                            'closing_balance' => 600000,
                        ],
                    ],
                ],
            ],
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $response = $this->get('/warta/' . $this->churchA->code . '/' . $pub->id);
        $response->assertOk();
        $response->assertSee('Sinode GKSBS');
        $response->assertSee('Kas Umum');
        $response->assertSee('Kolekte');
        $response->assertSee('100% Kas Tunai');
    }
}
