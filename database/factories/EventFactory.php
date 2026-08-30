<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Church;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDateTime = $this->faker->dateTimeBetween('-1 month', '+1 month');

        return [
            // Satu church yang sama untuk event DAN kategori (cegah silang-gereja).
            'church_id' => Church::factory(),
            'category_id' => function (array $attributes) {
                $churchId = $attributes['church_id'] instanceof Church
                    ? $attributes['church_id']->id
                    : $attributes['church_id'];

                return EventCategory::factory()->create(['church_id' => $churchId])->id;
            },
            'title' => $this->faker->sentence(3),
            'start_datetime' => $startDateTime,
            'end_datetime' => $this->faker->dateTimeBetween($startDateTime, '+3 hours'),
            'location' => $this->faker->address(),
        ];
    }
}
