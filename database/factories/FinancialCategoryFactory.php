<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Church;
use App\Models\FinancialCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialCategory>
 */
class FinancialCategoryFactory extends Factory
{
    protected $model = FinancialCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'name' => $this->faker->word(),
            'type' => $this->faker->randomElement(['in', 'out']),
        ];
    }

    public function income(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'in',
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'out',
        ]);
    }
}
