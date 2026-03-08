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

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $church = Church::factory();

        return [
            'church_id' => $church,
            'fund_id' => Fund::factory()->state(['church_id' => $church]),
            'category_id' => FinancialCategory::factory()->state(['church_id' => $church]),
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
