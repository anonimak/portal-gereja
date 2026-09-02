<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Member;
use App\Models\Official;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 2 Task 3 — LOW-4: soft-delete Member menonaktifkan jabatan Official
 * (AC-T3-16..19).
 */
class OfficialAutoDeactivateTest extends TestCase
{
    use RefreshDatabase;

    private Church $church;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->church = Church::factory()->create();
        $this->admin = User::factory()->create([
            'church_id' => $this->church->id,
            'role' => 'church_admin',
        ]);
    }

    private function makeMajelisLokal(): array
    {
        $member = Member::factory()->create(['church_id' => $this->church->id]);

        $official = Official::factory()->create([
            'church_id' => $this->church->id,
            'type' => 'majelis_lokal',
            'member_id' => $member->id,
            'start_date' => now()->subYear(),
            'end_date' => null,
        ]);

        return [$member, $official];
    }

    // ---- AC-T3-16: soft delete member → official end_date terisi, isActive=false ----

    public function test_soft_delete_member_mengisi_end_date_official(): void
    {
        $this->actingAs($this->admin);

        [$member, $official] = $this->makeMajelisLokal();

        $this->assertNull($official->fresh()->end_date);

        $member->delete();

        $official->refresh();

        $this->assertNotNull($official->end_date);
        $this->assertSame(today()->toDateString(), $official->end_date->toDateString());
        $this->assertFalse($official->isActive);
    }

    // ---- AC-T3-17: restore member → end_date TETAP terisi ----

    public function test_restore_member_tidak_mengembalikan_end_date(): void
    {
        $this->actingAs($this->admin);

        [$member, $official] = $this->makeMajelisLokal();
        $member->delete();
        $official->refresh();
        $this->assertNotNull($official->end_date);

        $member->restore();

        $official->refresh();
        $this->assertNotNull($official->end_date);
        $this->assertSame(today()->toDateString(), $official->end_date->toDateString());
        // end_date sudah lewat/today → isActive mengikuti end_date.
        $this->assertFalse($official->isActive);
    }

    // ---- AC-T3-18: display_name member trashed → "(Nonaktif)" bukan "Unknown" ----

    public function test_display_name_member_trashed_menampilkan_nonaktif(): void
    {
        $this->actingAs($this->admin);

        [$member, $official] = $this->makeMajelisLokal();
        $member->delete();

        $display = $official->fresh()->display_name;

        $this->assertStringContainsString($member->full_name, $display);
        $this->assertStringContainsString('(Nonaktif)', $display);
        $this->assertStringNotContainsString('Unknown', $display);
    }

    // ---- AC-T3-19: forceDelete member TIDAK menghapus officials (FK restrict) ----

    public function test_force_delete_member_ditolak_fk_restrict(): void
    {
        $this->actingAs($this->admin);

        [$member, $official] = $this->makeMajelisLokal();

        try {
            $member->forceDelete();
            $this->fail('forceDelete Member dengan official seharusnya ditolak oleh FK restrict.');
        } catch (QueryException $e) {
            // expected: FK constraint violation
            $this->assertTrue(true);
        }

        $this->assertDatabaseHas('officials', ['id' => $official->id]);
        $this->assertDatabaseHas('members', ['id' => $member->id]);
    }

    // ---- AC-T3-20: member trashed tidak muncul di select official (tanpa withTrashed) ----

    public function test_member_trashed_tidak_bisa_dipilih_sebagai_official_baru(): void
    {
        $this->actingAs($this->admin);

        $member = Member::factory()->create(['church_id' => $this->church->id]);
        $member->delete();

        $available = Member::query()->pluck('id')->all();

        $this->assertNotContains($member->id, $available);
    }
}
