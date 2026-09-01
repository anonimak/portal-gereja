<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BirthRecord;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BirthRecord>
 */
class BirthRecordFactory extends Factory
{
    protected $model = BirthRecord::class;

    private ?int $cachedMemberChurchId = null;

    /**
     * Church id yang sama untuk birth record dan member-nya (memoized per instance).
     *
     * Pola sama seperti EventAttendanceFactory: atribut Model sudah dikonversi
     * menjadi integer SEBELUM closure definition dieksekusi, jadi member_id INT
     * harus di-resolve ke church_id via query (withoutGlobalScopes).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function recordChurchId(array $attributes): ?int
    {
        $member = $attributes['member_id'] ?? null;

        if ($member instanceof Member) {
            return $member->church_id;
        }
        if (is_numeric($member)) {
            return Member::query()
                ->withoutGlobalScopes()
                ->find((int) $member)
                ?->church_id;
        }

        return $this->cachedMemberChurchId;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => function (): int {
                $member = Member::factory()->create();
                $this->cachedMemberChurchId = $member->church_id;

                return $member->id;
            },
            'church_id' => fn (array $attributes): ?int => $this->recordChurchId($attributes),
            'birth_order' => $this->faker->numberBetween(1, 8),
            'birth_place_full' => $this->faker->city(),
            'birth_date' => $this->faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'father_name' => $this->faker->name('male'),
            'mother_name' => $this->faker->name('female'),
            'certificate_number' => null,
            'issued_at' => null,
            'notes' => null,
        ];
    }
}
