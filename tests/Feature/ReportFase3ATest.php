<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Clusters\Reporting\Pages\LaporanJemaatPage;
use App\Filament\Clusters\Reporting\Pages\LaporanKehadiranPage;
use App\Filament\Clusters\Reporting\Pages\LaporanKeuanganPage;
use App\Filament\Clusters\Reporting\Pages\LaporanPelayanPage;
use App\Filament\Clusters\Reporting\Pages\LaporanRapatPage;
use App\Filament\Clusters\Reporting\Pages\LaporanSakramenPage;
use App\Filament\Clusters\Reporting\Pages\WartaJemaat;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventCategory;
use App\Models\Family;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\MeetingMinutes;
use App\Models\Member;
use App\Models\Transaction;
use App\Models\User;
use App\Support\ChurchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportFase3ATest extends TestCase
{
    use RefreshDatabase;

    private Church $churchA;

    private Church $churchB;

    private User $superAdmin;

    private User $churchAdmin;

    private User $financeAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->churchA = Church::factory()->create(['name' => 'Gereja A']);
        $this->churchB = Church::factory()->create(['name' => 'Gereja B']);

        // UserObserver di master hanya whitelist 3 role (Task 3 RBAC 6-role belum
        // di-merge) — pakai withoutEvents() untuk membuat user role baru di test.
        $this->superAdmin = $this->makeUser('super_admin');
        $this->churchAdmin = $this->makeUser('church_admin');
        $this->financeAdmin = $this->makeUser('finance_admin');
    }

    private function makeUser(string $role): User
    {
        return $this->makeUserForChurch($role, $this->churchA);
    }

    private function makeUserForChurch(string $role, Church $church): User
    {
        return User::withoutEvents(fn () => User::factory()->create([
            'church_id' => $church->id,
            'role' => $role,
        ]));
    }

    /**
     * Matriks akses §1.1 dihitung dari canAccess() (gate server-side yang dipakai
     * Filament untuk menolak URL halaman — bukan sekadar hidden).
     */
    public static function accessMatrixProvider(): array
    {
        return [
            'warta - church_admin' => [WartaJemaat::class, 'church_admin', true],
            'warta - finance_admin' => [WartaJemaat::class, 'finance_admin', false],
            'warta - warta_editor' => [WartaJemaat::class, 'warta_editor', true],
            'warta - jemaat_admin' => [WartaJemaat::class, 'jemaat_admin', false],
            'jemaat - church_admin' => [LaporanJemaatPage::class, 'church_admin', true],
            'jemaat - jemaat_admin' => [LaporanJemaatPage::class, 'jemaat_admin', true],
            'jemaat - warta_editor' => [LaporanJemaatPage::class, 'warta_editor', false],
            'keuangan - finance_admin' => [LaporanKeuanganPage::class, 'finance_admin', true],
            'keuangan - warta_editor' => [LaporanKeuanganPage::class, 'warta_editor', false],
            'kehadiran - report_viewer' => [LaporanKehadiranPage::class, 'report_viewer', true],
            'kehadiran - jemaat_admin' => [LaporanKehadiranPage::class, 'jemaat_admin', false],
            'sakramen - jemaat_admin' => [LaporanSakramenPage::class, 'jemaat_admin', true],
            'pelayan - warta_editor' => [LaporanPelayanPage::class, 'warta_editor', true],
            'rapat - finance_admin' => [LaporanRapatPage::class, 'finance_admin', true],
            'rapat - warta_editor' => [LaporanRapatPage::class, 'warta_editor', false],
        ];
    }

    #[DataProvider('accessMatrixProvider')]
    public function test_matriks_akses_server_side(string $pageClass, string $role, bool $allowed): void
    {
        $user = $this->makeUser($role);
        $this->actingAs($user);

        $this->assertSame($allowed, $pageClass::canAccess(), "{$pageClass} untuk {$role}");

        if (! $allowed) {
            $this->assertSame(false, $pageClass::canAccess());
        }
    }

    public function test_warta_data_terisolasi_per_gereja_dan_punya_blok_export(): void
    {
        // Member gereja A dibuat saat actingAs admin A.
        $this->actingAs($this->churchAdmin);
        $familyA = Family::factory()->create(['church_id' => $this->churchA->id]);
        Member::factory()->create([
            'family_id' => $familyA,
            'church_id' => $this->churchA->id,
            'status' => 'aktif',
            'birth_date' => now()->startOfMonth()->addDays(2)->subYears(25)->toDateString(),
        ]);

        // Member gereja B harus dibuat saat actingAs admin B (trait memaksa church_id
        // ke gereja aktor), dengan family gereja B sendiri (FK satu gereja).
        $churchBAdmin = $this->makeUserForChurch('church_admin', $this->churchB);
        $this->actingAs($churchBAdmin);
        $familyB = Family::factory()->create(['church_id' => $this->churchB->id]);
        Member::factory()->create([
            'family_id' => $familyB,
            'church_id' => $this->churchB->id,
            'status' => 'aktif',
            'birth_date' => now()->startOfMonth()->addDays(2)->subYears(30)->toDateString(),
        ]);

        $this->actingAs($this->churchAdmin);
        $page = new WartaJemaat;
        $page->startDate = Carbon::now()->startOfMonth();
        $page->endDate = Carbon::now()->endOfMonth();

        $data = $page->getReportData();
        $this->assertSame('Gereja A', $data['churchName']);
        $this->assertSame(1, $data['birthdays']->count());

        $blocks = $this->invokeExportBlocks($page);
        $this->assertGreaterThanOrEqual(4, count($blocks));
    }

    public function test_laporan_keuangan_sum_berdasarkan_transaksi_bukan_hardcode(): void
    {
        $this->actingAs($this->financeAdmin);

        $fund = Fund::factory()->create(['church_id' => $this->churchA->id]);
        $debit = FinancialCategory::factory()->create(['church_id' => $this->churchA->id, 'type' => 'debit']);
        $credit = FinancialCategory::factory()->create(['church_id' => $this->churchA->id, 'type' => 'credit']);

        Transaction::factory()->create([
            'church_id' => $this->churchA->id,
            'fund_id' => $fund->id,
            'category_id' => $debit->id,
            'type' => 'debit',
            'amount' => 500_000,
            'transaction_date' => now()->startOfMonth(),
        ]);
        Transaction::factory()->create([
            'church_id' => $this->churchA->id,
            'fund_id' => $fund->id,
            'category_id' => $credit->id,
            'type' => 'credit',
            'amount' => 200_000,
            'transaction_date' => now()->startOfMonth(),
        ]);

        $page = new LaporanKeuanganPage;
        $page->month = now()->format('Y-m');
        $data = $page->getReportData();

        $this->assertTrue($data['funds']->contains(fn ($f) => $f->id === $fund->id));
        $target = $data['funds']->firstWhere('id', $fund->id);
        $this->assertNotNull($target);
        $transactions = $target->transactions;
        $this->assertSame(500_000, (int) $transactions->where('type', 'debit')->sum('amount'));
        $this->assertSame(200_000, (int) $transactions->where('type', 'credit')->sum('amount'));
    }

    public function test_laporan_kehadiran_mengambil_dari_event_attendances(): void
    {
        $this->actingAs($this->churchAdmin);

        $category = EventCategory::factory()->create(['church_id' => $this->churchA->id]);
        $event = Event::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $category->id,
            'start_datetime' => now()->startOfMonth()->addDay()->setTime(9, 0),
        ]);
        $family = Family::factory()->create(['church_id' => $this->churchA->id]);
        $member = Member::factory()->create(['family_id' => $family, 'church_id' => $this->churchA->id]);

        EventAttendance::factory()->create([
            'church_id' => $this->churchA->id,
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'hadir',
        ]);

        $page = new LaporanKehadiranPage;
        $page->month = now()->format('Y-m');
        $data = $page->getReportData();

        $this->assertCount(1, $data['events']);
        $this->assertSame(1, $data['events']->first()->attendances->where('status', 'hadir')->count());
    }

    public function test_laporan_rapat_notulen_tersimpan_dan_teraudit(): void
    {
        $this->actingAs($this->churchAdmin);

        $category = EventCategory::factory()->create(['church_id' => $this->churchA->id]);
        $event = Event::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $category->id,
            'start_datetime' => now()->startOfMonth(),
        ]);

        $minutes = MeetingMinutes::create([
            'church_id' => $this->churchA->id,
            'event_id' => $event->id,
            'title' => 'Rapat Bulanan',
            'meeting_date' => now()->toDateString(),
            'agenda' => ['Evaluasi'],
            'participants' => ['Anggota'],
            'notes' => 'Berjalan lancar.',
            'decisions' => [],
            'attachments' => [],
        ]);

        $this->assertDatabaseHas('meeting_minutes', ['title' => 'Rapat Bulanan', 'church_id' => $this->churchA->id]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => MeetingMinutes::class,
            'auditable_id' => $minutes->id,
            'action' => 'created',
        ]);
    }

    public function test_super_admin_pemilih_gereja_mengubah_scope_data(): void
    {
        $this->actingAs($this->superAdmin);

        $familyA = Family::factory()->create(['church_id' => $this->churchA->id]);
        $familyB = Family::factory()->create(['church_id' => $this->churchB->id]);
        Member::factory()->create(['family_id' => $familyA, 'church_id' => $this->churchA->id]);
        Member::factory()->create(['family_id' => $familyB, 'church_id' => $this->churchB->id]);

        // All → kedua gereja
        ChurchContext::setActiveChurch(null, $this->superAdmin);
        $this->assertSame(2, Member::query()->count());

        // Pilih gereja A → hanya gereja A
        ChurchContext::setActiveChurch($this->churchA->id, $this->superAdmin);
        $this->assertSame(1, Member::query()->count());
        $this->assertSame($this->churchA->id, Member::query()->first()->church_id);
    }

    public function test_export_pdf_dan_excel_mengembalikan_response(): void
    {
        $this->actingAs($this->churchAdmin);

        $page = new WartaJemaat;
        $page->startDate = Carbon::now()->startOfMonth();
        $page->endDate = Carbon::now()->endOfMonth();

        $pdf = $page->downloadPdf();
        $this->assertSame(200, $pdf->getStatusCode());
        $this->assertStringContainsString('.pdf', $pdf->headers->get('Content-Disposition') ?? '');

        $excel = $page->downloadExcel();
        $this->assertSame(200, $excel->getStatusCode());
    }

    public function test_data_soft_deleted_otomatis_dikecualikan_laporan(): void
    {
        $this->actingAs($this->churchAdmin);

        $family = Family::factory()->create(['church_id' => $this->churchA->id]);
        $member = Member::factory()->create([
            'family_id' => $family,
            'church_id' => $this->churchA->id,
            'status' => 'aktif',
        ]);
        $member->delete();

        $page = new LaporanJemaatPage;
        $data = $page->getReportData();
        $this->assertSame(0, $data['members']->count());
    }

    public function test_endpoint_export_warta_pdf_dan_excel(): void
    {
        $this->actingAs($this->churchAdmin);

        $this->post(route('warta-jemaat.export-pdf'), [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ])->assertSuccessful();

        $this->post(route('warta-jemaat.export-excel'), [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ])->assertSuccessful();
    }

    public function test_export_warta_ditolak_untuk_role_luar_matriks(): void
    {
        $this->actingAs($this->financeAdmin);

        $this->post(route('warta-jemaat.export-pdf'), [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ])->assertForbidden();
    }

    private function invokeExportBlocks(object $page): array
    {
        $method = new \ReflectionMethod($page, 'exportBlocks');

        return $method->invoke($page);
    }
}
