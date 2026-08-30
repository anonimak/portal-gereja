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
     * Catatan penting (Laravel Factory): atribut bertipe Model (mis. Member instance)
     * sudah dikonversi menjadi integer (getKey()) SEBELUM closure definition dieksekusi,
     * jadi member_id berupa INT harus di-resolve ke church_id via query ke DB
     * (withoutGlobalScopes agar tidak terpengaruh scope church aktor).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function sacramentChurchId(array $attributes): ?int
    {
        $member = $attributes['member_id'] ?? null;

        // 1. member_id eksplisit: resolve church dari member terkait.
        if ($member instanceof Member) {
            return $member->church_id;
        }
        if (is_numeric($member)) {
            return Member::query()
                ->withoutGlobalScopes()
                ->find((int) $member)
                ?->church_id;
        }

        // 2. Fallback: member dibuat oleh factory ini (member_id closure).
        return $this->cachedMemberChurchId;
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
        return $this->state(fn (array $attributes) => [
            'type' => 'baptis_anak',
        ]);
    }

    public function confirmation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'sidi',
        ]);
    }
}
