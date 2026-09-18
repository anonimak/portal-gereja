<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Clusters\Reporting\Resources\MeetingMinutesResource;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\MeetingMinutes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingMinutesResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $churchAdmin;

    private Church $church;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->church = Church::factory()->create(['name' => 'GKSBS Candimas']);
        $this->churchAdmin = User::factory()->create([
            'role' => 'church_admin',
            'church_id' => $this->church->id,
        ]);
    }

    // ------------------------------------------------------------------ //
    // LIST
    // ------------------------------------------------------------------ //

    public function test_super_admin_can_list_notulen(): void
    {
        MeetingMinutes::factory()->count(2)->create(['church_id' => $this->church->id]);

        $this->actingAs($this->superAdmin)
            ->get(MeetingMinutesResource::getUrl('index'))
            ->assertOk();
    }

    public function test_church_admin_can_list_notulen_for_own_church(): void
    {
        MeetingMinutes::factory()->create([
            'title' => 'Notulen Rapat Pleno',
            'meeting_date' => now()->toDateString(),
            'church_id' => $this->church->id,
        ]);

        $this->actingAs($this->churchAdmin)
            ->get(MeetingMinutesResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Notulen Rapat Pleno');
    }

    // ------------------------------------------------------------------ //
    // CREATE
    // ------------------------------------------------------------------ //

    public function test_super_admin_can_access_create_page(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(MeetingMinutesResource::getUrl('create'))
            ->assertOk();
    }

    public function test_notulen_model_stored_correctly(): void
    {
        $this->actingAs($this->superAdmin);

        MeetingMinutes::create([
            'church_id' => $this->church->id,
            'title' => 'Rapat Majelis Bulanan',
            'meeting_date' => now()->toDateString(),
            'agenda' => ['Pembahasan Anggaran', 'Laporan Pelayanan'],
            'participants' => ['Pdt. Yohanes', 'Pnt. Petrus'],
            'notes' => 'Rapat berlangsung kondusif.',
            'decisions' => ['Anggaran disetujui', 'Pelayanan dilanjutkan'],
        ]);

        $this->assertDatabaseHas('meeting_minutes', [
            'title' => 'Rapat Majelis Bulanan',
            'church_id' => $this->church->id,
        ]);
    }

    // ------------------------------------------------------------------ //
    // EDIT / VIEW
    // ------------------------------------------------------------------ //

    public function test_super_admin_can_access_edit_page(): void
    {
        $notulen = MeetingMinutes::factory()->create([
            'title' => 'Notulen Lama',
            'church_id' => $this->church->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->get(MeetingMinutesResource::getUrl('edit', ['record' => $notulen]))
            ->assertOk();
    }

    public function test_super_admin_can_access_view_page(): void
    {
        $notulen = MeetingMinutes::factory()->create([
            'church_id' => $this->church->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->get(MeetingMinutesResource::getUrl('view', ['record' => $notulen]))
            ->assertOk();
    }

    // ------------------------------------------------------------------ //
    // EMBEDDED EVENT LINK
    // ------------------------------------------------------------------ //

    public function test_notulen_can_be_linked_to_event(): void
    {
        $category = EventCategory::factory()->create(['church_id' => $this->church->id]);
        $event = Event::factory()->create([
            'church_id' => $this->church->id,
            'category_id' => $category->id,
        ]);

        $notulen = MeetingMinutes::create([
            'church_id' => $this->church->id,
            'event_id' => $event->id,
            'title' => 'Notulen Rapat Ibadah Paskah',
            'meeting_date' => now()->toDateString(),
            'agenda' => [],
            'participants' => [],
            'notes' => '',
            'decisions' => [],
        ]);

        $this->assertDatabaseHas('meeting_minutes', [
            'title' => 'Notulen Rapat Ibadah Paskah',
            'event_id' => $event->id,
        ]);
        $this->assertEquals($event->id, $notulen->event_id);
    }

    // ------------------------------------------------------------------ //
    // SOFT DELETE
    // ------------------------------------------------------------------ //

    public function test_soft_delete_notulen(): void
    {
        $notulen = MeetingMinutes::factory()->create([
            'title' => 'Notulen Dihapus',
            'church_id' => $this->church->id,
        ]);

        $notulen->delete();

        $this->assertSoftDeleted('meeting_minutes', ['id' => $notulen->id]);
    }

    // ------------------------------------------------------------------ //
    // TENANT ISOLATION
    // ------------------------------------------------------------------ //

    public function test_tenant_isolation_notulen(): void
    {
        $otherChurch = Church::factory()->create();
        MeetingMinutes::factory()->create([
            'title' => 'Notulen Gereja Lain',
            'church_id' => $otherChurch->id,
        ]);

        $this->actingAs($this->churchAdmin)
            ->get(MeetingMinutesResource::getUrl('index'))
            ->assertOk()
            ->assertDontSee('Notulen Gereja Lain');
    }

    // ------------------------------------------------------------------ //
    // RBAC
    // ------------------------------------------------------------------ //

    public function test_report_viewer_can_access_notulen_list(): void
    {
        $viewer = User::factory()->create([
            'role' => 'report_viewer',
            'church_id' => $this->church->id,
        ]);

        $this->actingAs($viewer)
            ->get(MeetingMinutesResource::getUrl('index'))
            ->assertOk();
    }

    public function test_audit_trail_recorded_on_create(): void
    {
        $this->actingAs($this->superAdmin);

        $notulen = MeetingMinutes::create([
            'church_id' => $this->church->id,
            'title' => 'Notulen Audit Trail Test',
            'meeting_date' => now()->toDateString(),
            'agenda' => [],
            'participants' => [],
            'notes' => '',
            'decisions' => [],
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => MeetingMinutes::class,
            'auditable_id' => $notulen->id,
            'action' => 'created',
        ]);
    }
}
