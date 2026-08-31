<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Church;
use App\Models\Event;
use App\Models\Member;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 2 — Soft delete + Audit trail.
 */
class SoftDeleteAuditTest extends TestCase
{
    use RefreshDatabase;

    private Church $church;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::factory()->create(['name' => 'Gereja Audit Test']);
        $this->admin = User::factory()->create([
            'church_id' => $this->church->id,
            'role' => 'church_admin',
        ]);
    }

    // ---------- Soft delete ----------

    public function test_soft_delete_menyembunyikan_record_dari_query_default(): void
    {
        $this->actingAs($this->admin);

        $member = Member::factory()->create(['church_id' => $this->church->id]);

        $this->assertSame(1, Member::count());

        $member->delete();

        $this->assertNull(Member::find($member->id));
        $this->assertSame(0, Member::count());
        $this->assertNotNull(Member::withTrashed()->find($member->id));
        $this->assertNotNull($member->deleted_at);
    }

    public function test_restore_mengembalikan_record(): void
    {
        $this->actingAs($this->admin);

        $member = Member::factory()->create(['church_id' => $this->church->id]);
        $member->delete();
        $member->restore();

        $this->assertNotNull(Member::find($member->id));
        $this->assertSame(1, Member::count());
        $this->assertNull($member->deleted_at);
    }

    public function test_force_delete_menghapus_permanen(): void
    {
        $this->actingAs($this->admin);

        $transaction = Transaction::factory()->create(['church_id' => $this->church->id]);
        $transaction->forceDelete();

        $this->assertNull(Transaction::withTrashed()->find($transaction->id));
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_soft_delete_juga_berlaku_untuk_event_dan_relasinya(): void
    {
        $this->actingAs($this->admin);

        $event = Event::factory()->create([
            'church_id' => $this->church->id,
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDay()->addHours(2),
        ]);

        $this->assertSame(1, Event::count());

        $event->delete();

        $this->assertNull(Event::find($event->id));
        $this->assertSame(0, Event::count());
    }

    // ---------- Audit trail ----------

    public function test_audit_create_tercatat(): void
    {
        $this->actingAs($this->admin);

        $member = Member::factory()->create(['church_id' => $this->church->id]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'created',
            'auditable_type' => Member::class,
            'auditable_id' => $member->id,
        ]);
    }

    public function test_audit_update_menyimpan_nilai_lama_dan_baru(): void
    {
        $this->actingAs($this->admin);

        $member = Member::factory()->create(['church_id' => $this->church->id]);
        $member->update(['full_name' => 'Nama Baru Test']);

        $log = AuditLog::query()
            ->where('action', 'updated')
            ->where('auditable_type', Member::class)
            ->where('auditable_id', $member->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame('Nama Baru Test', $log->new_values['full_name']);
        $this->assertArrayHasKey('full_name', $log->old_values);
    }

    public function test_audit_delete_dan_restore_tercatat(): void
    {
        $this->actingAs($this->admin);

        $member = Member::factory()->create(['church_id' => $this->church->id]);
        $member->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'auditable_type' => Member::class,
            'auditable_id' => $member->id,
        ]);

        $member->restore();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'restored',
            'auditable_type' => Member::class,
            'auditable_id' => $member->id,
        ]);
    }

    public function test_audit_force_delete_tercatat(): void
    {
        $this->actingAs($this->admin);

        $transaction = Transaction::factory()->create(['church_id' => $this->church->id]);
        $transaction->forceDelete();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'force_deleted',
            'auditable_type' => Transaction::class,
            'auditable_id' => $transaction->id,
        ]);
    }

    public function test_audit_tidak_rusak_saat_tanpa_aktor(): void
    {
        // Tanpa auth (console/seeder) — audit tetap tercatat dengan user_id null.
        $member = Member::factory()->create(['church_id' => $this->church->id]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => null,
            'action' => 'created',
            'auditable_type' => Member::class,
            'auditable_id' => $member->id,
        ]);
    }

    public function test_operasi_normal_tidak_rusak_oleh_audit(): void
    {
        $this->actingAs($this->admin);

        $event = Event::factory()->create([
            'church_id' => $this->church->id,
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDay()->addHours(2),
        ]);

        $this->assertSame(1, Event::count());
        $this->assertDatabaseHas('events', ['id' => $event->id]);
        // Audit create untuk event tercatat.
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => Event::class,
            'auditable_id' => $event->id,
        ]);
    }
}
