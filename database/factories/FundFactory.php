<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Church;
use App\Models\Fund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fund>
 */
class FundFactory extends Factory
{
    protected $model = Fund::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'name' => $this->faker->word() . ' Fund',
        ];
    }
}
