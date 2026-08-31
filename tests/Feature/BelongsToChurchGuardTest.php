<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Family;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\Member;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BelongsToChurchGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_dengan_family_gereja_lain_ditolak_403(): void
    {
        $churchA = Church::factory()->create();
        $churchB = Church::factory()->create();
        $adminA = User::factory()->create([
            'church_id' => $churchA->id,
            'role' => 'church_admin',
        ]);
        // Keluarga milik gereja B — dibuat sebelum actingAs agar tidak dipaksa ke A
        $familyB = Family::factory()->create(['church_id' => $churchB->id]);

        $this->actingAs($adminA);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Data referensi 'family_id' milik gereja lain tidak diizinkan.");

        Member::create([
            'family_id' => $familyB->id,
            'full_name' => 'Percobaan Manipulasi',
            'status' => 'aktif',
        ]);
    }

    public function test_church_id_dipaksa_ke_gereja_aktor_saat_create(): void
    {
        $churchA = Church::factory()->create();
        $churchB = Church::factory()->create();
        $adminA = User::factory()->create([
            'church_id' => $churchA->id,
            'role' => 'church_admin',
        ]);
        $familyA = Family::factory()->create(['church_id' => $churchA->id]);

        $this->actingAs($adminA);

        $member = new Member([
            'family_id' => $familyA->id,
            'full_name' => 'Anggota Sah',
            'status' => 'aktif',
        ]);
        $member->church_id = $churchB->id; // upaya menitipkan data ke gereja lain
        $member->save();

        $this->assertSame($churchA->id, $member->church_id);
        $this->assertSame($churchA->id, Member::find($member->id)->church_id);
    }

    public function test_event_dengan_kategori_gereja_lain_ditolak_403(): void
    {
        $churchA = Church::factory()->create();
        $churchB = Church::factory()->create();
        $adminA = User::factory()->create([
            'church_id' => $churchA->id,
            'role' => 'church_admin',
        ]);
        $categoryB = EventCategory::factory()->create(['church_id' => $churchB->id]);

        $this->actingAs($adminA);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Data referensi 'category_id' milik gereja lain tidak diizinkan.");

        Event::create([
            'category_id' => $categoryB->id,
            'title' => 'Acara Nakal',
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDay()->addHours(2),
        ]);
    }

    public function test_transaksi_dengan_fund_gereja_lain_ditolak_403(): void
    {
        $churchA = Church::factory()->create();
        $churchB = Church::factory()->create();
        $adminA = User::factory()->create([
            'church_id' => $churchA->id,
            'role' => 'church_admin',
        ]);
        $fundB = Fund::factory()->create(['church_id' => $churchB->id]);
        $categoryA = FinancialCategory::factory()->create(['church_id' => $churchA->id]);

        $this->actingAs($adminA);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Data referensi 'fund_id' milik gereja lain tidak diizinkan.");

        Transaction::create([
            'fund_id' => $fundB->id,
            'category_id' => $categoryA->id,
            'type' => 'debit',
            'amount' => 10_000,
            'transaction_date' => now(),
            'description' => 'Transaksi Nakal',
        ]);
    }

    public function test_tanpa_aktor_church_id_yang_diisi_dipertahankan(): void
    {
        $church = Church::factory()->create();
        $family = Family::factory()->create(['church_id' => $church->id]);

        $member = Member::factory()->create([
            'family_id' => $family,
            'church_id' => $church->id,
        ]);

        $this->assertSame($church->id, $member->church_id);
    }

    public function test_factory_menghasilkan_data_konsisten_dalam_satu_gereja(): void
    {
        // Event: event, kategori, roster, member, role harus satu gereja
        $event = \App\Models\Event::factory()->create();
        $category = $event->category;
        $roster = \App\Models\EventRoster::factory()->create(['event_id' => $event]);

        $this->assertSame($event->church_id, $category->church_id);
        $this->assertSame($event->church_id, $roster->church_id);
        $this->assertSame($event->church_id, $roster->member->church_id);
        $this->assertSame($event->church_id, $roster->member->family->church_id);

        // Transaksi: transaksi, fund, kategori satu gereja
        $tx = \App\Models\Transaction::factory()->create();
        $this->assertSame($tx->church_id, $tx->fund->church_id);
        $this->assertSame($tx->church_id, $tx->category->church_id);

        // Sakramen: sakramen dan member satu gereja
        $sacrament = \App\Models\MemberSacrament::factory()->create();
        $this->assertSame($sacrament->church_id, $sacrament->member->church_id);
    }

    public function test_factory_resolve_church_dari_event_id_int(): void
    {
        $church = Church::factory()->create();
        $event = Event::factory()->create(['church_id' => $church->id]);

        // event_id diberikan sebagai INT (bukan instance) — church_id harus tetap ter-resolve.
        $roster = \App\Models\EventRoster::factory()->create(['event_id' => $event->id]);

        $this->assertSame($church->id, $roster->church_id);
        $this->assertSame($church->id, $roster->member->church_id);
        $this->assertSame($church->id, $roster->member->family->church_id);
    }

    public function test_factory_resolve_church_dari_member_id_int(): void
    {
        $church = Church::factory()->create();
        $member = Member::factory()->create(['church_id' => $church->id]);

        // member_id diberikan sebagai INT (bukan instance) — church_id harus ter-resolve.
        $sacrament = \App\Models\MemberSacrament::factory()->create(['member_id' => $member->id]);

        $this->assertSame($church->id, $sacrament->church_id);
        $this->assertSame($church->id, $sacrament->member->church_id);
    }
}
