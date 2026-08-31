<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventAttendance>
 */
class EventAttendanceFactory extends Factory
{
    protected $model = EventAttendance::class;

    private ?int $cachedEventChurchId = null;

    /**
     * Church id yang sama untuk attendance, event, dan member (memoized per instance).
     *
     * Catatan penting (Laravel Factory): atribut bertipe Model (mis. Event instance)
     * sudah dikonversi menjadi integer (getKey()) SEBELUM closure definition dieksekusi,
     * jadi event_id berupa INT harus di-resolve ke church_id via query ke DB
     * (withoutGlobalScopes agar tidak terpengaruh scope church aktor), atau memakai
     * cache bila event dibuat oleh factory ini sendiri. Pola sama seperti EventRosterFactory.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function attendanceChurchId(array $attributes): ?int
    {
        $event = $attributes['event_id'] ?? null;

        if ($event instanceof Event) {
            return $event->church_id;
        }
        if (is_numeric($event)) {
            return Event::query()
                ->withoutGlobalScopes()
                ->find((int) $event)
                ?->church_id;
        }

        return $this->cachedEventChurchId;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Urutan penting: event dulu (mengisi church event), lalu member & church_id
            // memakai church yang sama dengan event.
            'event_id' => function (): int {
                $event = Event::factory()->create();
                $this->cachedEventChurchId = $event->church_id;

                return $event->id;
            },
            'church_id' => fn (array $attributes): ?int => $this->attendanceChurchId($attributes),
            'member_id' => function (array $attributes) {
                $churchId = $this->attendanceChurchId($attributes);

                return Member::factory()->create(['church_id' => $churchId])->id;
            },
            'status' => $this->faker->randomElement(['hadir', 'tidak_hadir']),
            'checked_in_at' => now(),
            'checked_in_by' => null,
            'notes' => null,
        ];
    }

    public function present(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'hadir',
        ]);
    }

    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'tidak_hadir',
        ]);
    }
}
