<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Church;
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

    private ?int $cachedEventChurchId = null;

    /**
     * Church id yang sama untuk event, roster, member, dan role (memoized per instance).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function rosterChurchId(array $attributes): ?int
    {
        if ($this->cachedEventChurchId !== null) {
            return $this->cachedEventChurchId;
        }

        $value = $attributes['church_id'] ?? null;
        if ($value instanceof Church || $value instanceof Event) {
            return $value->church_id;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Urutan penting: event dulu (mengisi church event), lalu entitas lain
            // memakai church yang sama dengan event.
            'event_id' => function (): int {
                $event = Event::factory()->create();
                $this->cachedEventChurchId = $event->church_id;

                return $event->id;
            },
            'church_id' => fn (array $attributes): ?int => $this->rosterChurchId($attributes),
            'member_id' => function (array $attributes) {
                $churchId = $this->rosterChurchId($attributes);

                return Member::factory()->create(['church_id' => $churchId])->id;
            },
            'role_id' => function (array $attributes) {
                $churchId = $this->rosterChurchId($attributes);

                return MinistryRole::factory()->create(['church_id' => $churchId])->id;
            },
            'official_id' => null,
        ];
    }
}
