<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Family;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'church_id' => fn(array $attributes) => $attributes['family_id']->church_id,
            'id_card_number' => $this->faker->unique()->numerify('###.###.###-###'),
            'full_name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(['m', 'f']),
            'birth_place' => $this->faker->city(),
            'birth_date' => $this->faker->dateTimeBetween('-80 years', '-5 years'),
            'family_relation' => $this->faker->randomElement(['kepala_keluarga', 'istri', 'anak', 'lainnya']),
            'status' => 'aktif',
            'custom_fields' => null,
        ];
    }

    public function headOfFamily(): static
    {
        return $this->state(fn(array $attributes) => [
            'family_relation' => 'kepala_keluarga',
        ]);
    }

    public function spouse(): static
    {
        return $this->state(fn(array $attributes) => [
            'family_relation' => 'istri',
            'gender' => 'f',
        ]);
    }

    public function child(): static
    {
        return $this->state(fn(array $attributes) => [
            'family_relation' => 'anak',
        ]);
    }
}
