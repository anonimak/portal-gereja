<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Clusters\Events\Resources\Event\Pages\EditEvent;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventRoster;
use App\Models\Member;
use App\Models\MinistryRole;
use App\Models\Official;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventEditRosterTest extends TestCase
{
    use RefreshDatabase;

    private Church $church;
    private User $admin;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::factory()->create(['name' => 'GKSBS Candimas']);

        $this->admin = User::factory()->create([
            'church_id' => $this->church->id,
            'role' => 'church_admin',
        ]);

        $category = EventCategory::factory()->create([
            'church_id' => $this->church->id,
            'name' => 'Ibadah Raya',
        ]);

        $this->event = Event::factory()->create([
            'church_id' => $this->church->id,
            'category_id' => $category->id,
            'title' => 'Ibadah Minggu Pagi',
            'start_datetime' => now()->addDays(2),
            'end_datetime' => now()->addDays(2)->addHours(2),
        ]);

        $member = Member::factory()->create([
            'church_id' => $this->church->id,
            'full_name' => 'Yohanes Penatua',
        ]);

        $official = Official::factory()->create([
            'church_id' => $this->church->id,
            'external_name' => 'Pdt. Andreas',
            'type' => 'pelayan_tamu',
        ]);

        $role = MinistryRole::factory()->create([
            'church_id' => $this->church->id,
            'name' => 'Pengkhotbah',
        ]);

        // Roster 1: Member
        EventRoster::create([
            'church_id' => $this->church->id,
            'event_id' => $this->event->id,
            'member_id' => $member->id,
            'role_id' => $role->id,
        ]);

        // Roster 2: Official
        EventRoster::create([
            'church_id' => $this->church->id,
            'event_id' => $this->event->id,
            'official_id' => $official->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_admin_can_load_event_edit_page_with_rosters_without_type_error(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(EditEvent::class, ['record' => $this->event->getKey()])
            ->assertSuccessful()
            ->assertSchemaStateSet([
                'title' => 'Ibadah Minggu Pagi',
            ]);
    }
}
