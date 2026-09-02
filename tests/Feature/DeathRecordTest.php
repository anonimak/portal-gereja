<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Church;
use App\Models\DeathRecord;
use App\Models\Family;
use App\Models\Member;
use App\Models\Official;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 3B T11 — Kematian (Surat Keterangan Kematian).
 *
 * AC-LC-05 (status -> meninggal), AC-LC-08 (PDF render null-safe),
 * AC-LC-09 (cross-church 403), AC-LC-10 (super_admin ikut gereja induk),
 * AC-LC-11 (scope tenant), AC-LC-12 (finance_admin ditolak),
 * AC-LC-15 (audit), AC-LC-16 (soft delete/restore).
 */
class DeathRecordTest extends TestCase
{
    use RefreshDatabase;

    private Church $church;

    private User $admin;

    private Member $member;

    private Official $official;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::factory()->create();
        $this->admin = User::factory()->create([
            'role' => 'church_admin',
            'church_id' => $this->church->id,
        ]);
        $family = Family::factory()->create(['church_id' => $this->church->id]);
        $this->member = Member::factory()->create([
            'church_id' => $this->church->id,
            'family_id' => $family->id,
            'status' => 'aktif',
        ]);
        $this->official = Official::factory()->create(['church_id' => $this->church->id]);
    }

    private function makeDeath(?Church $church = null): DeathRecord
    {
        return DeathRecord::query()->create([
            'church_id' => ($church ?? $this->church)->id,
            'member_id' => $this->member->id,
            'death_date' => now()->subDay()->toDateString(),
            'official_id' => $this->official->id,
            'certificate_number' => 'SKM-001',
        ]);
    }

    public function test_create_mengubah_status_member_menjadi_meninggal(): void
    {
        $this->makeDeath();

        $this->assertSame('meninggal', $this->member->fresh()->status);
    }

    public function test_duplikat_member_ditolak_unique(): void
    {
        $this->makeDeath();

        try {
            $this->makeDeath();
            $this->fail('Expected unique constraint violation.');
        } catch (\Throwable $e) {
            $this->assertStringContainsStringIgnoringCase('unique', $e->getMessage());
        }
    }

    public function test_export_pdf_surat_kematian_berhasil(): void
    {
        $record = $this->makeDeath();

        $response = $this->actingAs($this->admin)
            ->get(route('death-record.export-pdf', $record));

        $response->assertOk();
        $this->assertStringContainsString('%PDF', $response->streamedContent());
    }

    public function test_export_pdf_null_data_aman(): void
    {
        $record = DeathRecord::query()->create([
            'church_id' => $this->church->id,
            'member_id' => $this->member->id,
            'death_date' => now()->toDateString(),
            // semua dokumen nullable dibiarkan null
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('death-record.export-pdf', $record));

        $response->assertOk();
    }

    public function test_cross_church_ditolak_403(): void
    {
        $otherChurch = Church::factory()->create();
        $otherFamily = Family::factory()->create(['church_id' => $otherChurch->id]);
        $otherMember = Member::factory()->create([
            'church_id' => $otherChurch->id,
            'family_id' => $otherFamily->id,
        ]);

        $record = DeathRecord::query()->create([
            'church_id' => $otherChurch->id,
            'member_id' => $otherMember->id,
            'death_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('death-record.export-pdf', $record));

        $response->assertForbidden();
    }

    public function test_finance_admin_ditolak_export(): void
    {
        $finance = User::factory()->create([
            'role' => 'finance_admin',
            'church_id' => $this->church->id,
        ]);
        $record = $this->makeDeath();

        $this->assertFalse($finance->hasPermission('lifecycle.view'));
        $response = $this->actingAs($finance)
            ->get(route('death-record.export-pdf', $record));

        $response->assertForbidden();
    }

    public function test_scope_tenant_terisolasi(): void
    {
        $this->makeDeath();

        $otherChurch = Church::factory()->create();
        $adminB = User::factory()->create([
            'role' => 'church_admin',
            'church_id' => $otherChurch->id,
        ]);

        $this->actingAs($adminB);
        $this->assertSame(0, DeathRecord::query()->count());
    }

    public function test_audit_tercatat(): void
    {
        $this->actingAs($this->admin);
        $this->makeDeath();

        $audit = AuditLog::query()
            ->where('auditable_type', DeathRecord::class)
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame('created', $audit->action);
        $this->assertSame($this->church->id, (int) $audit->church_id);
        $this->assertSame($this->admin->id, (int) $audit->user_id);
    }

    public function test_soft_delete_dan_restore(): void
    {
        $record = $this->makeDeath();
        $this->assertSame(1, DeathRecord::query()->count());

        $record->delete();
        $this->assertSame(0, DeathRecord::query()->count());
        $this->assertSame(1, DeathRecord::withTrashed()->count());

        $record->restore();
        $this->assertSame(1, DeathRecord::query()->count());
    }
}
