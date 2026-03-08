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
        $church = Church::factory();
        $startDateTime = $this->faker->dateTimeBetween('-1 month', '+1 month');

        return [
            'church_id' => $church,
            'category_id' => EventCategory::factory()->state(['church_id' => $church]),
            'title' => $this->faker->sentence(3),
            'start_datetime' => $startDateTime,
            'end_datetime' => $this->faker->dateTimeBetween($startDateTime, '+3 hours'),
            'location' => $this->faker->address(),
        ];
    }
}
