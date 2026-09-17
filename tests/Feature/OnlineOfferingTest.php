<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\Member;
use App\Models\OnlineOffering;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnlineOfferingTest extends TestCase
{
    use RefreshDatabase;

    private Church $churchA;

    private Church $churchB;

    private User $superAdmin;

    private User $churchAdminA;

    private User $financeAdminA;

    private User $memberUserA;

    private Member $memberA;

    private Fund $kasUmumA;

    private FinancialCategory $persembahanIbadahA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->churchA = Church::factory()->create([
            'code' => 'GKSBS-A',
            'name' => 'Jemaat A',
            'bank_accounts' => [
                ['bank_name' => 'BCA', 'account_number' => '1234567890', 'account_holder' => 'GKSBS Jemaat A'],
            ],
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

        $this->financeAdminA = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'finance_admin',
        ]);

        $this->memberA = Member::factory()->create([
            'church_id' => $this->churchA->id,
            'full_name' => 'Yohanes Prasetyo',
        ]);

        $this->memberUserA = User::factory()->create([
            'church_id' => $this->churchA->id,
            'member_id' => $this->memberA->id,
            'role' => 'member',
            'api_token' => 'token-yohanes-12345',
        ]);

        $this->kasUmumA = Fund::create([
            'church_id' => $this->churchA->id,
            'name' => 'Kas Umum',
        ]);

        $this->persembahanIbadahA = FinancialCategory::create([
            'church_id' => $this->churchA->id,
            'name' => 'Persembahan Ibadah Minggu',
            'type' => 'debit',
        ]);
    }

    public function test_public_offering_page_renders_successfully(): void
    {
        $response = $this->get('/persembahan/' . $this->churchA->code);

        $response->assertOk();
        $response->assertSee('Persembahan & Donasi Jemaat', false);
        $response->assertSee('1234567890');
        $response->assertSee('Kas Umum');
        $response->assertSee('Persembahan Ibadah Minggu');
    }

    public function test_guest_can_submit_online_offering(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('bukti_transfer.jpg');

        $response = $this->post('/persembahan', [
            'church_id' => $this->churchA->id,
            'fund_id' => $this->kasUmumA->id,
            'financial_category_id' => $this->persembahanIbadahA->id,
            'donor_name' => 'Bpk. Markus',
            'donor_phone' => '08123456789',
            'donor_email' => 'markus@example.com',
            'amount' => 100000,
            'payment_method' => 'bank_transfer',
            'bank_name' => 'BCA',
            'prayer_notes' => 'Ucapan syukur atas berkat Tuhan',
            'proof' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('offering_success');

        $offering = OnlineOffering::where('church_id', $this->churchA->id)->latest()->first();
        $this->assertNotNull($offering);
        $this->assertEquals('Bpk. Markus', $offering->donor_name);
        $this->assertEquals(100000, $offering->amount);
        $this->assertEquals('pending', $offering->status);
        $this->assertNotNull($offering->proof_path);
        Storage::disk('public')->assertExists($offering->proof_path);
    }

    public function test_member_can_submit_online_offering_via_portal(): void
    {
        $response = $this->actingAs($this->memberUserA)->post('/portal/persembahan', [
            'fund_id' => $this->kasUmumA->id,
            'financial_category_id' => $this->persembahanIbadahA->id,
            'amount' => 250000,
            'payment_method' => 'qris',
            'prayer_notes' => 'Perpuluhan bulan September',
        ]);

        $response->assertRedirect(route('portal.offerings'));
        $response->assertSessionHas('success');

        $offering = OnlineOffering::where('member_id', $this->memberA->id)->latest()->first();
        $this->assertNotNull($offering);
        $this->assertEquals($this->memberA->full_name, $offering->donor_name);
        $this->assertEquals(250000, $offering->amount);
        $this->assertEquals('pending', $offering->status);
    }

    public function test_member_can_submit_online_offering_via_api(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->memberUserA->api_token,
        ])->postJson('/api/portal/offerings', [
            'fund_id' => $this->kasUmumA->id,
            'financial_category_id' => $this->persembahanIbadahA->id,
            'amount' => 500000,
            'payment_method' => 'va',
            'prayer_notes' => 'Aksi peduli kasih',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'offering' => ['id', 'reference_code', 'amount', 'status'],
        ]);

        // Cek riwayat API
        $history = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->memberUserA->api_token,
        ])->getJson('/api/portal/offerings');

        $history->assertOk();
        $history->assertJsonCount(1, 'offerings');
    }

    public function test_finance_admin_can_confirm_offering_and_it_creates_cash_transaction(): void
    {
        $offering = OnlineOffering::create([
            'church_id' => $this->churchA->id,
            'fund_id' => $this->kasUmumA->id,
            'financial_category_id' => $this->persembahanIbadahA->id,
            'donor_name' => 'Ibu Maria',
            'amount' => 300000,
            'payment_method' => 'qris',
            'reference_code' => OnlineOffering::generateReferenceCode(),
            'status' => 'pending',
        ]);

        $this->assertNull($offering->transaction_id);

        // Eksekusi konfirmasi oleh finance_admin
        $transaction = $offering->confirm($this->financeAdminA);

        $this->assertNotNull($transaction);
        $this->assertEquals('debit', $transaction->type);
        $this->assertEquals(300000, $transaction->amount);
        $this->assertEquals($this->churchA->id, $transaction->church_id);
        $this->assertEquals($this->kasUmumA->id, $transaction->fund_id);

        $offering->refresh();
        $this->assertEquals('confirmed', $offering->status);
        $this->assertEquals($this->financeAdminA->id, $offering->confirmed_by);
        $this->assertEquals($transaction->id, $offering->transaction_id);
    }

    public function test_finance_admin_can_reject_offering_with_reason(): void
    {
        $offering = OnlineOffering::create([
            'church_id' => $this->churchA->id,
            'fund_id' => $this->kasUmumA->id,
            'financial_category_id' => $this->persembahanIbadahA->id,
            'donor_name' => 'Sdr. Tomas',
            'amount' => 50000,
            'payment_method' => 'bank_transfer',
            'reference_code' => OnlineOffering::generateReferenceCode(),
            'status' => 'pending',
        ]);

        $offering->reject($this->financeAdminA, 'Bukti transfer buram dan tidak terbaca');

        $offering->refresh();
        $this->assertEquals('rejected', $offering->status);
        $this->assertEquals('Bukti transfer buram dan tidak terbaca', $offering->rejection_reason);
        $this->assertNull($offering->transaction_id);
    }

    public function test_tenant_isolation_on_online_offerings(): void
    {
        $offeringA = OnlineOffering::create([
            'church_id' => $this->churchA->id,
            'fund_id' => $this->kasUmumA->id,
            'financial_category_id' => $this->persembahanIbadahA->id,
            'donor_name' => 'Jemaat A',
            'amount' => 100000,
            'payment_method' => 'qris',
            'reference_code' => OnlineOffering::generateReferenceCode(),
            'status' => 'pending',
        ]);

        // Buat user church_admin B sebagai superAdmin agar diizinkan oleh UserObserver
        $this->actingAs($this->superAdmin);
        $churchAdminB = User::factory()->create([
            'church_id' => $this->churchB->id,
            'role' => 'church_admin',
        ]);

        // Menggunakan scope church_admin A
        $this->actingAs($this->churchAdminA);
        $this->assertEquals(1, OnlineOffering::count());

        // Menggunakan church_admin B
        $this->actingAs($churchAdminB);
        $this->assertEquals(0, OnlineOffering::count());
    }
}
