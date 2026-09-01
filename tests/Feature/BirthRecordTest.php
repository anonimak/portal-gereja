<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BirthRecord;
use App\Models\Church;
use App\Models\Family;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Fase 3B T5 — Modul Kelahiran + Akta Lahir (SPEC-FASE3B-LIFECYCLE).
 *
 * Menutup AC-LC-01, 07, 08, 09, 10, 11, 12, 15, 16 untuk scope kelahiran.
 */
class BirthRecordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function makeAdmin(Church $church): User
    {
        return User::factory()->create([
            'church_id' => $church->id,
            'role' => 'church_admin',
        ]);
    }

    private function makeFamilyWithParents(Church $church): array
    {
        $family = Family::factory()->create(['church_id' => $church->id]);

        $head = Member::factory()->create([
            'church_id' => $church->id,
            'family_id' => $family->id,
            'family_relation' => 'kepala_keluarga',
            'gender' => 'm',
        ]);
        $wife = Member::factory()->create([
            'church_id' => $church->id,
            'family_id' => $family->id,
            'family_relation' => 'istri',
            'gender' => 'f',
        ]);
        $child = Member::factory()->create([
            'church_id' => $church->id,
            'family_id' => $family->id,
            'family_relation' => 'anak',
            'gender' => 'm',
            'birth_place' => 'Kota X',
            'birth_date' => '2025-01-15',
        ]);

        return [$family, $head, $wife, $child];
    }

    // ---- AC-LC-01/07/08: create + default dari member/keluarga + render akta ----

    public function test_birth_record_create_dan_akta_render(): void
    {
        $church = Church::factory()->create();
        $admin = $this->makeAdmin($church);
        [, $head, $wife, $child] = $this->makeFamilyWithParents($church);

        $this->actingAs($admin);

        // AC-07: defaultsFor mengambil birth_date/place dari member, ayah/ibu dari keluarga.
        $defaults = BirthRecord::defaultsFor($child);
        $this->assertSame('2025-01-15', $defaults['birth_date']);
        $this->assertSame('Kota X', $defaults['birth_place_full']);
        $this->assertSame($head->full_name, $defaults['father_name']);
        $this->assertSame($wife->full_name, $defaults['mother_name']);

        $record = BirthRecord::create([
            'member_id' => $child->id,
            'birth_date' => $defaults['birth_date'],
            'birth_place_full' => $defaults['birth_place_full'],
            'father_name' => $defaults['father_name'],
            'mother_name' => $defaults['mother_name'],
            'certificate_number' => 'AKTA-2026-001',
            'issued_at' => now(),
        ]);

        // AC-01: church_id terisi otomatis dari aktor (gereja yang sama dengan member).
        $this->assertNotNull($record->id);
        $this->assertSame($church->id, $record->church_id);
        $this->assertSame($child->id, $record->member_id);

        // AC-08: blade akta render tanpa exception & memuat data penting.
        $html = view('pdf.akta-lahir', ['record' => $record])->render();
        $this->assertStringContainsString($child->full_name, $html);
        $this->assertStringContainsString('AKTA-2026-001', $html);
        $this->assertStringContainsString($head->full_name, $html);

        // Export PDF via route (dompdf) → 200 + content-type PDF.
        $response = $this->get(route('birth-record.export-pdf', $record));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    // ---- AC-08: dokumen render aman dengan data null ----

    public function test_dokumen_render_dengan_data_null_aman(): void
    {
        $church = Church::factory()->create();
        $admin = $this->makeAdmin($church);
        $member = Member::factory()->create([
            'church_id' => $church->id,
            'birth_place' => null,
            'birth_date' => null,
            'gender' => 'f',
        ]);

        $this->actingAs($admin);

        $record = BirthRecord::create([
            'member_id' => $member->id,
            'birth_date' => now(),
            // father_name/mother_name/certificate_number/issued_at sengaja null.
        ]);

        $html = view('pdf.akta-lahir', ['record' => $record])->render();
        $this->assertStringContainsString($member->full_name, $html);
        $this->assertStringNotContainsString('Undefined', $html);
    }

    // ---- AC-09: cross-church FK ditolak 403 ----

    public function test_lifecycle_cross_church_ditolak(): void
    {
        $churchA = Church::factory()->create();
        $churchB = Church::factory()->create();
        $adminA = $this->makeAdmin($churchA);
        $memberB = Member::factory()->create(['church_id' => $churchB->id]);

        $this->actingAs($adminA);

        try {
            BirthRecord::create([
                'member_id' => $memberB->id,
                'birth_date' => now(),
            ]);
            $this->fail('Seharusnya ditolak 403 (AC-LC-09).');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    // ---- AC-10: super_admin mengikuti gereja induk (member B) ----

    public function test_lifecycle_super_admin_mengikuti_church_induk(): void
    {
        $churchB = Church::factory()->create();
        $superAdmin = User::factory()->create([
            'church_id' => Church::factory()->create()->id,
            'role' => 'super_admin',
        ]);
        $memberB = Member::factory()->create(['church_id' => $churchB->id]);

        $this->actingAs($superAdmin);

        $record = BirthRecord::create([
            'member_id' => $memberB->id,
            'birth_date' => now(),
        ]);

        $this->assertSame($churchB->id, $record->church_id, 'church_id harus mengikuti gereja member (AC-LC-10).');
    }

    // ---- AC-11: scope tenant terisolasi ----

    public function test_lifecycle_scope_tenant_terisolasi(): void
    {
        $churchA = Church::factory()->create();
        $churchB = Church::factory()->create();
        $adminA = $this->makeAdmin($churchA);

        BirthRecord::factory()->create(); // gereja acak (bukan A)
        $memberA = Member::factory()->create(['church_id' => $churchA->id]);
        BirthRecord::factory()->create(['member_id' => $memberA->id, 'church_id' => $churchA->id]);

        $this->actingAs($adminA);

        $visible = BirthRecord::query()->pluck('church_id')->unique()->values();
        $this->assertCount(1, $visible);
        $this->assertSame($churchA->id, (int) $visible[0], 'Admin hanya melihat record gereja sendiri (AC-LC-11).');
    }

    // ---- AC-12: finance_admin ditolak ----

    public function test_lifecycle_finance_admin_ditolak(): void
    {
        $church = Church::factory()->create();
        $financeAdmin = User::factory()->create([
            'church_id' => $church->id,
            'role' => 'finance_admin',
        ]);

        $this->actingAs($financeAdmin);

        $this->assertTrue(Gate::denies('viewAny', BirthRecord::class));
        $this->assertTrue(Gate::denies('create', BirthRecord::class));
    }

    // ---- AC-16: soft delete & restore ----

    public function test_lifecycle_soft_delete_dan_restore(): void
    {
        $record = BirthRecord::factory()->create();
        $church = Church::find($record->church_id);
        $admin = $this->makeAdmin($church);

        $this->actingAs($admin);

        $this->assertNull($record->deleted_at);

        $record->delete();
        $this->assertNotNull($record->fresh()->deleted_at);
        $this->assertNull(BirthRecord::find($record->id), 'Tidak muncul di query default (AC-LC-16).');
        $this->assertNotNull(BirthRecord::withTrashed()->find($record->id), 'Masih ada di DB (AC-LC-16).');

        $record->restore();
        $this->assertNull($record->fresh()->deleted_at);
        $this->assertNotNull(BirthRecord::find($record->id));
    }

    // ---- AC-15: audit tercatat ----

    public function test_lifecycle_audit_tercatat(): void
    {
        $church = Church::factory()->create();
        $admin = $this->makeAdmin($church);

        $this->actingAs($admin);

        $record = BirthRecord::factory()->create(['church_id' => $church->id]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => BirthRecord::class,
            'auditable_id' => $record->id,
            'action' => 'created',
            'church_id' => $church->id,
            'user_id' => $admin->id,
        ]);
    }
}
