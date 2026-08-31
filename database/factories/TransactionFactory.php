<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Church;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    private ?int $cachedChurchId = null;

    /**
     * Church id yang sama untuk transaksi, fund, dan kategori (memoized per instance).
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
        return [
            // Urutan penting: church_id dulu, lalu fund/kategori memakai church yang sama.
            'church_id' => fn (): int => $this->churchId(),
            'fund_id' => function (array $attributes) {
                $churchId = $this->resolveChurchId($attributes);

                return Fund::factory()->create([
                    'church_id' => $churchId,
                ])->id;
            },
            'category_id' => function (array $attributes) {
                $churchId = $this->resolveChurchId($attributes);

                return FinancialCategory::factory()->create([
                    'church_id' => $churchId,
                ])->id;
            },
            'type' => $this->faker->randomElement(['debit', 'credit']),
            'amount' => $this->faker->numberBetween(10000, 10000000),
            'transaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'description' => $this->faker->sentence(),
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'debit',
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'credit',
        ]);
    }
}
