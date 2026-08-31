<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Fase 2 Task 2 — Modul Kehadiran Ibadah per Anggota (check-in).
 *
 * Menutup AC-T2-01 s/d AC-T2-16 (10 test wajib di spec).
 */
class EventAttendanceTest extends TestCase
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

    // ---- 1. AC-T2-08: create attendance, relasi terisi, checked_in_at/by otomatis ----

    public function test_attendance_create_dan_relasi_terisi(): void
    {
        $church = Church::factory()->create();
        $admin = $this->makeAdmin($church);
        $event = $this->makeEvent($church);
        $member = $this->makeMember($church);

        $this->actingAs($admin);

        $attendance = EventAttendance::create([
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'hadir',
        ]);

        $this->assertNotNull($attendance->id);
        $this->assertSame($church->id, $attendance->church_id);
        $this->assertSame($event->id, $attendance->event->id);
        $this->assertSame($member->id, $attendance->member->id);
        $this->assertSame($admin->id, $attendance->checked_in_by, 'checked_in_by harus terisi user aktor (AC-T2-08).');
        $this->assertNotNull($attendance->checked_in_at, 'checked_in_at harus terisi otomatis now() (AC-T2-08).');
    }

    // ---- 2. AC-T2-01/09: UNIQUE(event_id, member_id) menolak duplikat ----

    public function test_attendance_duplicate_ditolak_unique(): void
    {
        $church = Church::factory()->create();
        $admin = $this->makeAdmin($church);
        $event = $this->makeEvent($church);
        $member = $this->makeMember($church);

        $this->actingAs($admin);

        EventAttendance::create([
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'hadir',
        ]);

        $this->expectException(QueryException::class);

        EventAttendance::create([
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'hadir',
        ]);
    }

    // ---- 3. AC-T2-04: member gereja lain ditolak 403 ----

    public function test_attendance_cross_church_ditolak(): void
    {
        $churchA = Church::factory()->create();
        $churchB = Church::factory()->create();
        $adminA = $this->makeAdmin($churchA);
        $eventA = $this->makeEvent($churchA);
        $memberB = $this->makeMember($churchB); // member gereja lain

        $this->actingAs($adminA);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Data referensi 'member_id' milik gereja lain tidak diizinkan.");

        EventAttendance::create([
            'event_id' => $eventA->id,
            'member_id' => $memberB->id,
            'status' => 'hadir',
        ]);
    }

    // ---- 4. AC-T2-05: super_admin check-in event gereja lain → church_id ikut event ----

    public function test_attendance_super_admin_mengikuti_church_event(): void
    {
        $churchA = Church::factory()->create();
        $churchB = Church::factory()->create();
        $superAdmin = User::factory()->create([
            'church_id' => $churchA->id,
            'role' => 'super_admin',
        ]);
        $eventB = $this->makeEvent($churchB);
        $memberB = $this->makeMember($churchB);

        $this->actingAs($superAdmin);

        $attendance = EventAttendance::create([
            'event_id' => $eventB->id,
            'member_id' => $memberB->id,
            'status' => 'hadir',
        ]);

        $this->assertSame($churchB->id, $attendance->church_id, 'church_id harus mengikuti gereja event, bukan gereja aktor.');
    }

    // ---- 5. AC-T2-06: scope tenant terisolasi per gereja ----

    public function test_attendance_scope_tenant_terisolasi(): void
    {
        $churchA = Church::factory()->create();
        $churchB = Church::factory()->create();
        $adminA = $this->makeAdmin($churchA);

        $eventA = $this->makeEvent($churchA);
        $memberA = $this->makeMember($churchA);
        $eventB = $this->makeEvent($churchB);
        $memberB = $this->makeMember($churchB);

        // seed data tanpa aktor (factory) supaya tidak ter-scope
        EventAttendance::factory()->create(['event_id' => $eventA, 'member_id' => $memberA]);
        EventAttendance::factory()->create(['event_id' => $eventB, 'member_id' => $memberB]);

        $this->actingAs($adminA);

        $visible = EventAttendance::query()->pluck('id')->all();

        $this->assertCount(1, $visible, 'admin gereja A hanya melihat attendance gereja A.');
        $this->assertSame(1, EventAttendance::where('church_id', $churchA->id)->count());
    }

    // ---- 6. AC-T2-10: total_attendance = record hadir, fallback legacy ----

    public function test_attendance_total_fallback_legacy(): void
    {
        $church = Church::factory()->create();
        $event = Event::factory()->create([
            'church_id' => $church->id,
            'attendance_male' => 3,
            'attendance_female' => 2,
        ]);

        // Tanpa record → fallback legacy 3+2 = 5.
        $this->assertSame(5, $event->total_attendance);

        // Dengan record hadir → jumlah record hadir (bukan jumlah field legacy).
        $m1 = $this->makeMember($church);
        $m2 = $this->makeMember($church);
        $m3 = $this->makeMember($church);
        EventAttendance::factory()->create(['event_id' => $event, 'member_id' => $m1, 'status' => 'hadir']);
        EventAttendance::factory()->create(['event_id' => $event, 'member_id' => $m2, 'status' => 'hadir']);
        EventAttendance::factory()->create(['event_id' => $event, 'member_id' => $m3, 'status' => 'tidak_hadir']);

        $this->assertSame(2, $event->total_attendance);
    }

    // ---- 7. AC-T2-15: soft delete & restore ----

    public function test_attendance_soft_delete_dan_restore(): void
    {
        $church = Church::factory()->create();
        $event = $this->makeEvent($church);
        $member = $this->makeMember($church);
        $attendance = EventAttendance::factory()->create([
            'event_id' => $event,
            'member_id' => $member,
            'status' => 'hadir',
        ]);

        $attendance->delete();

        $this->assertSame(0, EventAttendance::count(), 'record terhapus tidak muncul di query default.');
        $this->assertSame(1, EventAttendance::withTrashed()->count(), 'data tetap ada di DB (withTrashed).');
        $this->assertNotNull(EventAttendance::withTrashed()->find($attendance->id));

        $attendance->restore();

        $this->assertSame(1, EventAttendance::count(), 'record kembali muncul setelah restore.');
    }

    // ---- 8. AC-T2-14: audit trail tercatat ----

    public function test_attendance_audit_tercatat(): void
    {
        $church = Church::factory()->create();
        $admin = $this->makeAdmin($church);
        $event = $this->makeEvent($church);
        $member = $this->makeMember($church);

        $this->actingAs($admin);

        $attendance = EventAttendance::create([
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'hadir',
        ]);

        $attendance->update(['status' => 'tidak_hadir']);
        $attendance->delete();

        $actions = DB::table('audit_logs')
            ->where('auditable_type', EventAttendance::class)
            ->where('auditable_id', $attendance->id)
            ->orderBy('id')
            ->pluck('action')
            ->all();

        $this->assertSame(['created', 'updated', 'deleted'], $actions, 'audit harus mencatat created/updated/deleted (AC-T2-14).');

        $created = DB::table('audit_logs')
            ->where('auditable_type', EventAttendance::class)
            ->where('auditable_id', $attendance->id)
            ->where('action', 'created')
            ->first();

        $this->assertSame($admin->id, (int) $created->user_id);
        $this->assertSame($church->id, (int) $created->church_id);
    }

    // ---- 9. AC-T2-12: finance_admin ditolak ----

    public function test_attendance_finance_admin_ditolak(): void
    {
        $church = Church::factory()->create();
        $financeAdmin = User::factory()->create([
            'church_id' => $church->id,
            'role' => 'finance_admin',
        ]);

        $this->assertFalse(Gate::forUser($financeAdmin)->allows('viewAny', EventAttendance::class));
        $this->assertFalse(Gate::forUser($financeAdmin)->allows('create', EventAttendance::class));
    }

    // ---- 10. AC-T2-09: check-in massal skip duplikat ----

    public function test_attendance_bulk_checkin_skip_duplicate(): void
    {
        $church = Church::factory()->create();
        $admin = $this->makeAdmin($church);
        $event = $this->makeEvent($church);
        $memberA = $this->makeMember($church);
        $memberB = $this->makeMember($church);
        $memberC = $this->makeMember($church);

        // A sudah tercatat sebelumnya.
        EventAttendance::factory()->create(['event_id' => $event, 'member_id' => $memberA, 'status' => 'hadir']);

        $this->actingAs($admin);

        $result = $event->checkInMembers([$memberA->id, $memberB->id, $memberC->id]);

        $this->assertSame(2, $result['created'], 'hanya member yang belum tercatat yang dibuat.');
        $this->assertSame(1, $result['skipped'], 'duplikat harus dilewati tanpa error.');

        $this->assertSame(3, $event->attendances()->count(), 'total record attendance event = 3 (A + B + C).');
        $this->assertSame(3, $event->attendances()->distinct('member_id')->count('member_id'), 'tidak ada duplikat member.');
    }

    // ---- 11. AC-T2-17 (blocker Vera): re-check-in setelah soft delete → RESTORE, bukan create baru ----

    public function test_attendance_recheckin_setelah_soft_delete_melakukan_restore(): void
    {
        $church = Church::factory()->create();
        $admin = $this->makeAdmin($church);
        $event = $this->makeEvent($church);
        $member = $this->makeMember($church);

        $this->actingAs($admin);

        $attendance = EventAttendance::create([
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'hadir',
        ]);

        $attendance->delete(); // soft delete

        $this->assertSame(0, EventAttendance::count());
        $this->assertSame(1, EventAttendance::withTrashed()->count());

        // Check-in ulang → record lama harus di-restore, bukan insert baru.
        $restored = EventAttendance::checkInOrRestore([
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'hadir',
        ]);

        $this->assertSame($attendance->id, $restored->id, 'record lama harus di-restore, bukan record baru.');
        $this->assertNull($restored->deleted_at, 'deleted_at harus NULL setelah restore.');
        $this->assertSame(1, EventAttendance::count(), 'hanya 1 record aktif (tidak ada duplikat).');
        $this->assertSame(1, EventAttendance::withTrashed()->count(), 'total record tetap 1 (tidak ada insert baru).');
        $this->assertSame($admin->id, (int) $restored->checked_in_by, 'checked_in_by diperbarui ke aktor terbaru.');

        $actions = DB::table('audit_logs')
            ->where('auditable_type', EventAttendance::class)
            ->where('auditable_id', $attendance->id)
            ->orderBy('id')
            ->pluck('action')
            ->all();

        $this->assertContains('restored', $actions, 'audit harus mencatat restored saat record lama di-restore (AC-T2-17).');
    }

    // ---- 12. AC-T2-18: re-check-in dengan record AKTIF → dilewati tanpa perubahan ----

    public function test_attendance_recheckin_duplikat_aktif_dilewati(): void
    {
        $church = Church::factory()->create();
        $admin = $this->makeAdmin($church);
        $event = $this->makeEvent($church);
        $member = $this->makeMember($church);

        $this->actingAs($admin);

        $attendance = EventAttendance::create([
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'hadir',
            'notes' => 'catatan awal',
        ]);

        $result = EventAttendance::checkInOrRestore([
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'tidak_hadir',
            'notes' => 'catatan baru',
        ]);

        $this->assertSame($attendance->id, $result->id, 'record aktif yang sama dikembalikan.');
        $this->assertSame('hadir', $result->status, 'status record aktif TIDAK diubah (AC-T2-18).');
        $this->assertSame('catatan awal', $result->notes, 'notes record aktif TIDAK diubah (AC-T2-18).');
        $this->assertSame(1, EventAttendance::count(), 'tidak ada record baru.');
    }

    // ---- 13. AC-T2-08: create via form tidak menerima status/checked_in dari input ----

    public function test_attendance_form_tidak_menerima_status_checked_in_dari_input(): void
    {
        $church = Church::factory()->create();
        $admin = $this->makeAdmin($church);
        $event = $this->makeEvent($church);
        $member = $this->makeMember($church);

        $this->actingAs($admin);

        // Simulasi input form yang mencoba meng-set status/checked_in_at/checked_in_by seenaknya.
        $attendance = EventAttendance::create([
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'tidak_hadir', // user coba set
            'checked_in_at' => now()->subDays(5),
            'checked_in_by' => 999,
            'notes' => 'from form',
        ]);

        // booted() memaksa checked_in_at = now() dan checked_in_by = aktor,
        // karena keduanya di-set server-side (AC-T2-08).
        $this->assertSame($admin->id, (int) $attendance->checked_in_by, 'checked_in_by harus aktor, bukan input user.');
        $this->assertGreaterThan(now()->subMinutes(5), $attendance->checked_in_at, 'checked_in_at harus sekitar now(), bukan input user.');
    }
}
