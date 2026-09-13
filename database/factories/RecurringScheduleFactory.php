<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Church;
use App\Models\EventCategory;
use App\Models\RecurringSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringSchedule>
 */
class RecurringScheduleFactory extends Factory
{
    protected $model = RecurringSchedule::class;

    private ?int $cachedChurchId = null;

    private function churchId(): int
    {
        return $this->cachedChurchId ??= Church::factory()->create()->id;
    }

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
        $startDate = now()->startOfWeek();

        return [
            'church_id' => fn (): int => $this->churchId(),
            'category_id' => function (array $attributes): int {
                $churchId = $this->resolveChurchId($attributes);

                return EventCategory::factory()->create([
                    'church_id' => $churchId,
                ])->id;
            },
            'title' => 'Ibadah Minggu Raya',
            'description' => $this->faker->sentence(),
            'location' => 'Gedung Gereja Utama',
            'frequency' => 'weekly',
            'interval' => 1,
            'days_of_week' => [0], // Minggu
            'start_date' => $startDate->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'end_type' => 'until_date',
            'until_date' => $startDate->copy()->addMonths(3)->toDateString(),
            'repeat_count' => null,
            'is_active' => true,
            'last_generated_at' => null,
        ];
    }

    public function daily(int $interval = 1): static
    {
        return $this->state(fn (): array => [
            'frequency' => 'daily',
            'interval' => $interval,
            'days_of_week' => null,
        ]);
    }

    public function weekly(array $days = [0], int $interval = 1): static
    {
        return $this->state(fn (): array => [
            'frequency' => 'weekly',
            'interval' => $interval,
            'days_of_week' => $days,
        ]);
    }

    public function monthly(int $interval = 1): static
    {
        return $this->state(fn (): array => [
            'frequency' => 'monthly',
            'interval' => $interval,
            'days_of_week' => null,
        ]);
    }

    public function repeatCount(int $count = 10): static
    {
        return $this->state(fn (): array => [
            'end_type' => 'count',
            'repeat_count' => $count,
            'until_date' => null,
        ]);
    }

    public function untilDate(string $untilDate): static
    {
        return $this->state(fn (): array => [
            'end_type' => 'until_date',
            'until_date' => $untilDate,
            'repeat_count' => null,
        ]);
    }
}
