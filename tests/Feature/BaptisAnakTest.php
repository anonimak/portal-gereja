<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Member;
use App\Models\MemberSacrament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 3B T6 — Modul Baptis Anak + Dokumen Baptis Anak.
 *
 * Sakramen baptis anak tetap di tabel member_sacraments (type=baptis_anak,
 * konsisten dengan Warta & riwayat sakramen). Dokumen diterbitkan via
 * route sakramen.baptis-anak.export-pdf (dompdf).
 *
 * AC yang dicover: AC-LC-08 (render dokumen), AC-LC-09 (cross-church 403),
 * AC-LC-10 (super_admin ikut gereja induk), AC-LC-11 (scope tenant),
 * AC-LC-12 (finance_admin ditolak), AC-LC-15 (audit), AC-LC-16 (soft delete).
 */
class BaptisAnakTest extends TestCase
{
    use RefreshDatabase;

    private Church $churchA;

    private Church $churchB;

    private User $churchAdmin;

    private User $financeAdmin;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->churchA = Church::factory()->create(['name' => 'Gereja A']);
        $this->churchB = Church::factory()->create(['name' => 'Gereja B']);

        $this->superAdmin = $this->makeUser('super_admin', $this->churchA);
        $this->churchAdmin = $this->makeUser('church_admin', $this->churchA);
        $this->financeAdmin = $this->makeUser('finance_admin', $this->churchA);
    }

    private function makeUser(string $role, Church $church): User
    {
        return User::withoutEvents(fn () => User::factory()->create([
            'church_id' => $church->id,
            'role' => $role,
        ]));
    }

    private function makeBaptisAnak(Church $church): MemberSacrament
    {
        $member = Member::factory()->create([
            'church_id' => $church->id,
            'family_relation' => 'anak',
            'gender' => 'm',
            'birth_place' => 'Jakarta',
            'birth_date' => '2023-05-10',
        ]);

        return MemberSacrament::factory()->create([
            'church_id' => $church->id,
            'member_id' => $member->id,
            'type' => 'baptis_anak',
            'sacrament_date' => '2024-08-15',
            'certificate_number' => 'BA-2024-001',
            'issued_at' => '2024-08-20',
        ]);
    }

    public function test_baptis_anak_create_dan_dokumen_render(): void
    {
        $record = $this->makeBaptisAnak($this->churchA);

        $response = $this->actingAs($this->churchAdmin)
            ->get(route('sakramen.baptis-anak.export-pdf', $record->id));

        $response->assertOk();
        $this->assertStringContainsString('%PDF', $response->getContent());
    }

    public function test_baptis_anak_dokumen_null_data_aman(): void
    {
        // Sakramen tanpa member (relasi null) — blade harus null-safe, tidak crash.
        $record = MemberSacrament::factory()->create([
            'church_id' => $this->churchA->id,
            'type' => 'baptis_anak',
            'sacrament_date' => '2024-08-15',
            'certificate_number' => null,
            'issued_at' => null,
        ]);

        $response = $this->actingAs($this->churchAdmin)
            ->get(route('sakramen.baptis-anak.export-pdf', $record->id));

        $response->assertOk();
    }

    public function test_baptis_anak_cross_church_ditolak(): void
    {
        // AC-LC-09: admin gereja A tidak bisa mengunduh dokumen sakramen gereja B.
        $record = $this->makeBaptisAnak($this->churchB);

        $response = $this->actingAs($this->churchAdmin)
            ->get(route('sakramen.baptis-anak.export-pdf', $record->id));

        $response->assertForbidden();
    }

    public function test_baptis_anak_finance_admin_ditolak(): void
    {
        // AC-LC-12: finance_admin ditolak akses modul lifecycle.
        $record = $this->makeBaptisAnak($this->churchA);

        $response = $this->actingAs($this->financeAdmin)
            ->get(route('sakramen.baptis-anak.export-pdf', $record->id));

        $response->assertForbidden();
    }

    public function test_baptis_anak_super_admin_lintas_gereja(): void
    {
        // AC-LC-10/11: super_admin bisa mengunduh dokumen gereja lain.
        $record = $this->makeBaptisAnak($this->churchB);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('sakramen.baptis-anak.export-pdf', $record->id));

        $response->assertOk();
    }

    public function test_baptis_anak_scope_tenant_terisolasi(): void
    {
        // AC-LC-11: list default hanya data gereja sendiri (global scope).
        $this->makeBaptisAnak($this->churchA);
        $this->makeBaptisAnak($this->churchB);

        $this->actingAs($this->churchAdmin);

        // Global scope BelongsToChurch aktif saat query dijalankan → hanya gereja A.
        $countA = MemberSacrament::query()->where('type', 'baptis_anak')->count();
        $this->assertSame(1, $countA);

        // Super admin melihat semua gereja (scope dinonaktifkan).
        $this->actingAs($this->superAdmin);
        $this->assertSame(2, MemberSacrament::query()->where('type', 'baptis_anak')->count());
    }

    public function test_baptis_anak_soft_delete_dan_restore(): void
    {
        // AC-LC-16: soft delete tidak menghapus record; restore mengembalikan.
        $record = $this->makeBaptisAnak($this->churchA);

        $this->actingAs($this->churchAdmin);
        $record->delete();

        $this->assertSoftDeleted('member_sacraments', ['id' => $record->id]);
        $this->assertSame(0, MemberSacrament::query()->where('type', 'baptis_anak')->count());

        $record->restore();

        $this->assertNotSoftDeleted('member_sacraments', ['id' => $record->id]);
        $this->assertSame(1, MemberSacrament::query()->where('type', 'baptis_anak')->count());
    }

    public function test_baptis_anak_audit_tercatat(): void
    {
        // AC-LC-15: create tercatat di audit_logs dgn church_id.
        $record = $this->makeBaptisAnak($this->churchA);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => MemberSacrament::class,
            'auditable_id' => $record->id,
            'action' => 'created',
            'church_id' => $this->churchA->id,
        ]);
    }

    public function test_baptis_anak_route_menolak_unauthenticated(): void
    {
        $record = $this->makeBaptisAnak($this->churchA);

        $this->get(route('sakramen.baptis-anak.export-pdf', $record->id))
            ->assertRedirect(route('login'));
    }

    public function test_baptis_anak_gender_mapping_label(): void
    {
        // HIGH-1 Vera: nilai DB gender = enum 'm'/'f' (bukan 'L'/'P').
        $this->assertSame('Laki-laki', \App\Http\Controllers\BaptisAnakExportController::genderLabel('m'));
        $this->assertSame('Perempuan', \App\Http\Controllers\BaptisAnakExportController::genderLabel('f'));
        $this->assertSame('', \App\Http\Controllers\BaptisAnakExportController::genderLabel(null));
    }

    public function test_baptis_anak_dokumen_gender_tertampil_benar(): void
    {
        // Render view dokumen — label gender 'Laki-laki'/'Perempuan' muncul,
        // bukan nilai mentah 'm'/'f'.
        $htmlL = view('pdf.dokumen-baptis-anak', [
            'churchName' => 'Gereja A',
            'childName' => 'Anak Test',
            'gender' => \App\Http\Controllers\BaptisAnakExportController::genderLabel('m'),
        ])->render();
        $this->assertStringContainsString('Laki-laki', $htmlL);

        $htmlP = view('pdf.dokumen-baptis-anak', [
            'churchName' => 'Gereja A',
            'childName' => 'Anak Test',
            'gender' => \App\Http\Controllers\BaptisAnakExportController::genderLabel('f'),
        ])->render();
        $this->assertStringContainsString('Perempuan', $htmlP);
    }

    public function test_baptis_anak_soft_deleted_tidak_bisa_diunduh(): void
    {
        // MED-1 Vera: record yang sudah di-soft-delete tidak boleh lagi diunduh.
        // withoutGlobalScope('church') mempertahankan SoftDeletingScope sehingga
        // findOrFail -> 404 (bukan 200 dengan dokumen).
        $record = $this->makeBaptisAnak($this->churchA);

        $this->actingAs($this->churchAdmin);
        $record->delete();

        $this->assertSoftDeleted('member_sacraments', ['id' => $record->id]);

        $response = $this->actingAs($this->churchAdmin)
            ->get(route('sakramen.baptis-anak.export-pdf', $record->id));

        $response->assertNotFound();
    }
}
