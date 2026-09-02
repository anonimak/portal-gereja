<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Marriage;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Marriage>
 */
class MarriageFactory extends Factory
{
    protected $model = Marriage::class;

    private ?int $cachedHusbandChurchId = null;

    /**
     * Church id yang sama untuk marriage dan pasangannya (memoized per instance).
     *
     * Pola sama seperti BirthRecordFactory/EventAttendanceFactory: atribut Model
     * sudah dikonversi menjadi integer SEBELUM closure definition dieksekusi,
     * jadi husband_member_id INT harus di-resolve ke church_id via query.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function recordChurchId(array $attributes): ?int
    {
        $husband = $attributes['husband_member_id'] ?? null;

        if ($husband instanceof Member) {
            return $husband->church_id;
        }
        if (is_numeric($husband)) {
            return Member::query()
                ->withoutGlobalScopes()
                ->find((int) $husband)
                ?->church_id;
        }

        return $this->cachedHusbandChurchId;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'husband_member_id' => function (): int {
                $husband = Member::factory()->create(['gender' => 'm']);
                $this->cachedHusbandChurchId = $husband->church_id;

                return $husband->id;
            },
            'wife_member_id' => function (array $attributes): int {
                $wife = Member::factory()->create([
                    'gender' => 'f',
                    'church_id' => $this->recordChurchId($attributes) ?? $this->cachedHusbandChurchId,
                ]);

                return $wife->id;
            },
            'church_id' => fn (array $attributes): ?int => $this->recordChurchId($attributes),
            'marriage_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'official_id' => null,
            'location' => $this->faker->city(),
            'witness_names' => [],
            'program_id' => null,
            'certificate_number' => null,
            'issued_at' => null,
            'notes' => null,
        ];
    }
}
