<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Event;
use App\Models\EventRoster;
use App\Models\Family;
use App\Models\Member;
use App\Models\MemberSacrament;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Church $churchA;

    private Church $churchB;

    private User $adminA;

    private User $superAdmin;

    /** @var array<string, mixed> */
    private array $fixturesA = [];

    /** @var array<string, mixed> */
    private array $fixturesB = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->churchA = Church::factory()->create(['name' => 'Gereja A Test']);
        $this->churchB = Church::factory()->create(['name' => 'Gereja B Test']);

        $this->adminA = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'church_admin',
        ]);
        User::factory()->create(['church_id' => $this->churchB->id, 'role' => 'church_admin']);
        $this->superAdmin = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'super_admin',
        ]);

        $this->fixturesA = $this->seedChurchFixtures($this->churchA);
        $this->fixturesB = $this->seedChurchFixtures($this->churchB);
    }

    /**
     * @return array{family: Family, member: Member, sacrament: MemberSacrament, event: Event, roster: EventRoster, transaction: Transaction}
     */
    private function seedChurchFixtures(Church $church): array
    {
        $family = Family::factory()->create(['church_id' => $church->id]);
        $member = Member::factory()->create(['family_id' => $family, 'church_id' => $church->id]);
        $sacrament = MemberSacrament::factory()->create(['member_id' => $member, 'church_id' => $church->id]);
        $category = \App\Models\EventCategory::factory()->create(['church_id' => $church->id]);
        $role = \App\Models\MinistryRole::factory()->create(['church_id' => $church->id]);
        $event = \App\Models\Event::factory()->create([
            'church_id' => $church->id,
            'category_id' => $category->id,
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDay()->addHours(2),
        ]);
        $roster = EventRoster::factory()->create([
            'event_id' => $event,
            'church_id' => $church->id,
            'member_id' => $member,
            'role_id' => $role,
        ]);
        $transaction = Transaction::factory()->create([
            'church_id' => $church->id,
            'type' => 'debit',
            'amount' => 500_000,
            'transaction_date' => now(),
        ]);

        return compact('family', 'member', 'sacrament', 'event', 'roster', 'transaction');
    }

    public function test_church_admin_hanya_melihat_data_gereja_sendiri(): void
    {
        $this->actingAs($this->adminA);

        $this->assertSame(1, Member::count());
        $this->assertSame(1, MemberSacrament::count());
        $this->assertSame(1, Event::count());
        $this->assertSame(1, EventRoster::count());
        $this->assertSame(1, Transaction::count());
        $this->assertSame(1, Family::count());
    }

    public function test_church_admin_tidak_dapat_membaca_record_gereja_lain(): void
    {
        $this->actingAs($this->adminA);

        // Global scope menyembunyikan record gereja B
        $this->assertNull(Member::find($this->fixturesB['member']->id));
        $this->assertNull(MemberSacrament::find($this->fixturesB['sacrament']->id));
        $this->assertNull(Event::find($this->fixturesB['event']->id));
        $this->assertNull(Transaction::find($this->fixturesB['transaction']->id));
    }

    public function test_policy_menolak_idor_lintas_gereja(): void
    {
        $memberB = $this->fixturesB['member'];
        $transactionB = $this->fixturesB['transaction'];
        $rosterB = $this->fixturesB['roster'];

        $this->assertTrue(Gate::forUser($this->adminA)->denies('view', $memberB));
        $this->assertTrue(Gate::forUser($this->adminA)->denies('update', $memberB));
        $this->assertTrue(Gate::forUser($this->adminA)->denies('delete', $memberB));
        $this->assertTrue(Gate::forUser($this->adminA)->denies('view', $transactionB));
        $this->assertTrue(Gate::forUser($this->adminA)->denies('view', $rosterB));

        $this->assertTrue(Gate::forUser($this->adminA)->allows('view', $this->fixturesA['member']));
        $this->assertTrue(Gate::forUser($this->adminA)->allows('update', $this->fixturesA['member']));
    }

    public function test_super_admin_dapat_melihat_semua_gereja(): void
    {
        $this->actingAs($this->superAdmin);

        $this->assertSame(2, Member::count());
        $this->assertSame(2, MemberSacrament::count());
        $this->assertSame(2, Event::count());
        $this->assertSame(2, Transaction::count());
    }
}
