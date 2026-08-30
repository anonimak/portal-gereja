<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Clusters\Reporting\Pages\WartaJemaat;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventRoster;
use App\Models\Family;
use App\Models\Member;
use App\Models\MemberSacrament;
use App\Models\MinistryRole;
use App\Models\Official;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WartaJemaatTest extends TestCase
{
    use RefreshDatabase;

    private Church $churchA;
    private Church $churchB;
    private User $adminA;
    private Member $memberA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->churchA = Church::factory()->create();
        $this->churchB = Church::factory()->create();
        $this->adminA = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'church_admin',
        ]);

        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        // Member A dengan ulang tahun pada minggu ini
        $birthday = $weekStart->copy()->addDays(1);
        $familyA = Family::factory()->create(['church_id' => $this->churchA->id]);
        $this->memberA = Member::factory()->create([
            'family_id' => $familyA,
            'church_id' => $this->churchA->id,
            'birth_date' => $birthday->copy()->subYears(30)->toDateString(),
        ]);

        // Official pelayan tamu TANPA member — skenario crash lama (roster official)
        $officialA = Official::factory()->create([
            'church_id' => $this->churchA->id,
            'type' => 'pelayan_tamu',
            'external_name' => 'Pdt. Tamu',
            'origin_church' => 'Gereja Sumber',
            'start_date' => now()->subYear(),
        ]);

        // Event A minggu ini dengan roster official-only
        $categoryA = EventCategory::factory()->create(['church_id' => $this->churchA->id]);
        $roleA = MinistryRole::factory()->create(['church_id' => $this->churchA->id]);
        $eventA = Event::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $categoryA->id,
            'start_datetime' => $weekStart->copy()->addDay()->setTime(9, 0),
            'end_datetime' => $weekStart->copy()->addDay()->setTime(11, 0),
        ]);
        EventRoster::factory()->create([
            'event_id' => $eventA,
            'church_id' => $this->churchA->id,
            'member_id' => null,
            'official_id' => $officialA,
            'role_id' => $roleA,
        ]);

        // Sakramen A minggu ini
        MemberSacrament::factory()->create([
            'member_id' => $this->memberA,
            'church_id' => $this->churchA->id,
            'sacrament_date' => $weekStart->copy()->addDays(2)->toDateString(),
        ]);

        // Transaksi A minggu ini
        Transaction::factory()->create([
            'church_id' => $this->churchA->id,
            'type' => 'debit',
            'amount' => 100_000,
            'transaction_date' => $weekStart->copy()->addDays(1),
        ]);

        // ---- Data gereja B (tidak boleh bocor) ----
        $familyB = Family::factory()->create(['church_id' => $this->churchB->id]);
        $memberB = Member::factory()->create([
            'family_id' => $familyB,
            'church_id' => $this->churchB->id,
        ]);
        $categoryB = EventCategory::factory()->create(['church_id' => $this->churchB->id]);
        $roleB = MinistryRole::factory()->create(['church_id' => $this->churchB->id]);
        Event::factory()->create([
            'church_id' => $this->churchB->id,
            'category_id' => $categoryB->id,
            'start_datetime' => $weekStart->copy()->addDay()->setTime(9, 0),
            'end_datetime' => $weekStart->copy()->addDay()->setTime(11, 0),
        ]);
        MemberSacrament::factory()->create([
            'member_id' => $memberB,
            'church_id' => $this->churchB->id,
            'sacrament_date' => $weekStart->copy()->addDays(2)->toDateString(),
        ]);
        Transaction::factory()->create([
            'church_id' => $this->churchB->id,
            'type' => 'credit',
            'amount' => 50_000,
            'transaction_date' => $weekStart->copy()->addDays(1),
        ]);
    }

    public function test_get_report_data_tidak_crash_dengan_roster_official_tanpa_member(): void
    {
        $this->actingAs($this->adminA);

        $page = new WartaJemaat();
        $page->mount();

        // Dulu: roster ber-official (member null) menyebabkan crash (Carbon::parse(null) dkk)
        $data = $page->getReportData();

        $this->assertNotEmpty($data['events']);
        $roster = $data['events']->first()->rosters->first();
        $this->assertNull($roster->member_id);
        $this->assertNotNull($roster->official_id);
    }

    public function test_get_report_data_hanya_berisi_data_gereja_sendiri(): void
    {
        $this->actingAs($this->adminA);

        $page = new WartaJemaat();
        $page->mount();
        $data = $page->getReportData();

        // Events: hanya gereja A
        $this->assertSame(1, $data['events']->count());
        foreach ($data['events'] as $event) {
            $this->assertSame($this->churchA->id, $event->church_id);
        }

        // Sakramen: hanya gereja A
        $this->assertSame(1, $data['sacraments']->count());
        foreach ($data['sacraments'] as $sacrament) {
            $this->assertSame($this->churchA->id, $sacrament->church_id);
        }

        // Ulang tahun: hanya member A
        $this->assertTrue($data['birthdays']->contains('id', $this->memberA->id));

        // Transaksi: hanya gereja A, type debit → Pemasukan
        $this->assertArrayHasKey('Pemasukan', $data['transactions']);
        $this->assertArrayNotHasKey('Pengeluaran', $data['transactions']);
        $this->assertSame(100_000, (int) $data['transactions']['Pemasukan']->sum('amount'));
    }

    public function test_stat_widget_dashboard_terisolasi_per_gereja(): void
    {
        $this->actingAs($this->adminA);

        $widget = new \App\Filament\Widgets\StatsOverview();
        $reflection = new \ReflectionMethod($widget, 'getStats');
        $stats = $reflection->invoke($widget);

        $this->assertCount(3, $stats);
        // index 1 = pemasukan bulan ini, index 2 = pengeluaran bulan ini
        $this->assertStringContainsString('Rp100.000', (string) $stats[1]->getValue());
        $this->assertStringContainsString('Rp0', (string) $stats[2]->getValue());
    }
}
