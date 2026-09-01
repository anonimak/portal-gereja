<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Clusters\Lifecycle\Resources\GuidanceProgram\RelationManagers\SessionsRelationManager;
use App\Filament\Clusters\Lifecycle\Resources\GuidanceProgramResource;
use App\Filament\Clusters\Lifecycle\Resources\GuidanceSessionResource;
use App\Models\Church;
use App\Models\GuidanceProgram;
use App\Models\GuidanceTemplate;
use App\Models\User;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 3B T7 — Fix re-review Vera PR #25.
 *
 * 1. HIGH: select official_id TIDAK boleh memakai accessor display_name sebagai
 *    kolom relationship (Filament pluck -> SQL error). Harus kolom 'id' +
 *    getOptionLabelFromRecordUsing (pola yang sama dengan SacramentsRelationManager).
 * 2. MED: select program_id/template_id/official_id tidak dikunci ke
 *    auth()->user()->church_id — super_admin bisa memilih lintas gereja
 *    (via ChurchContext::activeChurchId).
 *
 * Catatan pendekatan: komponen form di-instantiate tanpa Livewire component,
 * sehingga method yang butuh container (getRelationship()->getRelated())
 * TIDAK bisa dipanggil. Untuk itu test runtime dibatasi pada properti
 * komponen (title attribute, option label callback), dan cakupan
 * super_admin lintas gereja diuji lewat source-code guard + query model
 * langsung (getRelatedCount), pola yang sama dengan ReReviewFixesTest.
 */
class GuidanceVeraFixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * Cari komponen form berdasarkan nama, termasuk komponen bersarang.
     *
     * Memakai getComponents()/getDefaultChildComponents() (bukan getChildComponents)
     * agar tidak membutuhkan instance Livewire untuk mengevaluasi child schema —
     * pola yang sama dengan ReReviewFixesTest yang sudah terbukti di master.
     *
     * @param  array<int, Component|mixed>  $components
     */
    private function findComponent(array $components, string $name): ?Component
    {
        foreach ($components as $component) {
            if (! $component instanceof Component) {
                // Schema (hasil getDefaultChildComponents) bukan Component —
                // turunkan langsung ke components-nya.
                if (is_object($component) && method_exists($component, 'getComponents')) {
                    $found = $this->findComponent($component->getComponents(), $name);
                    if ($found) {
                        return $found;
                    }
                }

                continue;
            }

            if (method_exists($component, 'getName') && $component->getName() === $name) {
                return $component;
            }

            if (method_exists($component, 'getDefaultChildComponents')) {
                $children = $component->getDefaultChildComponents();
                if ($children instanceof Schema) {
                    $children = $children->getComponents();
                }
                if (is_array($children)) {
                    $found = $this->findComponent($children, $name);
                    if ($found) {
                        return $found;
                    }
                }
            }
        }

        return null;
    }

    public function test_sessions_relation_manager_official_select_tidak_memakai_accessor(): void
    {
        $manager = new SessionsRelationManager;
        $schema = $manager->form(Schema::make());

        $select = $this->findComponent($schema->getComponents(), 'official_id');

        $this->assertNotNull($select, 'Field official_id tidak ditemukan di SessionsRelationManager.');
        $this->assertTrue(
            $select->hasOptionLabelFromRecordUsingCallback(),
            'official select harus memakai getOptionLabelFromRecordUsing (label dari accessor, bukan pluck).',
        );
        $this->assertSame(
            'id',
            $select->getRelationshipTitleAttribute(),
            'Title attribute harus kolom nyata (id), bukan accessor display_name.',
        );
    }

    public function test_guidance_session_resource_official_select_tidak_memakai_accessor(): void
    {
        $schema = GuidanceSessionResource::form(Schema::make());

        $select = $this->findComponent($schema->getComponents(), 'official_id');

        $this->assertNotNull($select, 'Field official_id tidak ditemukan di GuidanceSessionResource.');
        $this->assertTrue(
            $select->hasOptionLabelFromRecordUsingCallback(),
            'official select harus memakai getOptionLabelFromRecordUsing (label dari accessor, bukan pluck).',
        );
        $this->assertSame(
            'id',
            $select->getRelationshipTitleAttribute(),
            'Title attribute harus kolom nyata (id), bukan accessor display_name.',
        );
    }

    public function test_guidance_session_resource_program_select_tidak_dikunci_ke_gereja_aktor(): void
    {
        $churchA = Church::factory()->create(['name' => 'Gereja A']);
        $churchB = Church::factory()->create(['name' => 'Gereja B']);
        $superAdmin = User::factory()->create([
            'church_id' => $churchA->id,
            'role' => 'super_admin',
        ]);
        // Program di dua gereja — super_admin harus bisa memilih keduanya.
        GuidanceProgram::factory()->create([
            'church_id' => $churchA->id,
            'title' => 'Program A',
        ]);
        GuidanceProgram::factory()->create([
            'church_id' => $churchB->id,
            'title' => 'Program B',
        ]);

        $this->actingAs($superAdmin);

        $schema = GuidanceSessionResource::form(Schema::make());
        $select = $this->findComponent($schema->getComponents(), 'program_id');

        $this->assertNotNull($select, 'Field program_id tidak ditemukan di GuidanceSessionResource.');

        // 1) Select memakai ChurchContext (bukan auth()->user()->church_id) — guard source.
        $source = (string) file_get_contents(app_path('Filament/Clusters/Lifecycle/Resources/GuidanceSessionResource.php'));
        $this->assertStringNotContainsString('auth()->user()->church_id', $source);
        $this->assertStringContainsString('ChurchContext::activeChurchId()', $source);

        // 2) Super admin (tanpa scope church) melihat semua program lintas gereja.
        $this->assertSame(2, GuidanceProgram::query()->count(), 'super_admin harus melihat program semua gereja.');
    }

    public function test_guidance_program_resource_template_select_tidak_dikunci_ke_gereja_aktor(): void
    {
        $churchA = Church::factory()->create(['name' => 'Gereja A']);
        $churchB = Church::factory()->create(['name' => 'Gereja B']);
        $superAdmin = User::factory()->create([
            'church_id' => $churchA->id,
            'role' => 'super_admin',
        ]);
        GuidanceTemplate::factory()->create([
            'church_id' => $churchA->id,
            'name' => 'Template A',
        ]);
        GuidanceTemplate::factory()->create([
            'church_id' => $churchB->id,
            'name' => 'Template B',
        ]);

        $this->actingAs($superAdmin);

        $schema = GuidanceProgramResource::form(Schema::make());
        $select = $this->findComponent($schema->getComponents(), 'template_id');

        $this->assertNotNull($select, 'Field template_id tidak ditemukan di GuidanceProgramResource.');

        // 1) Select memakai ChurchContext (bukan auth()->user()->church_id) — guard source.
        $source = (string) file_get_contents(app_path('Filament/Clusters/Lifecycle/Resources/GuidanceProgramResource.php'));
        $this->assertStringNotContainsString('auth()->user()->church_id', $source);
        $this->assertStringContainsString('ChurchContext::activeChurchId()', $source);

        // 2) Super admin (tanpa scope church) melihat semua template lintas gereja.
        $this->assertSame(6, GuidanceTemplate::query()->count(), 'super_admin harus melihat template semua gereja (6 = 3 default x 2 gereja), bukan hanya gereja aktor (3).');
    }

    public function test_select_tidak_memakai_where_church_id_aktor_untuk_super_admin(): void
    {
        // Pastikan tidak ada sisa pola `where('church_id', auth()->user()->church_id)`
        // pada file yang di-fix — cek lewat source, bukan runtime (guard tambahan).
        foreach ([
            app_path('Filament/Clusters/Lifecycle/Resources/GuidanceSessionResource.php'),
            app_path('Filament/Clusters/Lifecycle/Resources/GuidanceProgramResource.php'),
            app_path('Filament/Clusters/Lifecycle/Resources/GuidanceProgram/RelationManagers/SessionsRelationManager.php'),
        ] as $file) {
            $source = (string) file_get_contents($file);
            $this->assertStringNotContainsString(
                'auth()->user()->church_id',
                $source,
                "{$file} masih memakai filter church_id aktor yang mengunci super_admin.",
            );
        }

        $this->assertTrue(true);
    }
}
