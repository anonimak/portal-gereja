<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Church;
use App\Models\GuidanceTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuidanceTemplate>
 */
class GuidanceTemplateFactory extends Factory
{
    protected $model = GuidanceTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'type' => $this->faker->randomElement(['pra_sidi', 'pra_nikah']),
            'name' => $this->faker->sentence(3),
            'session_count' => 12,
            'is_default' => false,
            'notes' => null,
        ];
    }

    public function praSidi(): static
    {
        return $this->state(fn () => ['type' => 'pra_sidi', 'name' => 'Template Pra-Sidi Standar (12 sesi)']);
    }

    public function praNikah(): static
    {
        return $this->state(fn () => ['type' => 'pra_nikah', 'name' => 'Template Pra-Nikah Standar (12 sesi)']);
    }
}
