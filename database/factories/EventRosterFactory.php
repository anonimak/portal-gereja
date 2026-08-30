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
        return [
            // Semua entitas dalam gereja yang SAMA dengan event (cegah silang-gereja).
            'event_id' => Event::factory(),
            'church_id' => function (array $attributes) {
                return $attributes['event_id'] instanceof Event
                    ? $attributes['event_id']->church_id
                    : $attributes['event_id'];
            },
            'member_id' => function (array $attributes) {
                $churchId = $attributes['church_id'] instanceof \App\Models\Church
                    ? $attributes['church_id']->id
                    : $attributes['church_id'];

                return Member::factory()->create(['church_id' => $churchId])->id;
            },
            'role_id' => function (array $attributes) {
                $churchId = $attributes['church_id'] instanceof \App\Models\Church
                    ? $attributes['church_id']->id
                    : $attributes['church_id'];

                return MinistryRole::factory()->create(['church_id' => $churchId])->id;
            },
            'official_id' => null,
        ];
    }
}
