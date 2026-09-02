<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Church;
use App\Models\GuidanceProgram;
use App\Models\Member;
use App\Models\MemberSacrament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 3B T8 — Modul Sidi / Baptis Dewasa + Dokumen.
 *
 * Sakramen sidi/baptis_dewasa tetap di tabel member_sacraments (type=sidi /
 * baptis_dewasa, konsisten dengan Warta & riwayat sakramen). Program bimbingan
 * pra-sidi ditautkan via member_sacraments.program_id (migrasi T8).
 * Dokumen diterbitkan via route sakramen.sidi.export-pdf (dompdf).
 *
 * AC: AC-LC-06 (program selesai tersedia), AC-LC-08 (render dokumen null-safe),
 * AC-LC-09 (cross-church 403), AC-LC-10 (super_admin ikut gereja induk),
 * AC-LC-11 (scope tenant), AC-LC-12 (finance_admin ditolak),
 * AC-LC-13 (URL record gereja lain 403), AC-LC-15 (audit), AC-LC-16 (soft delete).
 */
class SidiTest extends TestCase
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

    private function makeMember(Church $church, string $gender = 'm'): Member
    {
        return Member::factory()->create([
            'church_id' => $church->id,
            'gender' => $gender,
            'birth_place' => 'Jakarta',
            'birth_date' => '2000-05-10',
        ]);
    }

    private function makeSidi(Church $church, string $type = 'sidi', ?GuidanceProgram $program = null): MemberSacrament
    {
        $member = $this->makeMember($church);

        return MemberSacrament::factory()->create([
            'church_id' => $church->id,
            'member_id' => $member->id,
            'type' => $type,
            'sacrament_date' => '2024-08-15',
            'certificate_number' => 'SD-2024-001',
            'issued_at' => '2024-08-20',
            'program_id' => $program?->id,
        ]);
    }

    public function test_sidi_create_dan_dokumen_render(): void
    {
        $record = $this->makeSidi($this->churchA, 'sidi');

        $response = $this->actingAs($this->churchAdmin)
            ->get(route('sakramen.sidi.export-pdf', $record->id));

        $response->assertOk();
        $this->assertStringContainsString('%PDF', $response->getContent());
    }

    public function test_baptis_dewasa_dokumen_render(): void
    {
        $record = $this->makeSidi($this->churchA, 'baptis_dewasa');

        $response = $this->actingAs($this->churchAdmin)
            ->get(route('sakramen.sidi.export-pdf', $record->id));

        $response->assertOk();
    }

    public function test_sidi_dokumen_null_data_aman(): void
    {
        // Sakramen tanpa member/program — blade null-safe, tidak crash (AC-LC-08).
        $record = MemberSacrament::factory()->create([
            'church_id' => $this->churchA->id,
            'type' => 'sidi',
            'sacrament_date' => '2024-08-15',
            'certificate_number' => null,
            'issued_at' => null,
            'program_id' => null,
        ]);

        $response = $this->actingAs($this->churchAdmin)
            ->get(route('sakramen.sidi.export-pdf', $record->id));

        $response->assertOk();
    }

    public function test_sidi_cross_church_ditolak(): void
    {
        // AC-LC-09/13: admin gereja A tidak bisa mengunduh dokumen sakramen gereja B.
        $record = $this->makeSidi($this->churchB);

        $response = $this->actingAs($this->churchAdmin)
            ->get(route('sakramen.sidi.export-pdf', $record->id));

        $response->assertForbidden();
    }

    public function test_sidi_finance_admin_ditolak(): void
    {
        // AC-LC-12: finance_admin ditolak menerbitkan dokumen lifecycle.
        $record = $this->makeSidi($this->churchA);

        $response = $this->actingAs($this->financeAdmin)
            ->get(route('sakramen.sidi.export-pdf', $record->id));

        $response->assertForbidden();
    }

    public function test_sidi_super_admin_lintas_gereja(): void
    {
        // AC-LC-10/13: super_admin boleh mengunduh dokumen sakramen gereja lain.
        $record = $this->makeSidi($this->churchB);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('sakramen.sidi.export-pdf', $record->id));

        $response->assertOk();
    }

    public function test_sidi_program_id_tersimpan(): void
    {
        // AC-LC-06: sakramen sidi menautkan program pra-sidi yang diselesaikan.
        $program = GuidanceProgram::factory()->create([
            'church_id' => $this->churchA->id,
            'type' => 'pra_sidi',
            'status' => 'selesai',
            'title' => 'Katakisasi Angkatan 2026-1',
        ]);

        $record = $this->makeSidi($this->churchA, 'sidi', $program);

        $this->assertSame($program->id, $record->program_id);
        $this->assertSame($program->title, $record->program?->title);
    }

    public function test_sidi_soft_delete_dan_restore(): void
    {
        // AC-LC-16: soft delete -> tidak muncul di list default; restore -> kembali.
        $record = $this->makeSidi($this->churchA);

        $this->assertNull($record->deleted_at);

        $record->delete();

        $this->assertNotNull($record->fresh()->deleted_at);
        $this->assertNull(MemberSacrament::query()->find($record->id));

        $record->restore();

        $this->assertNotNull(MemberSacrament::query()->find($record->id));
    }

    public function test_sidi_audit_tercatat(): void
    {
        // AC-LC-15: create + delete tercatat di audit_logs dengan church_id.
        $record = $this->makeSidi($this->churchA);
        $record->delete();

        $audits = AuditLog::query()
            ->where('auditable_type', MemberSacrament::class)
            ->where('auditable_id', $record->id)
            ->orderBy('id')
            ->get();

        $this->assertGreaterThanOrEqual(2, $audits->count());
        $this->assertTrue($audits->contains(fn ($a) => $a->action === 'created'));
        $this->assertTrue($audits->contains(fn ($a) => $a->action === 'deleted'));
    }

    public function test_sidi_scope_tenant_terisolasi(): void
    {
        // AC-LC-11: list sakramen admin gereja A hanya menampilkan gereja A.
        $this->makeSidi($this->churchA);
        $this->makeSidi($this->churchB);

        $this->actingAs($this->churchAdmin);

        $count = MemberSacrament::query()
            ->where('type', 'sidi')
            ->count();

        $this->assertSame(1, $count);
    }
}
