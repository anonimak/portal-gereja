<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Clusters\Reporting\Pages\WartaJemaat;
use App\Filament\Pages\LaporanRapatPage;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Family;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\Member;
use App\Models\MinistryRole;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RbacPageAccessTest extends TestCase
{
    use RefreshDatabase;

    private Church $church;

    private User $churchAdmin;

    private User $financeAdmin;

    private User $superAdmin;

    private User $reader;

    protected function setUp(): void
    {
        parent::setUp();
        // Environment test tidak punya public/build (Vite build belum dijalankan) —
        // matikan Vite agar render view panel tidak error manifest not found.
        $this->withoutVite();

        $this->church = Church::factory()->create();

        $this->churchAdmin = User::factory()->create([
            'church_id' => $this->church->id,
            'role' => 'church_admin',
        ]);
        $this->financeAdmin = User::factory()->create([
            'church_id' => $this->church->id,
            'role' => 'finance_admin',
        ]);
        $this->superAdmin = User::factory()->create([
            'church_id' => $this->church->id,
            'role' => 'super_admin',
        ]);
        $this->reader = User::factory()->create([
            'church_id' => $this->church->id,
            'role' => 'church_admin',
        ]);
        DB::table('users')->where('id', $this->reader->id)->update(['role' => 'reader']);
        $this->reader = $this->reader->fresh();
    }

    // ---- BLOCK-1 (AC-T2-03): finance_admin dibatasi ke modul keuangan ----

    public function test_finance_admin_ditolak_resource_non_keuangan(): void
    {
        $this->assertTrue(Gate::forUser($this->financeAdmin)->denies('viewAny', Member::class));
        $this->assertTrue(Gate::forUser($this->financeAdmin)->denies('viewAny', Family::class));
        $this->assertTrue(Gate::forUser($this->financeAdmin)->denies('viewAny', Event::class));
        $this->assertTrue(Gate::forUser($this->financeAdmin)->denies('viewAny', EventCategory::class));
        $this->assertTrue(Gate::forUser($this->financeAdmin)->denies('viewAny', MinistryRole::class));
        $this->assertTrue(Gate::forUser($this->financeAdmin)->denies('viewAny', \App\Models\MemberSacrament::class));
    }

    public function test_finance_admin_diizinkan_resource_keuangan(): void
    {
        $this->assertTrue(Gate::forUser($this->financeAdmin)->allows('viewAny', Transaction::class));
        $this->assertTrue(Gate::forUser($this->financeAdmin)->allows('viewAny', Fund::class));
        $this->assertTrue(Gate::forUser($this->financeAdmin)->allows('viewAny', FinancialCategory::class));
    }

    public function test_church_admin_diizinkan_resource_tenant(): void
    {
        $this->assertTrue(Gate::forUser($this->churchAdmin)->allows('viewAny', Member::class));
        $this->assertTrue(Gate::forUser($this->churchAdmin)->allows('viewAny', Transaction::class));
        $this->assertTrue(Gate::forUser($this->churchAdmin)->allows('viewAny', Event::class));
    }

    // ---- BLOCK-2 (AC-T2-04): gerbang akses panel ----

    public function test_can_access_panel_hanya_role_panel(): void
    {
        $panel = \Filament\Facades\Filament::getPanel('admin');

        $this->assertTrue($this->churchAdmin->canAccessPanel($panel));
        $this->assertTrue($this->financeAdmin->canAccessPanel($panel));
        $this->assertTrue($this->superAdmin->canAccessPanel($panel));
        $this->assertFalse($this->reader->canAccessPanel($panel));
    }

    public function test_user_tanpa_role_panel_ditolak_membuka_admin(): void
    {
        $this->actingAs($this->reader);

        $this->get('/admin')->assertStatus(403);
    }

    public function test_church_admin_bisa_membuka_admin(): void
    {
        $this->actingAs($this->churchAdmin);

        $this->get('/admin')->assertStatus(200);
    }

    // ---- BLOCK-3 (AC-T2-06): halaman laporan dibatasi role ----

    public function test_warta_jemaat_hanya_super_admin_dan_church_admin(): void
    {
        $this->actingAs($this->churchAdmin);
        $this->assertTrue(WartaJemaat::canAccess());

        $this->actingAs($this->superAdmin);
        $this->assertTrue(WartaJemaat::canAccess());

        $this->actingAs($this->financeAdmin);
        $this->assertFalse(WartaJemaat::canAccess());

        $this->actingAs($this->reader);
        $this->assertFalse(WartaJemaat::canAccess());
    }

    public function test_laporan_rapat_diizinkan_finance_admin_dan_role_panel(): void
    {
        $this->actingAs($this->churchAdmin);
        $this->assertTrue(LaporanRapatPage::canAccess());

        $this->actingAs($this->financeAdmin);
        $this->assertTrue(LaporanRapatPage::canAccess());

        $this->actingAs($this->superAdmin);
        $this->assertTrue(LaporanRapatPage::canAccess());

        $this->actingAs($this->reader);
        $this->assertFalse(LaporanRapatPage::canAccess());
    }
}
