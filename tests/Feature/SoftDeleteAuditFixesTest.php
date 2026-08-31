<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Clusters\Demographics\Resources\Members\MemberResource;
use App\Filament\Pages\LaporanRapatPage;
use App\Models\AuditLog;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventRoster;
use App\Models\Family;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\Member;
use App\Models\MemberSacrament;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 2 — Perbaikan temuan re-review Vera pada PR #2 (C1-C3, H1-H4, M1).
 *
 * Catatan H4: kolom transactions.category_id NOT NULL + FK restrictOnDelete
 * membuat transaksi tanpa kategori tidak mungkin terjadi di DB normal; fallback
 * 'Tanpa kategori' adalah defense-in-depth untuk data lama/skenario migrasi.
 * Test di bawah membuktikan getReportData() tidak 500 dengan data normal.
 */
class SoftDeleteAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    private Church $church;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->church = Church::factory()->create(['name' => 'Gereja Fix Test']);
        $this->admin = User::factory()->create([
            'church_id' => $this->church->id,
            'role' => 'church_admin',
        ]);
    }

    // ---------- C1 / AC-AU-06: password & remember_token tidak boleh bocor ----------

    public function test_audit_update_user_tidak_mencatat_password_dan_remember_token(): void
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create([
            'church_id' => $this->church->id,
            'password' => 'secret-awal',
        ]);
        $user->update([
            'name' => 'Nama Baru',
            'password' => 'secret-baru',
        ]);

        $log = AuditLog::query()
            ->where('action', 'updated')
            ->where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('password', $log->old_values ?? []);
        $this->assertArrayNotHasKey('password', $log->new_values ?? []);
        $this->assertArrayNotHasKey('remember_token', $log->old_values ?? []);
        $this->assertArrayNotHasKey('remember_token', $log->new_values ?? []);
        $this->assertSame('Nama Baru', $log->new_values['name']);
    }

    // ---------- M1: forceDelete menghasilkan tepat 1 baris audit ----------

    public function test_force_delete_menghasilkan_satu_baris_audit(): void
    {
        $this->actingAs($this->admin);

        $transaction = Transaction::factory()->create(['church_id' => $this->church->id]);
        $transaction->forceDelete();

        // Total audit transaksi = created (saat factory) + force_deleted.
        // Yang diuji M1: TEPAT 1 baris force_deleted, dan TIDAK ada baris deleted.
        $forceDeletedCount = AuditLog::query()
            ->where('action', 'force_deleted')
            ->where('auditable_type', Transaction::class)
            ->where('auditable_id', $transaction->id)
            ->count();

        $this->assertSame(1, $forceDeletedCount);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'force_deleted',
            'auditable_type' => Transaction::class,
            'auditable_id' => $transaction->id,
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'deleted',
            'auditable_type' => Transaction::class,
            'auditable_id' => $transaction->id,
        ]);
    }

    // ---------- H1 / AC-TN: audit_logs terisi church_id & terisolasi per gereja ----------

    public function test_audit_log_mencatat_church_id_dari_record(): void
    {
        $this->actingAs($this->admin);

        $member = Member::factory()->create(['church_id' => $this->church->id]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => Member::class,
            'auditable_id' => $member->id,
            'church_id' => $this->church->id,
        ]);
    }

    public function test_non_super_admin_hanya_melihat_audit_gereja_sendiri(): void
    {
        $this->actingAs($this->admin);

        Member::factory()->create(['church_id' => $this->church->id]);

        // Pastikan audit Member gereja A benar-benar ada (tanpa scope).
        $this->assertGreaterThan(
            0,
            AuditLog::query()
                ->withoutGlobalScopes()
                ->where('church_id', $this->church->id)
                ->where('auditable_type', Member::class)
                ->count()
        );

        // Admin gereja B tidak boleh melihat audit gereja A sama sekali.
        // Dibuat via withoutEvents() agar UserObserver (larangan user lintas gereja
        // saat ada aktor terautentikasi) tidak memblokir setup test ini.
        $churchB = Church::factory()->create();
        $adminB = User::withoutEvents(fn () => User::factory()->create([
            'church_id' => $churchB->id,
            'role' => 'church_admin',
        ]));

        $this->actingAs($adminB);

        $this->assertSame(0, AuditLog::query()->where('church_id', $this->church->id)->count());
        $this->assertSame(0, AuditLog::query()->where('auditable_type', Member::class)->count());
    }

    // ---------- C2: cascade soft delete anak (Event->EventRoster, Member->MemberSacrament) ----------

    public function test_soft_delete_event_mengikutkan_event_rosters(): void
    {
        $this->actingAs($this->admin);

        $event = Event::factory()->create([
            'church_id' => $this->church->id,
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDay()->addHours(2),
        ]);
        $roster = EventRoster::factory()->create([
            'church_id' => $this->church->id,
            'event_id' => $event->id,
        ]);

        $event->delete();

        $this->assertNotNull(EventRoster::withTrashed()->find($roster->id)->deleted_at);
        $this->assertNull(EventRoster::find($roster->id));

        $event->restore();

        $this->assertNull(EventRoster::withTrashed()->find($roster->id)->deleted_at);
        $this->assertNotNull(EventRoster::find($roster->id));
    }

    public function test_soft_delete_member_mengikutkan_member_sacraments(): void
    {
        $this->actingAs($this->admin);

        $family = Family::factory()->create(['church_id' => $this->church->id]);
        $member = Member::factory()->create([
            'church_id' => $this->church->id,
            'family_id' => $family->id,
        ]);
        $sacrament = MemberSacrament::factory()->create([
            'church_id' => $this->church->id,
            'member_id' => $member->id,
        ]);

        $member->delete();

        $this->assertNotNull(MemberSacrament::withTrashed()->find($sacrament->id)->deleted_at);
        $this->assertNull(MemberSacrament::find($sacrament->id));

        $member->restore();

        $this->assertNull(MemberSacrament::withTrashed()->find($sacrament->id)->deleted_at);
        $this->assertNotNull(MemberSacrament::find($sacrament->id));
    }

    // ---------- C3: FK restrict melindungi data historis ----------

    public function test_hapus_fund_dengan_transaksi_ditolak_oleh_fk_restrict(): void
    {
        $this->actingAs($this->admin);

        $fund = Fund::factory()->create(['church_id' => $this->church->id]);
        $category = FinancialCategory::factory()->create(['church_id' => $this->church->id]);
        $transaction = Transaction::factory()->create([
            'church_id' => $this->church->id,
            'fund_id' => $fund->id,
            'category_id' => $category->id,
        ]);

        try {
            $fund->delete();
            $this->fail('Fund dengan transaksi seharusnya ditolak oleh FK restrict.');
        } catch (QueryException $e) {
            // expected: SQLite/MySQL FK constraint violation
            $this->assertTrue(true);
        }

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
        $this->assertDatabaseHas('funds', ['id' => $fund->id]);
    }

    // ---------- H4: laporan rapat tidak 500 (fallback null-safe kategori) ----------

    public function test_laporan_rapat_render_dengan_transaksi_normal(): void
    {
        $this->actingAs($this->admin);

        $fund = Fund::factory()->create(['church_id' => $this->church->id]);
        $category = FinancialCategory::factory()->create([
            'church_id' => $this->church->id,
            'name' => 'Persembahan Umum',
        ]);
        Transaction::factory()->income()->create([
            'church_id' => $this->church->id,
            'fund_id' => $fund->id,
            'category_id' => $category->id,
            'transaction_date' => now(),
        ]);

        $page = new LaporanRapatPage;
        $page->data = [
            'period_type' => 'monthly',
            'year' => now()->year,
            'month' => now()->month,
            'quarter' => ceil(now()->month / 3),
        ];

        $data = $page->getReportData();

        $this->assertArrayHasKey('income', $data);
        $this->assertArrayHasKey('expenses', $data);
        $this->assertTrue(
            $data['income']->contains(fn (array $item): bool => $item['category'] === 'Persembahan Umum')
        );
    }

    // ---------- H3 / AC-UI-01: halaman Member render dengan TrashedFilter ----------

    public function test_halaman_member_render_dengan_trashed_filter(): void
    {
        $this->actingAs($this->admin);

        $this->get(MemberResource::getUrl('index'))
            ->assertStatus(200);
    }
}
