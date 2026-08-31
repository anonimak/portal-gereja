<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Fund;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Fase 2 Task 3 — AC-T3-14: super_admin bisa create record untuk gereja lain,
 * tapi FK lintas gereja tetap ditolak 403 (guard BelongsToChurch).
 */
class CrossChurchCreateTest extends TestCase
{
    use RefreshDatabase;

    private Church $churchA;

    private Church $churchB;

    private User $superAdmin;

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
    }

    public function test_super_admin_create_event_untuk_gereja_b(): void
    {
        $this->actingAs($this->superAdmin);

        $categoryB = EventCategory::factory()->create(['church_id' => $this->churchB->id]);

        $event = Event::create([
            'church_id' => $this->churchB->id,
            'category_id' => $categoryB->id,
            'title' => 'Ibadah Raya Gereja B',
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDay()->addHours(2),
        ]);

        $this->assertSame($this->churchB->id, $event->church_id);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'church_id' => $this->churchB->id,
        ]);
    }

    public function test_super_admin_pilih_fk_lintas_gereja_ditolak_403(): void
    {
        $this->actingAs($this->superAdmin);

        $categoryA = EventCategory::factory()->create(['church_id' => $this->churchA->id]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Data referensi 'category_id' milik gereja lain tidak diizinkan.");

        Event::create([
            'church_id' => $this->churchB->id,
            'category_id' => $categoryA->id, // kategori gereja A — harus ditolak
            'title' => 'Event Nakal',
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDay()->addHours(2),
        ]);
    }

    public function test_super_admin_create_transaksi_untuk_gereja_b(): void
    {
        $this->actingAs($this->superAdmin);

        $fundB = Fund::factory()->create(['church_id' => $this->churchB->id]);
        $categoryB = \App\Models\FinancialCategory::factory()->create(['church_id' => $this->churchB->id]);

        $tx = Transaction::create([
            'church_id' => $this->churchB->id,
            'fund_id' => $fundB->id,
            'category_id' => $categoryB->id,
            'type' => 'debit',
            'amount' => 50_000,
            'transaction_date' => now(),
            'description' => 'Persembahan Gereja B',
        ]);

        $this->assertSame($this->churchB->id, $tx->church_id);
        $this->assertDatabaseHas('transactions', [
            'id' => $tx->id,
            'church_id' => $this->churchB->id,
        ]);
    }

    public function test_super_admin_transaksi_fk_lintas_gereja_ditolak_403(): void
    {
        $this->actingAs($this->superAdmin);

        $fundA = Fund::factory()->create(['church_id' => $this->churchA->id]);
        $categoryB = \App\Models\FinancialCategory::factory()->create(['church_id' => $this->churchB->id]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Data referensi 'fund_id' milik gereja lain tidak diizinkan.");

        Transaction::create([
            'church_id' => $this->churchB->id,
            'fund_id' => $fundA->id,
            'category_id' => $categoryB->id,
            'type' => 'debit',
            'amount' => 10_000,
            'transaction_date' => now(),
            'description' => 'Transaksi Nakal',
        ]);
    }

    public function test_church_id_terisi_otomatis_untuk_non_super_admin(): void
    {
        $churchAdmin = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'church_admin',
        ]);

        $this->actingAs($churchAdmin);

        $categoryA = EventCategory::factory()->create(['church_id' => $this->churchA->id]);

        $event = Event::create([
            'category_id' => $categoryA->id,
            'title' => 'Ibadah Gereja A',
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDay()->addHours(2),
        ]);

        $this->assertSame($this->churchA->id, $event->church_id);
    }
}
