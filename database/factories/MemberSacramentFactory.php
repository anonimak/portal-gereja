<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberSacrament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberSacrament>
 */
class MemberSacramentFactory extends Factory
{
    protected $model = MemberSacrament::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $member = Member::factory();

        return [
            'church_id' => $member->church_id,
            'member_id' => $member,
            'type' => $this->faker->randomElement(['penyerahan', 'baptis_anak', 'sidi', 'baptis_dewasa', 'nikah']),
            'sacrament_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'official_id' => null,
            'certificate_number' => $this->faker->unique()->numerify('SERT-####-##'),
        ];
    }

    public function baptism(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'baptis_anak',
        ]);
    }

    public function confirmation(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'sidi',
        ]);
    }
}
