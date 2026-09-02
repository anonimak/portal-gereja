<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Church;
use App\Models\DeathRecord;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory DeathRecord — church_id konsisten dengan member (pola Fase 3B).
 */
class DeathRecordFactory extends Factory
{
    protected $model = DeathRecord::class;

    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'member_id' => Member::factory(),
            'death_date' => $this->faker->date(),
            'certificate_number' => 'SKM-' . $this->faker->unique()->numerify('####'),
        ];
    }
}
