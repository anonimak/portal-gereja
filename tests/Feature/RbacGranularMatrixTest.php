<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Clusters\Reporting\Pages\WartaJemaat;
use App\Filament\Pages\LaporanRapatPage;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventCategory;
use App\Models\Family;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\Member;
use App\Models\MemberSacrament;
use App\Models\MinistryRole;
use App\Models\Transaction;
use App\Models\User;
use App\Support\RoleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Fase 2 Task 3 — RBAC granular: matriks akses role × modul (AC-T3-03..07).
 */
class RbacGranularMatrixTest extends TestCase
{
    use RefreshDatabase;

    private Church $church;

    private User $superAdmin;

    private User $churchAdmin;

    private User $financeAdmin;

    private User $jemaatAdmin;

    private User $wartaEditor;

    private User $reportViewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->church = Church::factory()->create();

        $this->superAdmin = User::factory()->create(['church_id' => $this->church->id, 'role' => 'super_admin']);
        $this->churchAdmin = User::factory()->create(['church_id' => $this->church->id, 'role' => 'church_admin']);
        $this->financeAdmin = User::factory()->create(['church_id' => $this->church->id, 'role' => 'finance_admin']);
        $this->jemaatAdmin = User::factory()->create(['church_id' => $this->church->id, 'role' => 'jemaat_admin']);
        $this->wartaEditor = User::factory()->create(['church_id' => $this->church->id, 'role' => 'warta_editor']);
        $this->reportViewer = User::factory()->create(['church_id' => $this->church->id, 'role' => 'report_viewer']);
    }

    // ---- AC-T3-03: finance_admin dibatasi ke modul keuangan + laporan rapat ----

    public function test_finance_admin_hanya_modul_keuangan_dan_laporan_rapat(): void
    {
        $this->assertTrue(Gate::forUser($this->financeAdmin)->denies('viewAny', Member::class));
        $this->assertTrue(Gate::forUser($this->financeAdmin)->denies('viewAny', Event::class));
        $this->assertTrue(Gate::forUser($this->financeAdmin)->denies('viewAny', EventCategory::class));
        $this->assertTrue(Gate::forUser($this->financeAdmin)->denies('viewAny', MinistryRole::class));
        $this->assertTrue(Gate::forUser($this->financeAdmin)->denies('viewAny', MemberSacrament::class));
        $this->assertTrue(Gate::forUser($this->financeAdmin)->denies('viewAny', EventAttendance::class));

        $this->assertTrue(Gate::forUser($this->financeAdmin)->allows('viewAny', Transaction::class));
        $this->assertTrue(Gate::forUser($this->financeAdmin)->allows('viewAny', Fund::class));
        $this->assertTrue(Gate::forUser($this->financeAdmin)->allows('viewAny', FinancialCategory::class));

        $this->actingAs($this->financeAdmin);
        $this->assertFalse(WartaJemaat::canAccess());
        $this->assertTrue(LaporanRapatPage::canAccess());
    }

    // ---- AC-T3-04: jemaat_admin hanya modul jemaat, boleh tulis member ----

    public function test_jemaat_admin_hanya_modul_jemaat_dan_boleh_tulis_member(): void
    {
        $this->assertTrue(Gate::forUser($this->jemaatAdmin)->allows('viewAny', Member::class));
        $this->assertTrue(Gate::forUser($this->jemaatAdmin)->allows('viewAny', Family::class));
        $this->assertTrue(Gate::forUser($this->jemaatAdmin)->allows('viewAny', MemberSacrament::class));

        $this->assertTrue(Gate::forUser($this->jemaatAdmin)->denies('viewAny', Event::class));
        $this->assertTrue(Gate::forUser($this->jemaatAdmin)->denies('viewAny', Transaction::class));
        $this->assertTrue(Gate::forUser($this->jemaatAdmin)->denies('viewAny', EventAttendance::class));

        $this->assertTrue(Gate::forUser($this->jemaatAdmin)->allows('create', Member::class));
        $this->assertTrue(Gate::forUser($this->jemaatAdmin)->allows('update', Member::class));
        $this->assertTrue(Gate::forUser($this->jemaatAdmin)->denies('create', Event::class));
    }

    // ---- AC-T3-05: warta_editor view-only + Warta, tanpa laporan rapat ----

    public function test_warta_editor_view_only_dan_waria_tanpa_laporan_rapat(): void
    {
        $this->assertTrue(Gate::forUser($this->wartaEditor)->allows('viewAny', Member::class));
        $this->assertTrue(Gate::forUser($this->wartaEditor)->allows('viewAny', Event::class));
        $this->assertTrue(Gate::forUser($this->wartaEditor)->allows('viewAny', Transaction::class));

        $this->assertTrue(Gate::forUser($this->wartaEditor)->denies('create', Member::class));
        $this->assertTrue(Gate::forUser($this->wartaEditor)->denies('update', Member::class));
        $this->assertTrue(Gate::forUser($this->wartaEditor)->denies('delete', Member::class));
        $this->assertTrue(Gate::forUser($this->wartaEditor)->denies('create', Event::class));

        $this->actingAs($this->wartaEditor);
        $this->assertTrue(WartaJemaat::canAccess());
        $this->assertFalse(LaporanRapatPage::canAccess());
    }

    // ---- AC-T3-06: report_viewer view-only + laporan rapat, tanpa attendance ----

    public function test_report_viewer_view_only_laporan_rapat_tanpa_attendance(): void
    {
        $this->assertTrue(Gate::forUser($this->reportViewer)->allows('viewAny', Transaction::class));
        $this->assertTrue(Gate::forUser($this->reportViewer)->allows('viewAny', Fund::class));
        $this->assertTrue(Gate::forUser($this->reportViewer)->allows('viewAny', Event::class));
        $this->assertTrue(Gate::forUser($this->reportViewer)->allows('viewAny', Member::class));

        $this->assertTrue(Gate::forUser($this->reportViewer)->denies('create', Transaction::class));
        $this->assertTrue(Gate::forUser($this->reportViewer)->denies('update', Fund::class));
        $this->assertTrue(Gate::forUser($this->reportViewer)->denies('viewAny', EventAttendance::class));

        $this->actingAs($this->reportViewer);
        $this->assertTrue(LaporanRapatPage::canAccess());
        $this->assertTrue(WartaJemaat::canAccess());
    }

    // ---- AC-T3-07: regresi 3 role lama tidak berubah ----

    public function test_regresi_church_admin_dan_super_admin(): void
    {
        // church_admin: semua modul tenant + laporan
        $this->assertTrue(Gate::forUser($this->churchAdmin)->allows('viewAny', Member::class));
        $this->assertTrue(Gate::forUser($this->churchAdmin)->allows('viewAny', Event::class));
        $this->assertTrue(Gate::forUser($this->churchAdmin)->allows('viewAny', Transaction::class));
        $this->assertTrue(Gate::forUser($this->churchAdmin)->allows('viewAny', Fund::class));
        $this->assertTrue(Gate::forUser($this->churchAdmin)->allows('viewAny', EventAttendance::class));
        $this->assertTrue(Gate::forUser($this->churchAdmin)->allows('create', EventAttendance::class));

        // super_admin: wildcard + lintas gereja
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('viewAny', Member::class));
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('viewAny', EventAttendance::class));
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('create', Transaction::class));
        $this->assertTrue(RoleRegistry::isCrossChurch($this->superAdmin));
        $this->assertFalse(RoleRegistry::isCrossChurch($this->churchAdmin));
    }
}
