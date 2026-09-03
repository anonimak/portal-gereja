<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Church;
use App\Models\Event;
use App\Models\User;
use App\Notifications\WorshipScheduleNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Kirim notifikasi email jadwal ibadah 7 hari ke depan ke church_admin tiap gereja.
 *
 * Terjadwal via routes/console.php: `warta:notify-schedule` dailyAt 05:30.
 */
class NotifyWorshipSchedule extends Command
{
    protected $signature = 'warta:notify-schedule';

    protected $description = 'Kirim email jadwal ibadah/acara gereja 7 hari ke depan ke church_admin';

    public function handle(): int
    {
        $start = now();
        $end = $start->copy()->addDays(7);

        $events = Event::query()
            ->select('id', 'church_id', 'title', 'start_datetime', 'location')
            ->whereBetween('start_datetime', [$start, $end])
            ->orderBy('start_datetime')
            ->get()
            ->groupBy('church_id');

        if ($events->isEmpty()) {
            $this->info('Tidak ada jadwal ibadah 7 hari ke depan.');

            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($events as $churchId => $churchEvents) {
            $church = Church::query()->find($churchId);

            if ($church === null) {
                continue;
            }

            $admins = User::query()
                ->where('church_id', $churchId)
                ->where('role', 'church_admin')
                ->get();

            if ($admins->isEmpty()) {
                continue;
            }

            $schedule = $churchEvents
                ->map(fn (Event $event): array => [
                    'title' => $event->title,
                    'start' => $event->start_datetime?->format('D, d M Y H:i'),
                    'location' => $event->location,
                ])
                ->values()
                ->all();

            try {
                Notification::send($admins, new WorshipScheduleNotification($church, $schedule));
                $sent += $admins->count();
            } catch (\Throwable $e) {
                Log::warning('Gagal mengirim notifikasi jadwal ibadah', [
                    'church_id' => $churchId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Notifikasi jadwal ibadah terkirim ke {$sent} admin.");

        return self::SUCCESS;
    }
}
