<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Church;
use App\Models\GuidanceProgram;
use App\Models\GuidanceSession;
use App\Models\GuidanceSessionMember;
use App\Models\GuidanceTemplate;
use App\Models\GuidanceTemplateSession;
use App\Models\Member;
use App\Models\Official;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Fase 3B T7 — Modul Bimbingan Pra-Sidi (template + program + sesi + peserta).
 *
 * Menutup AC-LC-03, AC-LC-09 s/d AC-LC-20 (template 12 sesi, instantiate,
 * sesi disesuaikan, program tanpa template, restore-or-create, tenant, RBAC,
 * audit, soft delete).
 *
 * Catatan A11: RBAC T3 (role jemaat_admin/warta_editor/report_viewer) belum di
 * master — role tersebut dibuat via DB::table()->update (bypass UserObserver),
 * pola yang sama dengan ExportRouteTest. Saat T3 merge, ganti dengan
 * User::factory()->create(['role' => ...]) langsung.
 */
class GuidanceBimbinganTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function makeChurch(): Church
    {
        return Church::factory()->create();
    }

    /**
     * Buat user dengan role apa pun. Role non-lama (T3) dibuat via bypass
     * observer (DB::table update) karena UserObserver master hanya mengizinkan
     * super_admin/church_admin/finance_admin.
     */
    private function makeUser(Church $church, string $role): User
    {
        $user = User::factory()->create([
            'church_id' => $church->id,
            'role' => 'church_admin',
        ]);

        if ($role !== 'church_admin') {
            DB::table('users')->where('id', $user->id)->update(['role' => $role]);
            $user->role = $role;
        }

        return $user;
    }

    private function makeMember(Church $church): Member
    {
        return Member::factory()->create(['church_id' => $church->id]);
    }

    private function makeOfficial(Church $church): Official
    {
        $member = $this->makeMember($church);

        return Official::factory()->create([
            'church_id' => $church->id,
            'member_id' => $member->id,
            'type' => 'majelis_lokal',
        ]);
    }

    /**
     * Buat program gereja tertentu TANPA auth — supaya trait BelongsToChurch
     * tidak memaksa church_id ke gereja aktor (data gereja lain harus dibuat
     * netral, seperti via console/seeder).
     */
    private function makeProgram(Church $church, string $type = 'pra_sidi', ?GuidanceTemplate $template = null): GuidanceProgram
    {
        return GuidanceProgram::create([
            'church_id' => $church->id,
            'type' => $type,
            'title' => 'Program '.$church->id.'-'.$type,
            'status' => 'draft',
            'template_id' => $template?->id,
        ]);
    }

    // ---- AC-LC-17: seeder default (via ChurchObserver) → 2 template × 12 sesi ----

    public function test_guidance_seeder_template_default_12_sesi(): void
    {
        $church = $this->makeChurch();

        $this->assertSame(2, GuidanceTemplate::where('church_id', $church->id)->count());
        $this->assertSame(2, GuidanceTemplate::where('church_id', $church->id)->where('is_default', true)->count());

        foreach (['pra_sidi', 'pra_nikah'] as $type) {
            $template = GuidanceTemplate::where('church_id', $church->id)->where('type', $type)->firstOrFail();
            $this->assertSame(12, $template->session_count);

            $sessions = GuidanceTemplateSession::where('template_id', $template->id)->orderBy('session_number')->get();
            $this->assertCount(12, $sessions);
            $this->assertSame(range(1, 12), $sessions->pluck('session_number')->all(), "session_number harus 1..12 utk {$type}.");
            foreach ($sessions as $session) {
                $this->assertNotEmpty($session->topic);
            }
        }
    }

    // ---- AC-LC-18: program dari template → 12 sesi otomatis ----

    public function test_guidance_program_dari_template_membuat_12_sesi(): void
    {
        $church = $this->makeChurch();
        $admin = $this->makeUser($church, 'church_admin');
        $this->actingAs($admin);

        $template = GuidanceTemplate::where('church_id', $church->id)->where('type', 'pra_sidi')->firstOrFail();
        $templateSessionCount = $template->sessions()->count();

        $program = GuidanceProgram::create([
            'church_id' => $church->id,
            'type' => 'pra_sidi',
            'title' => 'Katakisasi Angkatan 2026-1',
            'status' => 'draft',
            'template_id' => $template->id,
        ]);

        // create() dengan template_id sudah auto-instantiate 12 sesi (AC-LC-18);
        // panggilan eksplisit berikutnya idempotent (tidak menduplikasi).
        $created = $program->instantiateFromTemplate();

        $this->assertSame(0, $created, 'idempotent: sesi sudah dibuat otomatis saat create');
        $this->assertSame(12, $program->sessions()->count());
        $this->assertSame($template->id, $program->template_id);

        // title sesi = topik template urut
        $topics = $template->sessions()->orderBy('session_number')->pluck('topic')->all();
        $sessionTitles = $program->sessions()->orderBy('id')->pluck('title')->all();
        $this->assertSame($topics, $sessionTitles);

        // session_at & official_id null (disesuaikan manual)
        $program->sessions()->get()->each(function (GuidanceSession $session): void {
            $this->assertNull($session->session_at);
            $this->assertNull($session->official_id);
        });

        // template sumber TIDAK berubah
        $this->assertSame($templateSessionCount, $template->sessions()->count());
        $this->assertSame(12, $template->sessions()->count());
    }

    // ---- AC-LC-19: sesi tetap bisa disesuaikan tanpa mengubah template ----

    public function test_guidance_sesi_dari_template_bisa_disesuaikan(): void
    {
        $church = $this->makeChurch();
        $admin = $this->makeUser($church, 'church_admin');
        $official = $this->makeOfficial($church);
        $this->actingAs($admin);

        $template = GuidanceTemplate::where('church_id', $church->id)->where('type', 'pra_sidi')->firstOrFail();
        $program = GuidanceProgram::create([
            'church_id' => $church->id,
            'type' => 'pra_sidi',
            'title' => 'Katakisasi 2026-2',
            'status' => 'berjalan',
            'template_id' => $template->id,
        ]);
        $program->instantiateFromTemplate();

        $session = $program->sessions()->first();
        $session->update([
            'title' => 'Topik Diubah: Pengenalan Iman',
            'session_at' => now()->addWeek(),
            'location' => 'Ruang Serbaguna',
            'official_id' => $official->id,
        ]);

        $session->refresh();
        $this->assertSame('Topik Diubah: Pengenalan Iman', $session->title);
        $this->assertNotNull($session->session_at);
        $this->assertSame($official->id, $session->official_id);

        // tambah sesi manual (tambah/kurang sesi diperbolehkan)
        $program->sessions()->create([
            'church_id' => $church->id,
            'title' => 'Sesi Tambahan: Evaluasi',
            'session_at' => now()->addWeeks(2),
        ]);
        $this->assertSame(13, $program->sessions()->count());

        // template sumber tidak berubah
        $this->assertSame(12, $template->sessions()->count());
        $this->assertSame('Pengenalan Iman Kristen & Alkitab', $template->sessions()->orderBy('session_number')->first()->topic);
    }

    // ---- AC-LC-20: program tanpa template → sesi manual ----

    public function test_guidance_program_tanpa_template_sesi_manual(): void
    {
        $church = $this->makeChurch();
        $admin = $this->makeUser($church, 'church_admin');
        $this->actingAs($admin);

        $program = GuidanceProgram::create([
            'church_id' => $church->id,
            'type' => 'pra_sidi',
            'title' => 'Program Manual',
            'status' => 'draft',
        ]);

        $this->assertNull($program->template_id);
        $this->assertSame(0, $program->instantiateFromTemplate(), 'tanpa template → tidak ada sesi otomatis');
        $this->assertSame(0, $program->sessions()->count());

        // buat sesi manual satu per satu
        $program->sessions()->create([
            'church_id' => $church->id,
            'title' => 'Pertemuan 1: Iman Kristen',
            'session_at' => now(),
        ]);
        $this->assertSame(1, $program->sessions()->count());
    }

    // ---- AC-LC-03: restore-or-create peserta ----

    public function test_guidance_sesi_peserta_restore_or_create(): void
    {
        $church = $this->makeChurch();
        $admin = $this->makeUser($church, 'church_admin');
        $member = $this->makeMember($church);
        $this->actingAs($admin);

        $program = GuidanceProgram::create([
            'church_id' => $church->id,
            'type' => 'pra_sidi',
            'title' => 'Katakisasi 2026-3',
            'status' => 'berjalan',
        ]);
        $session = $program->sessions()->create([
            'church_id' => $church->id,
            'title' => 'Pertemuan 1',
            'session_at' => now(),
        ]);

        // create baru
        $pivot = GuidanceSessionMember::checkInOrRestore($session->id, $member->id, true);
        $this->assertNotNull($pivot);
        $this->assertTrue($pivot->attended);
        $this->assertSame($church->id, $pivot->church_id);

        // duplikat aktif → dilewati (null), tidak mengubah apa pun
        $dup = GuidanceSessionMember::checkInOrRestore($session->id, $member->id, false);
        $this->assertNull($dup);
        $this->assertSame(1, GuidanceSessionMember::where('session_id', $session->id)->count());
        $this->assertTrue($pivot->fresh()->attended, 'duplikat aktif tidak boleh mengubah attended.');

        // soft delete → re-add → restore
        $pivot->delete();
        $this->assertSame(0, GuidanceSessionMember::where('session_id', $session->id)->count());
        $this->assertSame(1, GuidanceSessionMember::withTrashed()->where('session_id', $session->id)->count());

        $restored = GuidanceSessionMember::checkInOrRestore($session->id, $member->id, true);
        $this->assertNotNull($restored);
        $this->assertSame($pivot->id, $restored->id, 'record soft-deleted harus di-restore, bukan create baru.');
        $this->assertFalse($restored->trashed());
        $this->assertTrue($restored->attended);
    }

    // ---- AC-LC-09: cross-church ditolak 403 ----

    public function test_guidance_cross_church_ditolak(): void
    {
        $churchA = $this->makeChurch();
        $churchB = $this->makeChurch();
        $adminA = $this->makeUser($churchA, 'church_admin');
        // program gereja B dibuat TANPA auth agar church_id benar-benar B
        $programB = $this->makeProgram($churchB);
        $this->actingAs($adminA);

        $this->expectException(HttpException::class);
        GuidanceSession::create([
            'church_id' => $churchA->id,
            'program_id' => $programB->id,
            'title' => 'Sesi Silang',
            'session_at' => now(),
        ]);
    }

    public function test_guidance_program_template_cross_church_ditolak(): void
    {
        $churchA = $this->makeChurch();
        $churchB = $this->makeChurch();
        $adminA = $this->makeUser($churchA, 'church_admin');
        $this->actingAs($adminA);

        // template B diambil tanpa global scope (scope church admin A menyembunyikan B)
        $templateB = GuidanceTemplate::withoutGlobalScopes()
            ->where('church_id', $churchB->id)
            ->where('type', 'pra_sidi')
            ->firstOrFail();

        $this->expectException(HttpException::class);
        GuidanceProgram::create([
            'church_id' => $churchA->id,
            'type' => 'pra_sidi',
            'title' => 'Program dengan template B',
            'status' => 'draft',
            'template_id' => $templateB->id,
        ]);
    }

    // ---- AC-LC-10: super_admin mengikuti gereja induk ----

    public function test_guidance_super_admin_mengikuti_church_induk(): void
    {
        $churchB = $this->makeChurch();
        $superAdmin = $this->makeUser($churchB, 'super_admin');
        $this->actingAs($superAdmin);

        $programB = $this->makeProgram($churchB);

        // super_admin membuat sesi utk program gereja B → church_id = gereja induk (B)
        $session = GuidanceSession::create([
            'program_id' => $programB->id,
            'title' => 'Sesi 1',
            'session_at' => now(),
        ]);
        $this->assertSame($churchB->id, $session->church_id);

        // super_admin menambah peserta pada sesi gereja B → pivot church_id = B
        $memberB = $this->makeMember($churchB);
        $pivot = GuidanceSessionMember::checkInOrRestore($session->id, $memberB->id, true);
        $this->assertNotNull($pivot);
        $this->assertSame($churchB->id, $pivot->church_id);
    }

    // ---- AC-LC-11: scope tenant terisolasi ----

    public function test_guidance_scope_tenant_terisolasi(): void
    {
        $churchA = $this->makeChurch();
        $churchB = $this->makeChurch();
        $adminA = $this->makeUser($churchA, 'church_admin');

        $this->makeProgram($churchA);
        $this->makeProgram($churchB);

        $this->actingAs($adminA);

        $this->assertSame(1, GuidanceProgram::count());
        $this->assertSame($churchA->id, GuidanceProgram::first()->church_id);
    }

    // ---- AC-LC-12: finance_admin & jemaat_admin ditolak ----

    public function test_guidance_finance_admin_ditolak(): void
    {
        $church = $this->makeChurch();
        $finance = $this->makeUser($church, 'finance_admin');

        $this->assertFalse(Gate::forUser($finance)->allows('viewAny', GuidanceProgram::class));
        $this->assertFalse(Gate::forUser($finance)->allows('create', GuidanceProgram::class));
        $this->assertFalse(Gate::forUser($finance)->allows('viewAny', GuidanceTemplate::class));
        $this->assertFalse(Gate::forUser($finance)->allows('viewAny', GuidanceSession::class));
        $this->assertFalse(Gate::forUser($finance)->allows('viewAny', GuidanceSessionMember::class));
    }

    public function test_guidance_jemaat_admin_ditolak(): void
    {
        $church = $this->makeChurch();
        $jemaat = $this->makeUser($church, 'jemaat_admin');

        $this->assertFalse(Gate::forUser($jemaat)->allows('viewAny', GuidanceProgram::class));
        $this->assertFalse(Gate::forUser($jemaat)->allows('create', GuidanceProgram::class));
    }

    // ---- AC-LC-14: warta_editor & report_viewer read-only ----

    public function test_guidance_warta_editor_read_only(): void
    {
        $church = $this->makeChurch();
        $warta = $this->makeUser($church, 'warta_editor');

        $program = GuidanceProgram::create([
            'church_id' => $church->id,
            'type' => 'pra_sidi',
            'title' => 'Program Read-Only',
            'status' => 'draft',
        ]);

        $this->assertTrue(Gate::forUser($warta)->allows('viewAny', GuidanceProgram::class));
        $this->assertTrue(Gate::forUser($warta)->allows('viewAny', GuidanceTemplate::class));
        $this->assertFalse(Gate::forUser($warta)->allows('create', GuidanceProgram::class));
        $this->assertFalse(Gate::forUser($warta)->allows('update', $program));
        $this->assertFalse(Gate::forUser($warta)->allows('delete', $program));
    }

    public function test_guidance_report_viewer_read_only(): void
    {
        $church = $this->makeChurch();
        $viewer = $this->makeUser($church, 'report_viewer');

        $this->assertTrue(Gate::forUser($viewer)->allows('viewAny', GuidanceTemplate::class));
        $this->assertFalse(Gate::forUser($viewer)->allows('create', GuidanceProgram::class));
    }

    // ---- AC-LC-13: church_admin dapat menulis ----

    public function test_guidance_church_admin_dapat_menulis(): void
    {
        $church = $this->makeChurch();
        $admin = $this->makeUser($church, 'church_admin');

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', GuidanceProgram::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', GuidanceProgram::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', GuidanceTemplate::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', GuidanceSession::class));
    }

    // ---- AC-LC-15: audit tercatat ----

    public function test_guidance_audit_tercatat(): void
    {
        $church = $this->makeChurch();
        $admin = $this->makeUser($church, 'church_admin');
        $this->actingAs($admin);

        $program = GuidanceProgram::create([
            'church_id' => $church->id,
            'type' => 'pra_sidi',
            'title' => 'Program Audit',
            'status' => 'draft',
        ]);

        $audit = AuditLog::where('auditable_type', GuidanceProgram::class)
            ->where('auditable_id', $program->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame($church->id, $audit->church_id);
        $this->assertSame('Program Audit', $audit->new_values['title'] ?? null);
    }

    // ---- AC-LC-16: soft delete & restore ----

    public function test_guidance_soft_delete_dan_restore(): void
    {
        $church = $this->makeChurch();
        $admin = $this->makeUser($church, 'church_admin');
        $this->actingAs($admin);

        $program = GuidanceProgram::create([
            'church_id' => $church->id,
            'type' => 'pra_sidi',
            'title' => 'Program Soft Delete',
            'status' => 'draft',
        ]);

        $program->delete();

        // tidak muncul di list default
        $this->assertSame(0, GuidanceProgram::count());
        // masih ada di DB (withTrashed)
        $this->assertSame(1, GuidanceProgram::withTrashed()->count());
        $this->assertTrue(GuidanceProgram::withTrashed()->first()->trashed());

        // restore mengembalikan tampilan
        $program->restore();
        $this->assertSame(1, GuidanceProgram::count());
        $this->assertFalse(GuidanceProgram::first()->trashed());
    }

    // ---- AC-LC-02: model memakai trait BelongsToChurch (query ter-scope) ----

    public function test_guidance_model_terisolasi_church(): void
    {
        $churchA = $this->makeChurch();
        $churchB = $this->makeChurch();
        $adminA = $this->makeUser($churchA, 'church_admin');
        $this->actingAs($adminA);

        // global scope: admin A hanya melihat template gereja A (2 template default)
        $this->assertSame(2, GuidanceTemplate::count());
        $this->assertSame(0, GuidanceTemplate::where('church_id', $churchB->id)->count());
    }

    // ---- AC-LC-03 via jalur create UI (fix review Vera) ----
    // CreateAction di ParticipantsRelationManager kini memakai
    // GuidanceSessionMember::checkInOrRestore() sehingga re-add member yang
    // soft-deleted di sesi sama TIDAK melanggar UNIQUE(session_id, member_id).

    public function test_guidance_peserta_ui_create_path_restore_or_create(): void
    {
        $church = $this->makeChurch();
        $admin = $this->makeUser($church, 'church_admin');
        $this->actingAs($admin);

        $member = Member::factory()->create(['church_id' => $church->id]);
        $program = $this->makeProgram($church);
        $session = $program->sessions()->create([
            'church_id' => $church->id,
            'title' => 'Pertemuan 1',
            'session_at' => now(),
        ]);

        // create pertama lewat jalur UI (data form: member_id + attended)
        $first = GuidanceSessionMember::checkInOrRestore(
            (int) $session->id,
            (int) $member->id,
            true,
            null,
        );
        $this->assertNotNull($first);

        // member dihapus dari sesi (soft delete)
        $first->delete();
        $this->assertSame(0, GuidanceSessionMember::where('session_id', $session->id)->count());

        // create ulang lewat jalur UI -> restore, BUKAN create baru
        // (tanpa fix: create baru melanggar UNIQUE(session_id, member_id) -> 500)
        $second = GuidanceSessionMember::checkInOrRestore(
            (int) $session->id,
            (int) $member->id,
            true,
            'hadir',
        );
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id, 'harus restore record lama, bukan create baru.');
        $this->assertSame(1, GuidanceSessionMember::withTrashed()->where('session_id', $session->id)->count());
        $this->assertSame('hadir', $second->notes);
    }
}

