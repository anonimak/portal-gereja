<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Clusters\Events\Resources\Event\EventResource;
use App\Filament\Pages\LaporanRapatPage;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Re-review Vera PR #5 (Fase 2 Task 2):
 * - MED-1: member soft-deleted TIDAK boleh muncul di opsi check-in (tanpa ghost row).
 * - MED-2: accessor total_attendance tidak lagi N+1 — eager-load attendances di
 *   laporan + withCount di tabel + accessor satu query agregat saat relasi belum dimuat.
 */
class AttendanceVeraFixTest extends TestCase
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

    private function makeEvent(Church $church): Event
    {
        return Event::factory()->create(['church_id' => $church->id]);
    }

    private function makeMember(Church $church): Member
    {
        return Member::factory()->create(['church_id' => $church->id]);
    }

    // ---- MED-1: member soft-deleted tidak muncul di query opsi check-in ----

    public function test_member_soft_deleted_tidak_muncul_di_opsi_checkin(): void
    {
        $church = Church::factory()->create();
        $event = $this->makeEvent($church);
        $active = $this->makeMember($church);
        $softDeleted = $this->makeMember($church);
        $softDeleted->delete();

        // Query yang sama dengan Select member pada AttendancesRelationManager
        // (setelah MED-1: tanpa withTrashed, hanya scope church_id).
        $options = Member::query()
            ->where('church_id', $event->church_id)
            ->pluck('id');

        $this->assertTrue($options->contains($active->id), 'member aktif harus muncul di opsi.');
        $this->assertFalse($options->contains($softDeleted->id), 'member soft-deleted TIDAK boleh muncul di opsi check-in (ghost row).');
    }

    public function test_halaman_edit_event_render_dengan_relation_manager(): void
    {
        $church = Church::factory()->create();
        $admin = $this->makeAdmin($church);
        $event = $this->makeEvent($church);
        $member = $this->makeMember($church);

        EventAttendance::factory()->create([
            'church_id' => $church->id,
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'hadir',
        ]);

        $this->actingAs($admin);

        // Render halaman edit Event — RelationManager "Kehadiran" ikut dibangun
        // (schema form member select + tabel). Jika query select rusak (mis. pakai
        // accessor/withTrashed yang salah), halaman akan 500.
        $this->get(EventResource::getUrl('edit', ['record' => $event]))
            ->assertStatus(200);
    }

    // ---- MED-2: eager-load attendances di laporan rapat ----

    public function test_laporan_rapat_events_eager_load_attendances(): void
    {
        $church = Church::factory()->create();
        $admin = $this->makeAdmin($church);
        $event = $this->makeEvent($church);
        $memberA = $this->makeMember($church);
        $memberB = $this->makeMember($church);

        EventAttendance::factory()->create([
            'church_id' => $church->id,
            'event_id' => $event->id,
            'member_id' => $memberA->id,
            'status' => 'hadir',
        ]);
        EventAttendance::factory()->create([
            'church_id' => $church->id,
            'event_id' => $event->id,
            'member_id' => $memberB->id,
            'status' => 'tidak_hadir',
        ]);

        $this->actingAs($admin);

        $page = new LaporanRapatPage;
        $page->data = [
            'period_type' => 'monthly',
            'year' => (int) $event->start_datetime->year,
            'month' => (int) $event->start_datetime->month,
            'quarter' => (int) ceil($event->start_datetime->month / 3),
        ];

        $data = $page->getReportData();

        $reportEvent = $data['events']->first();
        $this->assertNotNull($reportEvent);
        $this->assertTrue(
            $reportEvent->relationLoaded('attendances'),
            'events di laporan harus eager-load attendances (hindari N+1).'
        );
        $this->assertSame(1, $reportEvent->total_attendance, '1 hadir + 1 tidak_hadir → total 1 (AC-T2-10).');
    }

    // ---- MED-2: accessor satu query agregat saat relasi belum dimuat ----

    public function test_accessor_total_attendance_satu_query_saat_tidak_eager_loaded(): void
    {
        $church = Church::factory()->create();
        $event = $this->makeEvent($church);
        $member = $this->makeMember($church);

        EventAttendance::factory()->create([
            'church_id' => $church->id,
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'hadir',
        ]);

        // Segarkan event tanpa relasi termuat — akses accessor harus SATU query
        // agregat (COUNT + SUM CASE), bukan exists+count (2 query).
        $event->refresh();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $total = $event->total_attendance;

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(1, $total);
        $this->assertSame(1, $queries, 'accessor tanpa eager-load harus memakai 1 query agregat, bukan 2.');
    }
}
