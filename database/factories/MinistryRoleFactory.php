<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Church;
use App\Models\MinistryRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MinistryRole>
 */
class MinistryRoleFactory extends Factory
{
    protected $model = MinistryRole::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'name' => $this->faker->word(),
        ];
    }
}
