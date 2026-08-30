<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Church;
use App\Models\Event;
use App\Models\EventRoster;
use App\Models\Member;
use App\Models\MinistryRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventRoster>
 */
class EventRosterFactory extends Factory
{
    protected $model = EventRoster::class;

    private ?int $cachedEventChurchId = null;

    /**
     * Church id yang sama untuk event, roster, member, dan role (memoized per instance).
     *
     * Catatan penting (Laravel Factory): atribut bertipe Model (mis. Event instance)
     * sudah dikonversi menjadi integer (getKey()) SEBELUM closure definition dieksekusi.
     * Karena itu event_id berupa INT harus di-resolve ke church_id via query ke DB
     * (pakai withoutGlobalScopes agar tidak terpengaruh scope church aktor), atau
     * memakai cache bila event dibuat oleh factory ini sendiri.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function rosterChurchId(array $attributes): ?int
    {
        $church = $attributes['church_id'] ?? null;
        $event = $attributes['event_id'] ?? null;

        // 1. church_id eksplisit menang (instance Church atau int).
        if ($church instanceof Church) {
            return $church->id;
        }
        if (is_numeric($church)) {
            return (int) $church;
        }

        // 2. event_id eksplisit: resolve church dari event terkait.
        if ($event instanceof Event) {
            return $event->church_id;
        }
        if (is_numeric($event)) {
            return Event::query()
                ->withoutGlobalScopes()
                ->find((int) $event)
                ?->church_id;
        }

        // 3. Fallback: event dibuat oleh factory ini (event_id closure).
        return $this->cachedEventChurchId;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Urutan penting: event dulu (mengisi church event), lalu entitas lain
            // memakai church yang sama dengan event.
            'event_id' => function (): int {
                $event = Event::factory()->create();
                $this->cachedEventChurchId = $event->church_id;

                return $event->id;
            },
            'church_id' => fn (array $attributes): ?int => $this->rosterChurchId($attributes),
            'member_id' => function (array $attributes) {
                $churchId = $this->rosterChurchId($attributes);

                return Member::factory()->create(['church_id' => $churchId])->id;
            },
            'role_id' => function (array $attributes) {
                $churchId = $this->rosterChurchId($attributes);

                return MinistryRole::factory()->create(['church_id' => $churchId])->id;
            },
            'official_id' => null,
        ];
    }
}
