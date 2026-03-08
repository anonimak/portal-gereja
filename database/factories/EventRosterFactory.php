<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventRoster;
use App\Models\Member;
use App\Models\MinistryRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventRoster>
 */
class EventRosterFactory extends Factory
{
    protected $model = EventRoster::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $event = Event::factory();

        return [
            'event_id' => $event,
            'member_id' => Member::factory()->state(['church_id' => $event->church_id]),
            'role_id' => MinistryRole::factory()->state(['church_id' => $event->church_id]),
        ];
    }
}
