<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Clusters\Events\Pages\KalenderIbadahPage;
use App\Filament\Clusters\Events\Resources\RecurringSchedule\Pages\ListRecurringSchedules;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\RecurringSchedule;
use App\Models\User;
use App\Services\RecurringEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RecurringEventTest extends TestCase
{
    use RefreshDatabase;

    private Church $churchA;

    private Church $churchB;

    private User $adminA;

    private User $adminB;

    private User $superAdmin;

    private EventCategory $categoryA;

    private EventCategory $categoryB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->churchA = Church::factory()->create(['name' => 'Gereja A']);
        $this->churchB = Church::factory()->create(['name' => 'Gereja B']);

        $this->adminA = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'church_admin',
        ]);

        $this->adminB = User::factory()->create([
            'church_id' => $this->churchB->id,
            'role' => 'church_admin',
        ]);

        $this->superAdmin = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'super_admin',
        ]);

        $this->categoryA = EventCategory::factory()->create([
            'church_id' => $this->churchA->id,
            'name' => 'Ibadah Raya',
        ]);

        $this->categoryB = EventCategory::factory()->create([
            'church_id' => $this->churchB->id,
            'name' => 'Ibadah Pemuda',
        ]);
    }

    public function test_church_admin_dapat_membuat_jadwal_berulang_mingguan(): void
    {
        $this->actingAs($this->adminA);

        $schedule = RecurringSchedule::create([
            'category_id' => $this->categoryA->id,
            'title' => 'Ibadah Minggu Pagi',
            'location' => 'Ruang Utama',
            'frequency' => 'weekly',
            'interval' => 1,
            'days_of_week' => [0], // Minggu
            'start_date' => '2026-09-06',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'end_type' => 'count',
            'repeat_count' => 4,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('recurring_schedules', [
            'id' => $schedule->id,
            'church_id' => $this->churchA->id,
            'title' => 'Ibadah Minggu Pagi',
            'frequency' => 'weekly',
            'interval' => 1,
        ]);
    }

    public function test_generasi_event_mingguan_berhasil(): void
    {
        $schedule = RecurringSchedule::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $this->categoryA->id,
            'title' => 'Ibadah Minggu 08:00',
            'frequency' => 'weekly',
            'interval' => 1,
            'days_of_week' => [0], // Minggu
            'start_date' => '2026-09-06',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'end_type' => 'count',
            'repeat_count' => 4,
            'is_active' => true,
        ]);

        $created = $schedule->generateEvents();

        $this->assertCount(4, $created);
        $this->assertEquals(4, Event::where('recurring_schedule_id', $schedule->id)->count());

        $events = Event::where('recurring_schedule_id', $schedule->id)
            ->orderBy('start_datetime')
            ->get();

        $expectedDates = [
            '2026-09-06 08:00:00',
            '2026-09-13 08:00:00',
            '2026-09-20 08:00:00',
            '2026-09-27 08:00:00',
        ];

        foreach ($events as $idx => $event) {
            $this->assertEquals($expectedDates[$idx], $event->start_datetime->format('Y-m-d H:i:s'));
            $this->assertEquals('2026-09-'.sprintf('%02d', 6 + ($idx * 7)).' 10:00:00', $event->end_datetime->format('Y-m-d H:i:s'));
            $this->assertEquals($this->churchA->id, $event->church_id);
            $this->assertEquals($this->categoryA->id, $event->category_id);
            $this->assertEquals($schedule->id, $event->recurring_schedule_id);
            $this->assertEquals('Ibadah Minggu 08:00', $event->title);
        }

        $schedule->refresh();
        $this->assertNotNull($schedule->last_generated_at);
    }

    public function test_generasi_event_harian_berhasil(): void
    {
        $schedule = RecurringSchedule::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $this->categoryA->id,
            'title' => 'Doa Pagi',
            'frequency' => 'daily',
            'interval' => 2, // setiap 2 hari sekali
            'start_date' => '2026-09-01',
            'start_time' => '05:00:00',
            'end_time' => '06:00:00',
            'end_type' => 'count',
            'repeat_count' => 3,
            'is_active' => true,
        ]);

        $created = $schedule->generateEvents();

        $this->assertCount(3, $created);

        $events = Event::where('recurring_schedule_id', $schedule->id)
            ->orderBy('start_datetime')
            ->get();

        $this->assertEquals('2026-09-01 05:00:00', $events[0]->start_datetime->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-09-03 05:00:00', $events[1]->start_datetime->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-09-05 05:00:00', $events[2]->start_datetime->format('Y-m-d H:i:s'));
    }

    public function test_generasi_event_bulanan_berhasil(): void
    {
        $schedule = RecurringSchedule::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $this->categoryA->id,
            'title' => 'Rapat Majelis Bulanan',
            'frequency' => 'monthly',
            'interval' => 1,
            'start_date' => '2026-09-10',
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
            'end_type' => 'count',
            'repeat_count' => 3,
            'is_active' => true,
        ]);

        $created = $schedule->generateEvents();

        $this->assertCount(3, $created);

        $events = Event::where('recurring_schedule_id', $schedule->id)
            ->orderBy('start_datetime')
            ->get();

        $this->assertEquals('2026-09-10 19:00:00', $events[0]->start_datetime->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-10-10 19:00:00', $events[1]->start_datetime->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-10 19:00:00', $events[2]->start_datetime->format('Y-m-d H:i:s'));
    }

    public function test_generasi_event_idempoten_tidak_menduplikasi_event_yang_sudah_ada(): void
    {
        $schedule = RecurringSchedule::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $this->categoryA->id,
            'frequency' => 'weekly',
            'interval' => 1,
            'days_of_week' => [0],
            'start_date' => '2026-09-06',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'end_type' => 'count',
            'repeat_count' => 4,
            'is_active' => true,
        ]);

        $firstRun = $schedule->generateEvents();
        $this->assertCount(4, $firstRun);
        $this->assertEquals(4, Event::where('recurring_schedule_id', $schedule->id)->count());

        // Jalankan generasi kedua kali
        $secondRun = $schedule->generateEvents();
        $this->assertCount(0, $secondRun);
        $this->assertEquals(4, Event::where('recurring_schedule_id', $schedule->id)->count());
    }

    public function test_generasi_event_menghormati_until_date(): void
    {
        $schedule = RecurringSchedule::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $this->categoryA->id,
            'frequency' => 'weekly',
            'interval' => 1,
            'days_of_week' => [0], // Minggu
            'start_date' => '2026-09-06',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'end_type' => 'until_date',
            'until_date' => '2026-09-20', // Hanya sampai tgl 20 (3 kemunculan: 6, 13, 20)
            'is_active' => true,
        ]);

        $created = $schedule->generateEvents();

        $this->assertCount(3, $created);
        $last = $created->last();
        $this->assertEquals('2026-09-20 08:00:00', $last->start_datetime->format('Y-m-d H:i:s'));
    }

    public function test_isolasi_tenant_recurring_schedule_hanya_melihat_gereja_sendiri(): void
    {
        RecurringSchedule::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $this->categoryA->id,
            'title' => 'Jadwal Gereja A',
        ]);

        RecurringSchedule::factory()->create([
            'church_id' => $this->churchB->id,
            'category_id' => $this->categoryB->id,
            'title' => 'Jadwal Gereja B',
        ]);

        $this->actingAs($this->adminA);
        $schedulesA = RecurringSchedule::all();
        $this->assertCount(1, $schedulesA);
        $this->assertEquals('Jadwal Gereja A', $schedulesA->first()->title);

        $this->actingAs($this->adminB);
        $schedulesB = RecurringSchedule::all();
        $this->assertCount(1, $schedulesB);
        $this->assertEquals('Jadwal Gereja B', $schedulesB->first()->title);
    }

    public function test_non_super_admin_church_id_dipaksa_ke_gereja_sendiri(): void
    {
        $this->actingAs($this->adminA);

        $schedule = RecurringSchedule::create([
            'church_id' => $this->churchB->id, // coba inject churchB
            'category_id' => $this->categoryA->id,
            'title' => 'Jadwal Tes Force Church',
            'frequency' => 'daily',
            'interval' => 1,
            'start_date' => '2026-09-01',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'end_type' => 'count',
            'repeat_count' => 1,
            'is_active' => true,
        ]);

        $this->assertEquals($this->churchA->id, $schedule->church_id);
    }

    public function test_cross_church_kategori_ditolak_403(): void
    {
        $this->actingAs($this->adminA);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Data referensi 'category_id' milik gereja lain tidak diizinkan.");

        RecurringSchedule::create([
            'category_id' => $this->categoryB->id, // kategori milik Gereja B!
            'title' => 'Jadwal Cross Church',
            'frequency' => 'weekly',
            'interval' => 1,
            'start_date' => '2026-09-01',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'end_type' => 'count',
            'repeat_count' => 1,
            'is_active' => true,
        ]);
    }

    public function test_super_admin_dapat_membuat_dan_melihat_jadwal_lintas_gereja(): void
    {
        $this->actingAs($this->superAdmin);

        $scheduleB = RecurringSchedule::create([
            'church_id' => $this->churchB->id,
            'category_id' => $this->categoryB->id,
            'title' => 'Jadwal Super Admin untuk Gereja B',
            'frequency' => 'weekly',
            'interval' => 1,
            'start_date' => '2026-09-06',
            'start_time' => '17:00:00',
            'end_time' => '19:00:00',
            'end_type' => 'count',
            'repeat_count' => 2,
            'is_active' => true,
        ]);

        $this->assertEquals($this->churchB->id, $scheduleB->church_id);
        $this->assertCount(1, RecurringSchedule::all());
    }

    public function test_validasi_input_jadwal_berulang(): void
    {
        $service = app(RecurringEventService::class);

        // Kasus 1: data kosong
        $this->expectException(ValidationException::class);
        $service->validateSchedule([]);
    }

    public function test_soft_delete_dan_restore_recurring_schedule(): void
    {
        $schedule = RecurringSchedule::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $this->categoryA->id,
        ]);

        $schedule->delete();
        $this->assertSoftDeleted('recurring_schedules', ['id' => $schedule->id]);

        $schedule->restore();
        $this->assertNotSoftDeleted('recurring_schedules', ['id' => $schedule->id]);
    }

    public function test_artisan_command_events_generate_recurring(): void
    {
        RecurringSchedule::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $this->categoryA->id,
            'title' => 'Ibadah Command Test',
            'frequency' => 'weekly',
            'interval' => 1,
            'days_of_week' => [0],
            'start_date' => '2026-09-06',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'end_type' => 'count',
            'repeat_count' => 3,
            'is_active' => true,
        ]);

        $this->artisan('events:generate-recurring', [
            '--church' => $this->churchA->id,
            '--days' => 30,
        ])
            ->expectsOutputToContain('Ibadah Command Test')
            ->assertSuccessful();

        $this->assertEquals(3, Event::where('church_id', $this->churchA->id)->count());
    }

    public function test_halaman_kalender_ibadah_render_berhasil_dan_terisolasi_per_gereja(): void
    {
        Event::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $this->categoryA->id,
            'title' => 'Ibadah Raya Gereja A',
            'start_datetime' => '2026-09-13 08:00:00',
            'end_datetime' => '2026-09-13 10:00:00',
        ]);

        Event::factory()->create([
            'church_id' => $this->churchB->id,
            'category_id' => $this->categoryB->id,
            'title' => 'Ibadah Pemuda Gereja B',
            'start_datetime' => '2026-09-13 17:00:00',
            'end_datetime' => '2026-09-13 19:00:00',
        ]);

        $this->actingAs($this->adminA);

        Livewire::test(KalenderIbadahPage::class)
            ->set('month', '2026-09')
            ->assertSuccessful()
            ->assertSee('Ibadah Raya Gereja A')
            ->assertDontSee('Ibadah Pemuda Gereja B');
    }

    public function test_halaman_kalender_filter_kategori(): void
    {
        $cat2 = EventCategory::factory()->create([
            'church_id' => $this->churchA->id,
            'name' => 'Doa Syafaat',
        ]);

        Event::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $this->categoryA->id,
            'title' => 'Ibadah Raya Utama',
            'start_datetime' => '2026-09-13 08:00:00',
            'end_datetime' => '2026-09-13 10:00:00',
        ]);

        Event::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $cat2->id,
            'title' => 'Doa Malam Bersama',
            'start_datetime' => '2026-09-13 19:00:00',
            'end_datetime' => '2026-09-13 20:30:00',
        ]);

        $this->actingAs($this->adminA);

        Livewire::test(KalenderIbadahPage::class)
            ->set('month', '2026-09')
            ->set('categoryId', $cat2->id)
            ->assertSuccessful()
            ->assertSee('Doa Malam Bersama')
            ->assertDontSee('Ibadah Raya Utama');
    }

    public function test_filament_list_recurring_schedules_render_dan_action_generate(): void
    {
        $schedule = RecurringSchedule::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $this->categoryA->id,
            'title' => 'Jadwal Filament Test',
            'frequency' => 'weekly',
            'interval' => 1,
            'days_of_week' => [0],
            'start_date' => '2026-09-06',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'end_type' => 'count',
            'repeat_count' => 2,
            'is_active' => true,
        ]);

        $this->actingAs($this->adminA);

        Livewire::test(ListRecurringSchedules::class)
            ->assertSuccessful()
            ->assertSee('Jadwal Filament Test')
            ->callTableAction('generate', $schedule)
            ->assertHasNoErrors();

        $this->assertEquals(2, Event::where('recurring_schedule_id', $schedule->id)->count());
    }
}
