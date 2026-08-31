<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Clusters\Demographics\Resources\Members\RelationManagers\SacramentsRelationManager;
use App\Filament\Clusters\Events\Resources\Event\EventResource;
use App\Filament\Pages\LaporanRapatPage;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\Transaction;
use App\Models\User;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Re-review PR #1 — 4 temuan baru Vera:
 *  1. [HIGH] SacramentsRelationManager: official select pluck accessor display_name → SQL error.
 *  2. [MED]  LaporanRapatPage: super_admin tetap ter-scope gereja sendiri.
 *  3. [MED]  member_sacraments & event_rosters: church_id masih nullable.
 *  4. [MED]  EventResource roster: assignee_type dehydrated(false) → petugas tak bisa diedit ulang.
 */
class ReReviewFixesTest extends TestCase
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
     * Memakai getComponents()/getDefaultChildComponents() (bukan getFlatComponents)
     * agar tidak membutuhkan instance Livewire untuk mengevaluasi child schema.
     *
     * @param  array<int, Component|mixed>  $components
     */
    private function findComponent(array $components, string $name): ?Component
    {
        foreach ($components as $component) {
            if (! $component instanceof Component) {
                continue;
            }

            if (method_exists($component, 'getName') && $component->getName() === $name) {
                return $component;
            }

            if (method_exists($component, 'getDefaultChildComponents')) {
                $children = $component->getDefaultChildComponents();
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

    // ---- 1. HIGH: form sakramen tidak error (official select tidak pluck accessor) ----

    public function test_sacrament_official_select_tidak_memakai_accessor_sebagai_kolom_pluck(): void
    {
        $schema = Schema::make();
        $manager = new SacramentsRelationManager;
        $schema = $manager->form($schema);

        $select = $this->findComponent($schema->getComponents(), 'official_id');

        $this->assertNotNull($select, 'Field official_id tidak ditemukan di form sakramen.');
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

    // ---- 2. MED: super_admin di Laporan Rapat melihat semua gereja ----

    public function test_laporan_rapat_super_admin_melihat_semua_gereja(): void
    {
        $churchA = Church::factory()->create(['name' => 'Gereja A']);
        $churchB = Church::factory()->create(['name' => 'Gereja B']);
        $superAdmin = User::factory()->create([
            'church_id' => $churchA->id,
            'role' => 'super_admin',
        ]);

        $this->seedLaporanFixture($churchA);
        $this->seedLaporanFixture($churchB);

        $this->actingAs($superAdmin);

        $page = new LaporanRapatPage;
        $page->data = $this->currentPeriodData();

        $data = $page->getReportData();

        $this->assertSame(2, $data['events']->count(), 'super_admin harus melihat event dari semua gereja.');
        $this->assertSame(200_000, (int) $data['income']->sum('total'), 'super_admin harus melihat pemasukan dari semua gereja.');
        $this->assertSame('Semua Gereja', $data['churchName']);
    }

    public function test_laporan_rapat_church_admin_hanya_melihat_gereja_sendiri(): void
    {
        $churchA = Church::factory()->create(['name' => 'Gereja A']);
        $churchB = Church::factory()->create(['name' => 'Gereja B']);
        $adminA = User::factory()->create([
            'church_id' => $churchA->id,
            'role' => 'church_admin',
        ]);

        $this->seedLaporanFixture($churchA);
        $this->seedLaporanFixture($churchB);

        $this->actingAs($adminA);

        $page = new LaporanRapatPage;
        $page->data = $this->currentPeriodData();

        $data = $page->getReportData();

        $this->assertSame(1, $data['events']->count(), 'church_admin hanya melihat event gereja sendiri.');
        $this->assertSame(100_000, (int) $data['income']->sum('total'), 'church_admin hanya melihat pemasukan gereja sendiri.');
        $this->assertSame('Gereja A', $data['churchName']);
    }

    /**
     * Seed satu event + satu transaksi debit 100.000 untuk sebuah gereja pada bulan berjalan.
     */
    private function seedLaporanFixture(Church $church): void
    {
        $category = EventCategory::factory()->create(['church_id' => $church->id]);
        Event::factory()->create([
            'church_id' => $church->id,
            'category_id' => $category->id,
            // Selalu dalam bulan berjalan (aman dari batas akhir bulan).
            'start_datetime' => now()->startOfMonth()->addDays(1)->setTime(9, 0),
            'end_datetime' => now()->startOfMonth()->addDays(1)->setTime(11, 0),
        ]);

        $fund = Fund::factory()->create(['church_id' => $church->id]);
        $finCategory = FinancialCategory::factory()->create([
            'church_id' => $church->id,
            'type' => 'debit',
        ]);
        Transaction::factory()->create([
            'church_id' => $church->id,
            'fund_id' => $fund->id,
            'category_id' => $finCategory->id,
            'type' => 'debit',
            'amount' => 100_000,
            'transaction_date' => now(),
        ]);
    }

    /**
     * @return array{period_type: string, month: int, quarter: int, year: int}
     */
    private function currentPeriodData(): array
    {
        return [
            'period_type' => 'monthly',
            'month' => (int) now()->month,
            'quarter' => (int) ceil(now()->month / 3),
            'year' => (int) now()->year,
        ];
    }

    // ---- 3. MED: church_id NOT NULL pada member_sacraments & event_rosters ----

    public function test_child_tables_church_id_not_null_setelah_migrasi(): void
    {
        foreach (['member_sacraments', 'event_rosters'] as $table) {
            $columns = DB::select("PRAGMA table_info({$table})");
            $column = collect($columns)->firstWhere('name', 'church_id');

            $this->assertNotNull($column, "Kolom church_id tidak ada di {$table}.");
            $this->assertSame(1, (int) $column->notnull, "church_id di {$table} harus NOT NULL.");
        }
    }

    // ---- 4. MED: EventResource roster bisa diedit ulang (assignee_type terisi saat edit) ----

    public function test_event_roster_repeater_mengisi_assignee_type_saat_edit(): void
    {
        $schema = Schema::make();
        $schema = EventResource::form($schema);

        $repeater = $this->findComponent($schema->getComponents(), 'rosters');
        $this->assertNotNull($repeater, 'Repeater rosters tidak ditemukan di form EventResource.');

        $reflection = new \ReflectionProperty($repeater, 'mutateRelationshipDataBeforeFillUsing');
        $reflection->setAccessible(true);
        $closure = $reflection->getValue($repeater);
        $this->assertNotNull($closure, 'mutateRelationshipDataBeforeFillUsing harus terpasang agar assignee_type terisi saat edit.');

        // Roster ber-member → assignee_type = member → field member tampil saat edit.
        $memberData = $closure(['member_id' => 7, 'official_id' => null, 'role_id' => 3]);
        $this->assertSame('member', $memberData['assignee_type'] ?? null);

        // Roster ber-official → assignee_type = official → field official tampil saat edit.
        $officialData = $closure(['member_id' => null, 'official_id' => 9, 'role_id' => 3]);
        $this->assertSame('official', $officialData['assignee_type'] ?? null);
    }
}
