<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\RosterConflictException;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventRoster;
use App\Models\Member;
use App\Models\MinistryRole;
use App\Models\Official;
use App\Services\RosterConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Deteksi bentrok jadwal pelayan (roster) — Task slot 07:00 Jumat 4 Sep.
 *
 * Aturan: satu orang (member/official) tidak boleh dijadwalkan pada dua event
 * yang waktunya overlap, atau didaftarkan dua kali pada event yang sama.
 * Guard dipasang di EventRoster::saving (level model); UI memakai conflictNote().
 */
class RosterConflictTest extends TestCase
{
    use RefreshDatabase;

    private Church $church;

    private Member $member;

    private MinistryRole $role;

    protected function setUp(): void
    {
        parent::setUp();

        // Dibuat tanpa auth — trait BelongsToChurch tidak memaksa church_id aktor
        // (konsisten pola test lintas-gereja lain di repo ini).
        $this->church = Church::factory()->create();
        $this->member = Member::factory()->create(['church_id' => $this->church->id]);
        $this->role = MinistryRole::factory()->create(['church_id' => $this->church->id]);
    }

    private function event(string $start, ?string $end = null): Event
    {
        return Event::factory()->create([
            'church_id' => $this->church->id,
            'start_datetime' => $start,
            'end_datetime' => $end,
        ]);
    }

    private function rosterFor(Event $event, ?Member $member = null, ?Official $official = null): EventRoster
    {
        $roster = new EventRoster([
            'church_id' => $this->church->id,
            'member_id' => $member?->id ?? $this->member->id,
            'official_id' => $official?->id,
            'role_id' => $this->role->id,
        ]);
        $roster->event()->associate($event);
        $roster->save();

        return $roster;
    }

    public function test_member_dijadwalkan_dua_event_overlap_ditolak(): void
    {
        $a = $this->event('2026-09-06 08:00:00', '2026-09-06 10:00:00');
        $this->rosterFor($a);

        $b = $this->event('2026-09-06 09:00:00', '2026-09-06 11:00:00');

        try {
            $this->rosterFor($b);
            $this->fail('Seharusnya ValidationException dilempar untuk jadwal overlap.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('rosters', $e->errors());
        }
    }

    public function test_member_dijadwalkan_event_tidak_overlap_diizinkan(): void
    {
        $a = $this->event('2026-09-06 08:00:00', '2026-09-06 10:00:00');
        $this->rosterFor($a);

        $b = $this->event('2026-09-06 10:00:00', '2026-09-06 12:00:00'); // boundary: end A == start B
        $rosterB = $this->rosterFor($b);

        $this->assertDatabaseHas('event_rosters', ['id' => $rosterB->id]);
    }

    public function test_duplikat_member_pada_event_sama_ditolak(): void
    {
        $a = $this->event('2026-09-06 08:00:00', '2026-09-06 10:00:00');
        $this->rosterFor($a);

        $this->expectException(ValidationException::class);
        $this->rosterFor($a); // member sama, event sama
    }

    public function test_official_overlap_ditolak(): void
    {
        $official = Official::factory()->create(['church_id' => $this->church->id]);
        $a = $this->event('2026-09-06 08:00:00', '2026-09-06 10:00:00');
        $this->rosterFor($a, official: $official);

        $b = $this->event('2026-09-06 09:30:00', '2026-09-06 11:00:00');

        try {
            $this->rosterFor($b, official: $official);
            $this->fail('Seharusnya ValidationException dilempar untuk official overlap.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('rosters', $e->errors());
        }
    }

    public function test_edit_roster_tanpa_perubahan_tidak_bentrok_dengan_dirinya_sendiri(): void
    {
        $a = $this->event('2026-09-06 08:00:00', '2026-09-06 10:00:00');
        $roster = $this->rosterFor($a);

        // Update sederhana (mis. ganti role) — dirinya sendiri harus dikecualikan.
        $roster->role_id = MinistryRole::factory()->create(['church_id' => $this->church->id])->id;
        $roster->save();

        $this->assertDatabaseHas('event_rosters', ['id' => $roster->id]);
    }

    public function test_conflict_note_mengembalikan_pesan_bentrok(): void
    {
        $a = $this->event('2026-09-06 08:00:00', '2026-09-06 10:00:00');
        $this->rosterFor($a);

        $b = $this->event('2026-09-06 09:00:00', '2026-09-06 11:00:00');
        $note = RosterConflictService::conflictNote(
            eventId: $b->id,
            start: $b->start_datetime,
            end: $b->end_datetime,
            memberId: $this->member->id,
        );

        $this->assertNotNull($note);
        $this->assertStringContainsString('Bentrok', $note);
        $this->assertStringContainsString($a->title, $note);
    }

    public function test_conflict_note_null_saat_tidak_ada_bentrok(): void
    {
        $a = $this->event('2026-09-06 08:00:00', '2026-09-06 10:00:00');
        $this->rosterFor($a);

        $b = $this->event('2026-09-06 11:00:00', '2026-09-06 12:00:00'); // tidak overlap
        $note = RosterConflictService::conflictNote(
            eventId: $b->id,
            start: $b->start_datetime,
            end: $b->end_datetime,
            memberId: $this->member->id,
        );

        $this->assertNull($note);
    }

    public function test_isolasi_antar_gereja_tidak_dianggap_bentrok(): void
    {
        // Gereja B: member berbeda dengan jadwal overlap — TIDAK bentrok lintas gereja.
        $churchB = Church::factory()->create();
        $memberB = Member::factory()->create(['church_id' => $churchB->id]);

        $a = $this->event('2026-09-06 08:00:00', '2026-09-06 10:00:00');
        $this->rosterFor($a);

        $eventB = Event::factory()->create([
            'church_id' => $churchB->id,
            'start_datetime' => '2026-09-06 09:00:00',
            'end_datetime' => '2026-09-06 11:00:00',
        ]);

        $rosterB = new EventRoster([
            'church_id' => $churchB->id,
            'member_id' => $memberB->id,
            'role_id' => MinistryRole::factory()->create(['church_id' => $churchB->id])->id,
        ]);
        $rosterB->event()->associate($eventB);
        $rosterB->save();

        $this->assertDatabaseHas('event_rosters', ['id' => $rosterB->id]);
    }
}
