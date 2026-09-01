<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Church;
use App\Models\GuidanceProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuidanceProgram>
 */
class GuidanceProgramFactory extends Factory
{
    protected $model = GuidanceProgram::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'type' => $this->faker->randomElement(['pra_sidi', 'pra_nikah']),
            'title' => $this->faker->sentence(3),
            'start_date' => null,
            'end_date' => null,
            'status' => 'draft',
            'template_id' => null,
            'notes' => null,
        ];
    }

    public function praSidi(): static
    {
        return $this->state(fn () => ['type' => 'pra_sidi', 'title' => 'Katakisasi Angkatan '.now()->year]);
    }

    public function praNikah(): static
    {
        return $this->state(fn () => ['type' => 'pra_nikah', 'title' => 'Bimbingan Pernikahan Angkatan '.now()->year]);
    }
}
