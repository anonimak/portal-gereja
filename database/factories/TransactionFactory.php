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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'church_id' => fn (): int => $this->churchId(),
            'fund_id' => fn (): int => Fund::factory()->create([
                'church_id' => $this->churchId(),
            ])->id,
            'category_id' => fn (): int => FinancialCategory::factory()->create([
                'church_id' => $this->churchId(),
            ])->id,
            'type' => $this->faker->randomElement(['debit', 'credit']),
            'amount' => $this->faker->numberBetween(10000, 10000000),
            'transaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'description' => $this->faker->sentence(),
        ];
    }

    public function income(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'debit',
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'credit',
        ]);
    }
}
