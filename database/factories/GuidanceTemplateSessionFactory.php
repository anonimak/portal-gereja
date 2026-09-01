<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GuidanceTemplate;
use App\Models\GuidanceTemplateSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuidanceTemplateSession>
 */
class GuidanceTemplateSessionFactory extends Factory
{
    protected $model = GuidanceTemplateSession::class;

    private ?int $cachedTemplateChurchId = null;

    private function sessionChurchId(array $attributes): ?int
    {
        $template = $attributes['template_id'] ?? null;
        if ($template instanceof GuidanceTemplate) {
            return $template->church_id;
        }
        if (is_numeric($template)) {
            return GuidanceTemplate::query()
                ->withoutGlobalScopes()
                ->find((int) $template)
                ?->church_id;
        }

        return $this->cachedTemplateChurchId;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_id' => function (): int {
                $template = GuidanceTemplate::factory()->create();
                $this->cachedTemplateChurchId = $template->church_id;

                return $template->id;
            },
            'church_id' => fn (array $attributes): ?int => $this->sessionChurchId($attributes),
            'session_number' => 1,
            'topic' => $this->faker->sentence(3),
            'notes' => null,
        ];
    }
}
