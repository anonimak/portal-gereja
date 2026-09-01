<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GuidanceProgram;
use App\Models\GuidanceSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuidanceSession>
 */
class GuidanceSessionFactory extends Factory
{
    protected $model = GuidanceSession::class;

    private ?int $cachedProgramChurchId = null;

    private function sessionChurchId(array $attributes): ?int
    {
        $program = $attributes['program_id'] ?? null;
        if ($program instanceof GuidanceProgram) {
            return $program->church_id;
        }
        if (is_numeric($program)) {
            return GuidanceProgram::query()
                ->withoutGlobalScopes()
                ->find((int) $program)
                ?->church_id;
        }

        return $this->cachedProgramChurchId;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => function (): int {
                $program = GuidanceProgram::factory()->create();
                $this->cachedProgramChurchId = $program->church_id;

                return $program->id;
            },
            'church_id' => fn (array $attributes): ?int => $this->sessionChurchId($attributes),
            'title' => $this->faker->sentence(3),
            'session_at' => now()->addDays($this->faker->numberBetween(1, 60)),
            'location' => $this->faker->optional()->city(),
            'official_id' => null,
            'notes' => null,
        ];
    }
}
