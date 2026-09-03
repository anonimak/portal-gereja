<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Clusters\System\Resources\AuditLog\AuditLogResource;
use App\Models\AuditLog;
use App\Models\Church;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task A slot sore — viewer Audit Trail (resource read-only, super_admin only).
 */
class AuditLogResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function makeSuperAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    public function test_super_admin_bisa_melihat_halaman_list_audit(): void
    {
        $church = Church::factory()->create();
        $member = Member::factory()->create(['church_id' => $church->id]);
        // Rekam satu baris audit.
        $member->update(['full_name' => 'Nama Baru']);

        // Pastikan aksi update tercatat (factory juga menulis audit 'created',
        // jadi cek baris spesifik 'updated' dengan nilai baru).
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Member::class,
            'auditable_id' => $member->id,
            'action' => 'updated',
        ]);

        $this->actingAs($this->makeSuperAdmin())
            ->get(AuditLogResource::getUrl('index'))
            ->assertOk();
    }

    public function test_church_admin_tidak_bisa_membuka_halaman_audit(): void
    {
        $church = Church::factory()->create();
        $admin = User::factory()->create(['church_id' => $church->id, 'role' => 'church_admin']);

        $this->actingAs($admin)
            ->get(AuditLogResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_super_admin_melihat_audit_semua_gereja_dengan_detail(): void
    {
        $churchA = Church::factory()->create();
        $churchB = Church::factory()->create();

        $memberA = Member::factory()->create(['church_id' => $churchA->id]);
        $memberA->update(['full_name' => 'A Updated']);

        $memberB = Member::factory()->create(['church_id' => $churchB->id]);
        $memberB->update(['full_name' => 'B Updated']);

        $this->actingAs($this->makeSuperAdmin())
            ->get(AuditLogResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Member');

        // Detail baris audit bisa dibuka.
        $log = AuditLog::query()->firstOrFail();
        $this->actingAs($this->makeSuperAdmin())
            ->get(AuditLogResource::getUrl('view', ['record' => $log]))
            ->assertOk();
    }
}
