<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Church;
use App\Models\User;
use App\Models\WartaPublication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WartaPublication>
 */
class WartaPublicationFactory extends Factory
{
    protected $model = WartaPublication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'title' => 'Warta Jemaat '.fake()->date('d M Y'),
            'period_start' => now()->startOfWeek(),
            'period_end' => now()->endOfWeek(),
            'content' => [
                'church_name' => 'Gereja Contoh',
                'events' => [],
                'birthdays' => [],
                'sacraments' => [],
                'finance' => [
                    'opening_balance' => 0,
                    'total_income' => 0,
                    'total_expenses' => 0,
                    'closing_balance' => 0,
                ],
            ],
            'status' => 'published',
            'published_at' => now(),
            'created_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function scheduled(?string $futureDate = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => $futureDate ?: now()->addDay(),
        ]);
    }
}
