<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Clusters\Reporting\Pages\WartaJemaat;
use App\Filament\Pages\LaporanRapatPage;
use App\Models\BirthRecord;
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

    private Member $member;

    private Fund $fund;

    private BirthRecord $birthRecord;

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

        // Instance model untuk ability ber-record (update/delete/view) — Gate
        // membutuhkan instance, bukan class-string (class-string di-shift → 1 arg).
        $this->member = Member::factory()->create(['church_id' => $this->church->id]);
        $this->fund = Fund::factory()->create(['church_id' => $this->church->id]);
        $this->birthRecord = BirthRecord::factory()->create(['church_id' => $this->church->id]);
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
        $this->assertTrue(Gate::forUser($this->jemaatAdmin)->allows('update', $this->member));
        $this->assertTrue(Gate::forUser($this->jemaatAdmin)->denies('create', Event::class));
    }

    // ---- AC-T3-05: warta_editor view-only + Warta, tanpa laporan rapat ----

    public function test_warta_editor_view_only_dan_waria_tanpa_laporan_rapat(): void
    {
        $this->assertTrue(Gate::forUser($this->wartaEditor)->allows('viewAny', Member::class));
        $this->assertTrue(Gate::forUser($this->wartaEditor)->allows('viewAny', Event::class));
        $this->assertTrue(Gate::forUser($this->wartaEditor)->allows('viewAny', Transaction::class));

        $this->assertTrue(Gate::forUser($this->wartaEditor)->denies('create', Member::class));
        $this->assertTrue(Gate::forUser($this->wartaEditor)->denies('update', $this->member));
        $this->assertTrue(Gate::forUser($this->wartaEditor)->denies('delete', $this->member));
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
        $this->assertTrue(Gate::forUser($this->reportViewer)->denies('update', $this->fund));
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

    // ---- AC-T3-08 (blocker Vera): BirthRecordPolicy module 'lifecycle' ----
    // Hanya super_admin & church_admin yang boleh view+CRUD BirthRecord;
    // finance_admin / jemaat_admin / warta_editor / report_viewer DITOLAK total
    // (tidak mewarisi member.* dari TenantPolicy).

    public function test_birth_record_policy_hanya_super_admin_dan_church_admin(): void
    {
        // [user, ekspektasi allows] — object TIDAK boleh jadi array key di PHP.
        $cases = [
            [$this->superAdmin, true],
            [$this->churchAdmin, true],
            [$this->financeAdmin, false],
            [$this->jemaatAdmin, false],
            [$this->wartaEditor, false],
            [$this->reportViewer, false],
        ];

        $abilities = ['viewAny', 'view', 'create', 'update', 'delete'];

        foreach ($cases as [$user, $expected]) {
            foreach ($abilities as $ability) {
                $record = in_array($ability, ['view', 'update', 'delete'], true)
                    ? $this->birthRecord
                    : BirthRecord::class;

                if ($expected) {
                    $this->assertTrue(
                        Gate::forUser($user)->allows($ability, $record),
                        "{$user->role} harus BOLEH {$ability} BirthRecord"
                    );
                } else {
                    $this->assertTrue(
                        Gate::forUser($user)->denies($ability, $record),
                        "{$user->role} harus DITOLAK {$ability} BirthRecord"
                    );
                }
            }
        }
    }

    // AC-T3-13: church_admin tetap ter-isolasi — BirthRecord gereja lain ditolak.
    public function test_birth_record_church_admin_tidak_bisa_akses_gereja_lain(): void
    {
        $other = Church::factory()->create();
        $otherRecord = BirthRecord::factory()->create(['church_id' => $other->id]);

        $this->assertTrue(Gate::forUser($this->churchAdmin)->denies('view', $otherRecord));
        $this->assertTrue(Gate::forUser($this->churchAdmin)->denies('update', $otherRecord));
        $this->assertTrue(Gate::forUser($this->churchAdmin)->denies('delete', $otherRecord));

        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('view', $otherRecord));
    }
}
