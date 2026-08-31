<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fase 2 Task 2 — Blocker re-review Vera (PR #4):
 * AC-T2-17 (restore-or-create setelah soft delete),
 * AC-T2-18 (duplikat aktif dilewati),
 * AC-T2-08 (server-side fill — form tidak menerima status/checked_in_at/checked_in_by),
 * edge AC-T2-10 (record ada tapi semua tidak_hadir → total 0, tanpa fallback legacy).
 */
class EventAttendanceReCheckinTest extends TestCase
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

    // ---- AC-T2-17: re-check-in setelah soft delete → RESTORE record lama ----

    public function test_recheckin_setelah_soft_delete_melakukan_restore(): void
    {
        $church = Church::factory()->create();
        $admin = $this->makeAdmin($church);
        $event = $this->makeEvent($church);
        $member = $this->makeMember($church);

        $this->actingAs($admin);

        // 1. Check-in pertama → record aktif.
        $first = EventAttendance::checkInOrRestore([
            'event_id' => $event->id,
            'member_id' => $member->id,
            'notes' => 'pertama',
        ]);
        $firstId = $first->id;

        // 2. Soft delete check-in.
        $first->delete();
        $this->assertNotNull(EventAttendance::withTrashed()->find($firstId)->deleted_at);

        // 3. Check-in ulang → TIDAK create baru, record lama di-restore.
        $again = EventAttendance::checkInOrRestore([
            'event_id' => $event->id,
            'member_id' => $member->id,
            'notes' => 'kedua',
        ]);

        $this->assertSame($firstId, $again->id, 'record lama harus di-restore, bukan create baru (AC-T2-17).');
        $this->assertNull($again->deleted_at, 'deleted_at harus NULL setelah restore.');
        $this->assertSame('hadir', $again->status);
        $this->assertSame('kedua', $again->notes);
        $this->assertSame($admin->id, $again->checked_in_by);
        $this->assertNotNull($again->checked_in_at);

        // 4. Total record (termasuk trashed) tetap 1 — UNIQUE tidak dilanggar.
        $this->assertSame(1, EventAttendance::withTrashed()->where('event_id', $event->id)->where('member_id', $member->id)->count());

        // 5. Audit mencatat 'restored' (bukan 'created' baru untuk record yang sama).
        $actions = DB::table('audit_logs')
            ->where('auditable_type', EventAttendance::class)
            ->where('auditable_id', $firstId)
            ->orderBy('id')
            ->pluck('action')
            ->all();

        $this->assertContains('created', $actions);
        $this->assertContains('deleted', $actions);
        $this->assertContains('restored', $actions);
        $this->assertNotSame('created', $actions[array_key_last($actions)], 'aksi terakhir harus restored/updated, bukan created baru.');
    }

    // ---- AC-T2-18: re-check-in pada record AKTIF → dilewati tanpa perubahan ----

    public function test_recheckin_duplikat_aktif_dilewati(): void
    {
        $church = Church::factory()->create();
        $admin = $this->makeAdmin($church);
        $event = $this->makeEvent($church);
        $member = $this->makeMember($church);

        $this->actingAs($admin);

        $first = EventAttendance::checkInOrRestore([
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'hadir',
            'notes' => 'asli',
        ]);
        $firstId = $first->id;
        $originalCheckedInAt = $first->checked_in_at;

        // Check-in ulang pada record yang masih aktif.
        $again = EventAttendance::checkInOrRestore([
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'tidak_hadir',
            'notes' => 'diubah',
        ]);

        $this->assertSame($firstId, $again->id);
        $this->assertSame('hadir', $again->status, 'record aktif TIDAK diubah (AC-T2-18).');
        $this->assertSame('asli', $again->notes, 'record aktif TIDAK diubah (AC-T2-18).');
        $this->assertEquals($originalCheckedInAt, $again->checked_in_at);

        $this->assertSame(1, EventAttendance::withTrashed()->where('event_id', $event->id)->where('member_id', $member->id)->count());
    }

    // ---- edge AC-T2-10: record ada tapi semua tidak_hadir → total 0 (tanpa fallback) ----

    public function test_total_attendance_semua_tidak_hadir_tanpa_fallback_legacy(): void
    {
        $church = Church::factory()->create();
        $event = Event::factory()->create([
            'church_id' => $church->id,
            'attendance_male' => 3,
            'attendance_female' => 2,
        ]);
        $m1 = $this->makeMember($church);

        // Event punya 1 record, status tidak_hadir → total = 0 (fallback legacy 5 TIDAK dipakai).
        EventAttendance::factory()->create([
            'event_id' => $event,
            'member_id' => $m1,
            'status' => 'tidak_hadir',
        ]);

        $this->assertSame(0, $event->total_attendance, 'record ada tapi tidak hadir → 0, tanpa fallback legacy.');
    }

    // ---- AC-T2-08 / test #13: payload form create (hanya member_id) → server-side fill ----

    public function test_checkin_payload_tanpa_status_dan_waktu_menggunakan_server_fill(): void
    {
        $church = Church::factory()->create();
        $admin = $this->makeAdmin($church);
        $event = $this->makeEvent($church);
        $member = $this->makeMember($church);

        $this->actingAs($admin);

        // Meniru payload form create AC-T2-08: hanya member_id (+ event_id konteks),
        // TANPA status / checked_in_at / checked_in_by dari input.
        $attendance = EventAttendance::checkInOrRestore([
            'event_id' => $event->id,
            'member_id' => $member->id,
        ]);

        $this->assertSame('hadir', $attendance->status, 'status default hadir di-set server-side.');
        $this->assertNotNull($attendance->checked_in_at, 'checked_in_at di-set server-side.');
        $this->assertSame($admin->id, $attendance->checked_in_by, 'checked_in_by di-set server-side.');
    }

    // ---- AC-T2-09 + 17: check-in massal memulihkan member yang soft-deleted ----

    public function test_checkin_massal_memulihkan_record_soft_deleted(): void
    {
        $church = Church::factory()->create();
        $admin = $this->makeAdmin($church);
        $event = $this->makeEvent($church);
        $memberA = $this->makeMember($church);
        $memberB = $this->makeMember($church);

        $this->actingAs($admin);

        // A tercatat lalu di-soft-delete; B belum tercatat.
        $attA = EventAttendance::factory()->create(['event_id' => $event, 'member_id' => $memberA, 'status' => 'hadir']);
        $attA->delete();

        $result = $event->checkInMembers([$memberA->id, $memberB->id]);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['restored'], 'member A yang soft-deleted harus di-restore, bukan dibuat baru.');
        $this->assertSame(0, $result['skipped']);

        $this->assertNull(EventAttendance::withTrashed()->find($attA->id)->deleted_at, 'record A harus ter-restore.');
        $this->assertSame(2, EventAttendance::withTrashed()->where('event_id', $event->id)->count());
    }
}
