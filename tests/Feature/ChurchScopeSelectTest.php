<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Family;
use App\Models\Member;
use App\Models\User;
use App\Support\ChurchScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 2 Task 3 — backlog MED: helper ChurchScope untuk select lintas gereja
 * (AC-T3-10..13).
 */
class ChurchScopeSelectTest extends TestCase
{
    use RefreshDatabase;

    private Church $churchA;

    private Church $churchB;

    private User $superAdmin;

    private User $churchAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->churchA = Church::factory()->create();
        $this->churchB = Church::factory()->create();

        $this->superAdmin = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'super_admin',
        ]);
        $this->churchAdmin = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'church_admin',
        ]);
    }

    // ---- AC-T3-11: super_admin melihat semua gereja ----

    public function test_for_actor_select_super_admin_tidak_memfilter_gereja(): void
    {
        Family::factory()->create(['church_id' => $this->churchA->id]);
        Family::factory()->create(['church_id' => $this->churchB->id]);

        $this->actingAs($this->superAdmin);

        $query = ChurchScope::forActorSelect(Family::query());

        $this->assertSame(2, $query->count());
    }

    // ---- AC-T3-12: non-super hanya gereja sendiri ----

    public function test_for_actor_select_church_admin_hanya_gereja_sendiri(): void
    {
        Family::factory()->create(['church_id' => $this->churchA->id]);
        Family::factory()->create(['church_id' => $this->churchB->id]);

        $this->actingAs($this->churchAdmin);

        $query = ChurchScope::forActorSelect(Family::query());

        $this->assertSame(1, $query->count());
        $this->assertSame($this->churchA->id, $query->first()->church_id);
    }

    // ---- AC-T3-13: forChurch mengikuti gereja OWNER RECORD ----

    public function test_for_church_memfilter_ke_gereja_owner_record(): void
    {
        Family::factory()->create(['church_id' => $this->churchA->id]);
        Family::factory()->create(['church_id' => $this->churchB->id]);

        $query = ChurchScope::forChurch($this->churchB->id, Family::query());

        $this->assertSame(1, $query->count());
        $this->assertSame($this->churchB->id, $query->first()->church_id);
    }

    // ---- AC-T3-13 lanjutan: forChurch mengabaikan gereja aktor ----

    public function test_helper_for_church_tidak_dipengaruhi_aktor(): void
    {
        Family::factory()->create(['church_id' => $this->churchB->id]);

        $this->actingAs($this->churchAdmin); // aktor gereja A

        $query = ChurchScope::forChurch($this->churchB->id, Family::query());

        // forChurch MENGABAIKAN gereja aktor — selalu ikut owner record.
        $this->assertSame(1, $query->count());
        $this->assertSame($this->churchB->id, $query->first()->church_id);
    }

    // ---- Integrasi: scope global BelongsToChurch tetap aktif untuk super_admin ----

    public function test_global_scope_tenant_tetap_aktif_untuk_super_admin(): void
    {
        $memberA = Member::factory()->create(['church_id' => $this->churchA->id]);
        $memberB = Member::factory()->create(['church_id' => $this->churchB->id]);

        $this->actingAs($this->superAdmin);

        $this->assertSame(2, Member::query()->count());
        $this->assertTrue(in_array($memberA->id, Member::query()->pluck('id')->all(), true));
        $this->assertTrue(in_array($memberB->id, Member::query()->pluck('id')->all(), true));
    }
}
