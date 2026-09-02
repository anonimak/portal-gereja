<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\GuidanceProgram;
use App\Models\GuidanceSession;
use App\Models\GuidanceSessionMember;
use App\Models\GuidanceTemplate;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\GuidanceTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 3B T10 — Bimbingan Pra-Nikah.
 *
 * Infra template/program/sesi/peserta sudah ada dari T7 (AC-LC-17/18/19);
 * T10 memverifikasi alur PRA-NIKAH spesifik: template 12 sesi pra-nikah,
 * program type=pra_nikah dari template → 12 sesi auto, peserta pasangan
 * (2 member), sesi tetap bisa disesuaikan.
 */
class GuidancePraNikahTest extends TestCase
{
    use RefreshDatabase;

    private Church $church;

    private User $admin;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::factory()->create();
        $this->admin = User::factory()->create([
            'role' => 'church_admin',
            'church_id' => $this->church->id,
        ]);
        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'church_id' => $this->church->id,
        ]);

        // Seed 2 template default (Pra-Sidi & Pra-Nikah, 12 sesi) — AC-LC-17.
        (new GuidanceTemplateSeeder)->run($this->church->id);
    }

    private function praNikahTemplate(): GuidanceTemplate
    {
        return GuidanceTemplate::query()
            ->where('church_id', $this->church->id)
            ->where('type', 'pra_nikah')
            ->firstOrFail();
    }

    public function test_seeder_template_pra_nikah_12_sesi_topik_urut(): void
    {
        $template = $this->praNikahTemplate();

        $this->assertSame('pra_nikah', $template->type);
        $this->assertSame(12, $template->sessions()->count());

        $sessions = $template->sessions()->orderBy('session_number')->get();
        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], $sessions->pluck('session_number')->all());
        // Topik pertama & terakhir sesuai spec (AC-LC-17 urut).
        $this->assertSame('Makna Pernikahan Kristen', $sessions->first()->topic);
        $this->assertSame('Persiapan Pemberkatan Nikah', $sessions->last()->topic);
    }

    public function test_program_pra_nikah_dari_template_membuat_12_sesi(): void
    {
        $program = GuidanceProgram::query()->create([
            'church_id' => $this->church->id,
            'type' => 'pra_nikah',
            'title' => 'Bimbingan Pra-Nikah Pasangan A',
            'status' => 'draft',
            'template_id' => $this->praNikahTemplate()->id,
        ]);

        $this->assertSame(12, $program->sessions()->count());
        $this->assertSame('pra_nikah', $program->type);
        $this->assertSame(
            'Makna Pernikahan Kristen',
            $program->sessions()->orderBy('session_number')->first()->title
        );
        // Template sumber tidak berubah.
        $this->assertSame(12, $this->praNikahTemplate()->sessions()->count());
    }

    public function test_program_pra_nikah_tanpa_template_sesi_manual(): void
    {
        $program = GuidanceProgram::query()->create([
            'church_id' => $this->church->id,
            'type' => 'pra_nikah',
            'title' => 'Pra-Nikah Manual',
            'status' => 'draft',
        ]);

        $this->assertSame(0, $program->sessions()->count());

        // Sesi manual bisa ditambahkan (AC-LC-19: sesi tetap bisa disesuaikan).
        GuidanceSession::query()->create([
            'church_id' => $this->church->id,
            'program_id' => $program->id,
            'session_number' => 1,
            'title' => 'Pertemuan Khusus',
        ]);

        $this->assertSame(1, $program->sessions()->count());
    }

    public function test_peserta_pasangan_dua_member_dalam_program_pra_nikah(): void
    {
        $program = GuidanceProgram::query()->create([
            'church_id' => $this->church->id,
            'type' => 'pra_nikah',
            'title' => 'Pra-Nikah Pasangan B',
            'status' => 'berjalan',
        ]);
        $session = GuidanceSession::query()->create([
            'church_id' => $this->church->id,
            'program_id' => $program->id,
            'session_number' => 1,
            'title' => 'Makna Pernikahan Kristen',
        ]);

        $husband = Member::factory()->create(['church_id' => $this->church->id]);
        $wife = Member::factory()->create(['church_id' => $this->church->id]);

        // Peserta = 2 member (pasangan) — AC-LC-03 pivot restore-or-create.
        GuidanceSessionMember::query()->create([
            'church_id' => $this->church->id,
            'session_id' => $session->id,
            'member_id' => $husband->id,
        ]);
        GuidanceSessionMember::query()->create([
            'church_id' => $this->church->id,
            'session_id' => $session->id,
            'member_id' => $wife->id,
        ]);

        $this->assertSame(2, $session->members()->count());

        // Re-add member soft-deleted pada sesi yang sama → restore, bukan duplikat.
        $session->members()->first()->pivot->delete();
        $this->assertSame(1, $session->members()->count());

        GuidanceSessionMember::query()->create([
            'church_id' => $this->church->id,
            'session_id' => $session->id,
            'member_id' => $husband->id,
        ]);
        $this->assertSame(2, $session->members()->count());
    }

    public function test_finance_admin_ditolak_akses_program_pra_nikah(): void
    {
        $finance = User::factory()->create([
            'role' => 'finance_admin',
            'church_id' => $this->church->id,
        ]);

        $this->assertFalse($finance->hasPermission('lifecycle.view'));
        $this->assertFalse($finance->hasPermission('lifecycle.create'));
    }

    public function test_super_admin_melihat_semua_gereja_scope_tenant_terisolasi(): void
    {
        $otherChurch = Church::factory()->create();
        (new GuidanceTemplateSeeder)->run($otherChurch->id);

        // super_admin membuat program di gereja lain → church_id mengikuti gereja target.
        $this->actingAs($this->superAdmin);
        GuidanceProgram::query()->create([
            'church_id' => $otherChurch->id,
            'type' => 'pra_nikah',
            'title' => 'Pra-Nikah Gereja Lain',
            'status' => 'draft',
        ]);

        // super_admin melihat semua gereja (global scope dinonaktifkan utk super_admin).
        $this->assertSame(1, GuidanceProgram::query()->count());

        // church_admin hanya melihat gereja sendiri (A) — program di gereja B tidak terlihat.
        $this->actingAs($this->admin);
        $this->assertSame(0, GuidanceProgram::query()->count());
    }
}
