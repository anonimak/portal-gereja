<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\RecurringSchedule;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RecurringEventService
{
    /**
     * Peta konversi nama hari (Inggris/Indonesia) ke indeks Carbon (0 = Sunday ... 6 = Saturday).
     */
    private const DAY_MAP = [
        'sunday' => 0,
        'sun' => 0,
        'minggu' => 0,
        'monday' => 1,
        'mon' => 1,
        'senin' => 1,
        'tuesday' => 2,
        'tue' => 2,
        'selasa' => 2,
        'wednesday' => 3,
        'wed' => 3,
        'rabu' => 3,
        'thursday' => 4,
        'thu' => 4,
        'kamis' => 4,
        'friday' => 5,
        'fri' => 5,
        'jumat' => 5,
        'jum\'at' => 5,
        'saturday' => 6,
        'sat' => 6,
        'sabtu' => 6,
    ];

    /**
     * Validasi input jadwal berulang.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateSchedule(array $data): array
    {
        return Validator::make($data, [
            'church_id' => ['nullable', 'integer', Rule::exists('churches', 'id')],
            'category_id' => ['required', 'integer', Rule::exists('event_categories', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'frequency' => ['required', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
            'interval' => ['required', 'integer', 'min:1', 'max:365'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['nullable'],
            'start_date' => ['required', 'date'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'end_type' => ['required', 'string', Rule::in(['until_date', 'count', 'never'])],
            'until_date' => ['required_if:end_type,until_date', 'nullable', 'date', 'after_or_equal:start_date'],
            'repeat_count' => ['required_if:end_type,count', 'nullable', 'integer', 'min:1', 'max:365'],
            'is_active' => ['nullable', 'boolean'],
        ])->validate();
    }

    /**
     * Normalisasi hari berulang ke array integer 0-6 (0 = Minggu).
     *
     * @return array<int, int>
     */
    public function normalizeDaysOfWeek(mixed $days, int $defaultDay = 0): array
    {
        if (empty($days)) {
            return [$defaultDay];
        }

        if (! is_array($days)) {
            $days = [$days];
        }

        $normalized = [];

        foreach ($days as $day) {
            if (is_numeric($day)) {
                $val = (int) $day;
                if ($val >= 0 && $val <= 6) {
                    $normalized[] = $val;
                }
            } elseif (is_string($day)) {
                $lower = strtolower(trim($day));
                if (isset(self::DAY_MAP[$lower])) {
                    $normalized[] = self::DAY_MAP[$lower];
                }
            }
        }

        $result = array_values(array_unique($normalized));
        sort($result);

        return empty($result) ? [$defaultDay] : $result;
    }

    /**
     * Hitung jadwal kemunculan (occurrences) tanggal & jam untuk sebuah template jadwal berulang.
     *
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    public function calculateOccurrences(RecurringSchedule $schedule, ?CarbonInterface $until = null, int $maxLimit = 52): array
    {
        $startDate = Carbon::parse($schedule->start_date)->startOfDay();
        $startTime = Carbon::parse($schedule->start_time)->format('H:i:s');
        $endTime = Carbon::parse($schedule->end_time)->format('H:i:s');
        $interval = max(1, (int) $schedule->interval);

        $untilLimit = null;
        if ($schedule->end_type === 'until_date' && $schedule->until_date) {
            $untilLimit = Carbon::parse($schedule->until_date)->endOfDay();
        }

        if ($until !== null) {
            $externalUntil = Carbon::parse($until)->endOfDay();
            $untilLimit = $untilLimit ? ($externalUntil->lt($untilLimit) ? $externalUntil : $untilLimit) : $externalUntil;
        }

        $countLimit = ($schedule->end_type === 'count' && $schedule->repeat_count)
            ? (int) $schedule->repeat_count
            : $maxLimit;

        $countLimit = min($countLimit, $maxLimit);

        $occurrences = [];

        switch ($schedule->frequency) {
            case 'daily':
                $curr = $startDate->copy();
                while (count($occurrences) < $countLimit) {
                    if ($untilLimit && $curr->gt($untilLimit)) {
                        break;
                    }

                    $this->addOccurrence($occurrences, $curr, $startTime, $endTime);
                    $curr->addDays($interval);
                }
                break;

            case 'weekly':
                $targetDays = $this->normalizeDaysOfWeek($schedule->days_of_week, $startDate->dayOfWeek);
                $weekStart = $startDate->copy()->startOfWeek(Carbon::SUNDAY);

                while (count($occurrences) < $countLimit) {
                    $addedInWeek = false;

                    foreach ($targetDays as $day) {
                        $curr = $weekStart->copy()->addDays($day);

                        // Abaikan hari sebelum tanggal mulai
                        if ($curr->lt($startDate)) {
                            continue;
                        }

                        if ($untilLimit && $curr->gt($untilLimit)) {
                            break 2; // Lewati batas akhir
                        }

                        $this->addOccurrence($occurrences, $curr, $startTime, $endTime);
                        $addedInWeek = true;

                        if (count($occurrences) >= $countLimit) {
                            break 2;
                        }
                    }

                    $weekStart->addWeeks($interval);

                    // Pengaman jika weekStart sudah melewati batas akhir
                    if ($untilLimit && $weekStart->gt($untilLimit)) {
                        break;
                    }
                }
                break;

            case 'monthly':
                $monthIndex = 0;
                while (count($occurrences) < $countLimit) {
                    $curr = $startDate->copy()->addMonthsNoOverflow($monthIndex * $interval);

                    if ($untilLimit && $curr->gt($untilLimit)) {
                        break;
                    }

                    $this->addOccurrence($occurrences, $curr, $startTime, $endTime);
                    $monthIndex++;
                }
                break;
        }

        return $occurrences;
    }

    /**
     * Tambahkan pasangan start & end datetime ke daftar kemunculan.
     *
     * @param  array<int, array{start: Carbon, end: Carbon}>  $occurrences
     */
    private function addOccurrence(array &$occurrences, Carbon $date, string $startTime, string $endTime): void
    {
        $start = Carbon::parse($date->format('Y-m-d').' '.$startTime);
        $end = Carbon::parse($date->format('Y-m-d').' '.$endTime);

        // Bila jam selesai lebih awal dari jam mulai, berarti selesai di hari berikutnya
        if ($end->lte($start)) {
            $end->addDay();
        }

        $occurrences[] = [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Generate acara nyata (Event) dari jadwal berulang.
     *
     * Bersifat idempoten: tidak membuat duplikat jika acara untuk jadwal
     * dan start_datetime ini sudah pernah digenerate sebelumnya.
     *
     * @return Collection<int, Event>
     */
    public function generateEvents(RecurringSchedule $schedule, ?CarbonInterface $until = null, int $maxLimit = 52): Collection
    {
        $occurrences = $this->calculateOccurrences($schedule, $until, $maxLimit);
        $created = collect();

        foreach ($occurrences as $occ) {
            $exists = Event::withTrashed()
                ->where('recurring_schedule_id', $schedule->id)
                ->where('start_datetime', $occ['start'])
                ->exists();

            if ($exists) {
                continue;
            }

            $event = Event::create([
                'church_id' => $schedule->church_id,
                'category_id' => $schedule->category_id,
                'recurring_schedule_id' => $schedule->id,
                'title' => $schedule->title,
                'location' => $schedule->location,
                'start_datetime' => $occ['start'],
                'end_datetime' => $occ['end'],
                'attendance_male' => 0,
                'attendance_female' => 0,
            ]);

            $created->push($event);
        }

        $schedule->forceFill([
            'last_generated_at' => now(),
        ])->save();

        return $created;
    }
}
