<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GuidanceSession;
use App\Models\GuidanceSessionMember;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuidanceSessionMember>
 */
class GuidanceSessionMemberFactory extends Factory
{
    protected $model = GuidanceSessionMember::class;

    private ?int $cachedSessionChurchId = null;

    private function pivotChurchId(array $attributes): ?int
    {
        $session = $attributes['session_id'] ?? null;
        if ($session instanceof GuidanceSession) {
            return $session->church_id;
        }
        if (is_numeric($session)) {
            return GuidanceSession::query()
                ->withoutGlobalScopes()
                ->find((int) $session)
                ?->church_id;
        }

        return $this->cachedSessionChurchId;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => function (): int {
                $session = GuidanceSession::factory()->create();
                $this->cachedSessionChurchId = $session->church_id;

                return $session->id;
            },
            'church_id' => fn (array $attributes): ?int => $this->pivotChurchId($attributes),
            'member_id' => function (array $attributes) {
                $churchId = $this->pivotChurchId($attributes);

                return Member::factory()->create(['church_id' => $churchId])->id;
            },
            'attended' => false,
            'notes' => null,
        ];
    }
}
