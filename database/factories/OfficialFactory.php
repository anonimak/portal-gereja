<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Church;
use App\Models\Official;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Official>
 */
class OfficialFactory extends Factory
{
    protected $model = Official::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'type' => $this->faker->randomElement(['majelis_lokal', 'pendeta_internal', 'pelayan_tamu']),
            'member_id' => null,
            'external_name' => $this->faker->name(),
            'origin_church' => null,
            'start_date' => now()->subYear(),
            'end_date' => null,
        ];
    }

    public function guestMinister(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'pelayan_tamu',
            'origin_church' => 'Gereja ' . $this->faker->word(),
        ]);
    }
}
