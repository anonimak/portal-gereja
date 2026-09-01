<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Clusters\Lifecycle\Resources\GuidanceProgram\Pages\CreateGuidanceProgram;
use App\Filament\Clusters\Lifecycle\Resources\GuidanceProgramResource;
use App\Models\Church;
use App\Models\GuidanceProgram;
use App\Models\GuidanceTemplate;
use App\Models\Member;
use App\Models\Official;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Re-review Vera PR #25 (T7 Bimbingan Pra-Sidi):
 * - HIGH-1: SessionsRelationManager & GuidanceSessionResource pakai
 *   relationship('official','display_name') — display_name adalah accessor,
 *   Filament pluck → SQL error saat render form. Fix: 'id' +
 *   getOptionLabelFromRecordUsing (pola SacramentsRelationManager).
 * - MED-3: CreateGuidanceProgram harus auto-instantiate sesi 1..N dari template
 *   saat create (AC-LC-18 penuh di UI), bukan hanya lewat model manual.
 */
class GuidanceVeraReviewFixTest extends TestCase
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

    private function makeUser(Church $church, string $role = 'church_admin'): User
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

    private function makeOfficial(Church $church): Official
    {
        $member = Member::factory()->create(['church_id' => $church->id]);

        return Official::factory()->create([
            'church_id' => $church->id,
            'member_id' => $member->id,
            'type' => 'majelis_lokal',
        ]);
    }

    // ---- HIGH-1: render halaman edit program (memuat SessionsRelationManager) ----

    public function test_halaman_edit_program_render_dengan_sessions_relation_manager(): void
    {
        $church = $this->makeChurch();
        $admin = $this->makeUser($church);
        $this->actingAs($admin);

        $this->makeOfficial($church);

        $template = GuidanceTemplate::where('church_id', $church->id)
            ->where('type', 'pra_sidi')
            ->firstOrFail();

        $program = GuidanceProgram::create([
            'church_id' => $church->id,
            'type' => 'pra_sidi',
            'title' => 'Katakisasi Test Render',
            'status' => 'draft',
            'template_id' => $template->id,
        ]);
        $program->instantiateFromTemplate();

        // Render halaman edit — RelationManager "Sesi/Pertemuan" ikut dibangun
        // (form select official_id). Jika select pakai accessor display_name
        // sebagai kolom pluck → SQL error → 500. Dengan fix 'id' + label callback → 200.
        $this->get(GuidanceProgramResource::getUrl('edit', ['record' => $program]))
            ->assertStatus(200);
    }

    public function test_sessions_relation_manager_official_select_tidak_pakai_accessor_sebagai_kolom(): void
    {
        $sessionsRm = file_get_contents(
            app_path('Filament/Clusters/Lifecycle/Resources/GuidanceProgram/RelationManagers/SessionsRelationManager.php')
        );
        $sessionResource = file_get_contents(
            app_path('Filament/Clusters/Lifecycle/Resources/GuidanceSessionResource.php')
        );

        foreach ([$sessionsRm, $sessionResource] as $source) {
            $this->assertStringNotContainsString(
                "'display_name',",
                $source,
                'Select official_id TIDAK boleh memakai accessor display_name sebagai kolom pluck.'
            );
            $this->assertStringContainsString(
                'getOptionLabelFromRecordUsing',
                $source,
                'Label official harus via getOptionLabelFromRecordUsing (accessor aman, bukan pluck).'
            );
        }
    }

    // ---- MED-3: CreateGuidanceProgram auto-instantiate sesi dari template ----

    public function test_create_program_page_auto_instantiate_sesi_dari_template(): void
    {
        $church = $this->makeChurch();
        $admin = $this->makeUser($church);
        $this->actingAs($admin);

        $template = GuidanceTemplate::where('church_id', $church->id)
            ->where('type', 'pra_sidi')
            ->firstOrFail();

        $program = GuidanceProgram::create([
            'church_id' => $church->id,
            'type' => 'pra_sidi',
            'title' => 'Katakisasi Auto Instantiate',
            'status' => 'draft',
            'template_id' => $template->id,
        ]);

        // Simulasi jalur UI: Filament memanggil afterCreate() setelah record dibuat.
        $page = new CreateGuidanceProgram;
        $reflection = new \ReflectionClass($page);
        $recordProp = $reflection->getProperty('record');
        $recordProp->setAccessible(true);
        $recordProp->setValue($page, $program);
        $afterCreate = $reflection->getMethod('afterCreate');
        $afterCreate->setAccessible(true);
        $afterCreate->invoke($page);

        $this->assertSame(12, $program->sessions()->count(), 'afterCreate harus auto-instantiate 12 sesi (AC-LC-18 di UI).');

        $titles = $program->sessions()->orderBy('id')->pluck('title')->all();
        $topics = $template->sessions()->orderBy('session_number')->pluck('topic')->all();
        $this->assertSame($topics, $titles, 'sesi mengikuti urutan topik template.');

        // Idempotent: panggil lagi tidak menduplikasi.
        $afterCreate->invoke($page);
        $this->assertSame(12, $program->sessions()->count());
    }
}
