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

    private ?int $cachedMemberChurchId = null;

    /**
     * Church id yang sama untuk sakramen dan member-nya (memoized per instance).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function sacramentChurchId(array $attributes): ?int
    {
        if ($this->cachedMemberChurchId !== null) {
            return $this->cachedMemberChurchId;
        }

        $value = $attributes['member_id'] ?? null;
        if ($value instanceof Member) {
            return $value->church_id;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Urutan penting: member dulu (mengisi church member), lalu church_id
            // memakai church yang sama dengan member.
            'member_id' => function (): int {
                $member = Member::factory()->create();
                $this->cachedMemberChurchId = $member->church_id;

                return $member->id;
            },
            'church_id' => fn (array $attributes): ?int => $this->sacramentChurchId($attributes),
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
