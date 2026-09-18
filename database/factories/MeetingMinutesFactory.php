<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Church;
use App\Models\MeetingMinutes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingMinutes>
 */
class MeetingMinutesFactory extends Factory
{
    protected $model = MeetingMinutes::class;

    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'event_id' => null,
            'title' => $this->faker->sentence(4),
            'meeting_date' => $this->faker->date(),
            'agenda' => ['Pembahasan Evaluasi Pelayanan', 'Laporan Keuangan Bulanan'],
            'participants' => ['Pdt. Samuel', 'Pnt. Maria', 'Dkn. Lukas'],
            'notes' => $this->faker->paragraph(),
            'decisions' => ['Program disetujui bersama', 'Jadwal kunjungan ditetapkan'],
            'attachments' => [],
        ];
    }
}
