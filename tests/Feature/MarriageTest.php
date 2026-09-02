<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Marriage;
use App\Models\Member;
use App\Models\MemberSacrament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 3B T9 — Modul Pernikahan (Akta Nikah).
 *
 * AC yang dicover:
 * - AC-LC-04: pernikahan otomatis membuat 2 baris sakramen 'nikah' (suami & istri)
 * - AC-LC-08: dokumen Akta Nikah render (PDF), null-safe
 * - AC-LC-09: cross-church 403 (church_admin gereja lain)
 * - AC-LC-10: super_admin bisa membuka gereja lain (tanpa filter gereja sendiri)
 * - AC-LC-11: scope tenant (list hanya data gereja sendiri)
 * - AC-LC-12: finance_admin ditolak terbitkan akta
 * - AC-LC-15: audit trail tercatat
 * - AC-LC-16: soft delete + sakramen ikut ter-soft-delete
 */
class MarriageTest extends TestCase
{
    use RefreshDatabase;

    private Church $churchA;

    private Church $churchB;

    private User $superAdmin;

    private User $churchAdminA;

    private User $churchAdminB;

    private User $financeAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->churchA = Church::factory()->create(['name' => 'Gereja A']);
        $this->churchB = Church::factory()->create(['name' => 'Gereja B']);

        $this->superAdmin = $this->makeUser('super_admin', $this->churchA);
        $this->churchAdminA = $this->makeUser('church_admin', $this->churchA);
        $this->churchAdminB = $this->makeUser('church_admin', $this->churchB);
        $this->financeAdmin = $this->makeUser('finance_admin', $this->churchA);
    }

    private function makeUser(string $role, Church $church): User
    {
        return User::withoutEvents(fn () => User::factory()->create([
            'church_id' => $church->id,
            'role' => $role,
        ]));
    }

    private function makeMarriage(Church $church): Marriage
    {
        $husband = Member::factory()->create([
            'church_id' => $church->id,
            'gender' => 'm',
        ]);
        $wife = Member::factory()->create([
            'church_id' => $church->id,
            'gender' => 'f',
        ]);

        return Marriage::factory()->create([
            'church_id' => $church->id,
            'husband_member_id' => $husband->id,
            'wife_member_id' => $wife->id,
            'marriage_date' => '2024-06-15',
            'location' => 'Gereja A',
            'witness_names' => ['Saksi Satu', 'Saksi Dua'],
            'certificate_number' => 'NK-2024-001',
            'issued_at' => '2024-06-20',
        ]);
    }

    public function test_pernikahan_otomatis_membuat_dua_sakramen_nikah(): void
    {
        $marriage = $this->makeMarriage($this->churchA);

        $sacraments = MemberSacrament::query()
            ->where('marriage_id', $marriage->id)
            ->get();

        $this->assertCount(2, $sacraments);
        $this->assertTrue($sacraments->every(fn (MemberSacrament $s) => $s->type === 'nikah'));
        $this->assertTrue($sacraments->contains('member_id', $marriage->husband_member_id));
        $this->assertTrue($sacraments->contains('member_id', $marriage->wife_member_id));
        $this->assertTrue($sacraments->every(fn (MemberSacrament $s) => $s->church_id === $this->churchA->id));
    }

    public function test_akta_nikah_dokumen_render(): void
    {
        $marriage = $this->makeMarriage($this->churchA);

        $response = $this->actingAs($this->churchAdminA)
            ->get(route('marriage.export-pdf', $marriage->id));

        $response->assertOk();
        $this->assertStringContainsString('%PDF', $response->getContent());
    }

    public function test_akta_nikah_dokumen_null_data_aman(): void
    {
        // Pasangan VALID + field dokumen null — blade harus null-safe, tidak crash.
        $marriage = $this->makeMarriage($this->churchA);
        $marriage->update([
            // marriage_date NOT NULL di DB — biarkan terisi; uji null-safe
            // pada field dokumen yang memang nullable.
            'certificate_number' => null,
            'issued_at' => null,
            'location' => null,
        ]);

        $response = $this->actingAs($this->churchAdminA)
            ->get(route('marriage.export-pdf', $marriage->id));

        $response->assertOk();
        $this->assertStringContainsString('%PDF', $response->getContent());
    }

    public function test_cross_church_ditolak_403(): void
    {
        $marriage = $this->makeMarriage($this->churchA);

        $this->actingAs($this->churchAdminB)
            ->get(route('marriage.export-pdf', $marriage->id))
            ->assertForbidden();
    }

    public function test_super_admin_bisa_buka_gereja_lain(): void
    {
        $marriage = $this->makeMarriage($this->churchB);

        $this->actingAs($this->superAdmin)
            ->get(route('marriage.export-pdf', $marriage->id))
            ->assertOk();
    }

    public function test_scope_tenant_list_hanya_gereja_sendiri(): void
    {
        $this->makeMarriage($this->churchA);
        $this->makeMarriage($this->churchB);

        $count = Marriage::query()->count();

        // Tanpa auth: global scope BelongsToChurch nonaktif (query semua).
        // Dengan actingAs churchAdminA: hanya data gereja A.
        $this->actingAs($this->churchAdminA);
        $this->assertSame(1, Marriage::query()->count());
        $this->assertNotSame($count, Marriage::query()->count());
    }

    public function test_finance_admin_ditolak_terbitkan_akta(): void
    {
        $marriage = $this->makeMarriage($this->churchA);

        $this->actingAs($this->financeAdmin)
            ->get(route('marriage.export-pdf', $marriage->id))
            ->assertForbidden();
    }

    public function test_audit_trail_tercatat(): void
    {
        $marriage = $this->makeMarriage($this->churchA);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Marriage::class,
            'auditable_id' => $marriage->id,
            'action' => 'created',
        ]);
    }

    public function test_soft_delete_menghapus_sakramen_nikah_juga(): void
    {
        $marriage = $this->makeMarriage($this->churchA);
        $this->assertCount(2, MemberSacrament::query()->where('marriage_id', $marriage->id)->get());

        $marriage->delete();

        $this->assertSoftDeleted('marriages', ['id' => $marriage->id]);
        $this->assertSame(0, MemberSacrament::query()->where('marriage_id', $marriage->id)->count());

        // Restore -> sakramen ikut restore (konsisten cascade soft-delete).
        $marriage->restore();
        $this->assertSame(2, MemberSacrament::query()->where('marriage_id', $marriage->id)->count());
    }
}
