<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Church;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    private ?int $cachedChurchId = null;

    /**
     * Church id yang sama untuk seluruh entitas dalam satu factory instance
     * (memoized) — mencegah silang-gereja antar atribut.
     */
    private function churchId(): int
    {
        return $this->cachedChurchId ??= Church::factory()->create()->id;
    }

    /**
     * Resolve church id: prioritaskan church_id eksplisit (int / instance Church),
     * fallback ke memoized churchId().
     *
     * @param  array<string, mixed>  $attributes
     */
    private function resolveChurchId(array $attributes): int
    {
        $church = $attributes['church_id'] ?? null;

        if ($church instanceof Church) {
            return $church->id;
        }
        if (is_numeric($church)) {
            return (int) $church;
        }

        return $this->churchId();
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDateTime = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $endDateTime = (clone $startDateTime)->modify('+3 hours');

        return [
            // Urutan penting: church_id dulu, lalu kategori memakai church yang sama.
            'church_id' => fn (): int => $this->churchId(),
            'category_id' => function (array $attributes) {
                $churchId = $this->resolveChurchId($attributes);

                return EventCategory::factory()->create([
                    'church_id' => $churchId,
                ])->id;
            },
            'title' => $this->faker->sentence(3),
            'start_datetime' => $startDateTime,
            // End dihitung dari START (bukan relatif ke now) — hindari start > end.
            'end_datetime' => $endDateTime,
            'location' => $this->faker->address(),
        ];
    }
}
