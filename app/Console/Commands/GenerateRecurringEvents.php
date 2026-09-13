<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RecurringSchedule;
use App\Services\RecurringEventService;
use Illuminate\Console\Command;

class GenerateRecurringEvents extends Command
{
    protected $signature = 'events:generate-recurring
                            {--church= : ID gereja spesifik (opsional)}
                            {--days=30 : Batas hari ke depan untuk digenerate (default: 30 hari)}
                            {--max=52 : Batas maksimal event per jadwal (default: 52)}';

    protected $description = 'Generate acara (Event) dari template jadwal berulang (RecurringSchedule)';

    public function handle(RecurringEventService $service): int
    {
        $churchId = $this->option('church');
        $days = (int) $this->option('days');
        $maxLimit = (int) $this->option('max');

        $query = RecurringSchedule::query()->where('is_active', true);

        if ($churchId) {
            $query->where('church_id', (int) $churchId);
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->info('Tidak ada jadwal berulang aktif yang ditemukan.');

            return self::SUCCESS;
        }

        $until = now()->addDays($days);
        $totalCreated = 0;

        foreach ($schedules as $schedule) {
            $created = $service->generateEvents($schedule, $until, $maxLimit);
            $totalCreated += $created->count();
            $this->line(sprintf('Jadwal "%s" (Gereja #%d): %d acara baru digenerate.', $schedule->title, $schedule->church_id, $created->count()));
        }

        $this->info(sprintf('Selesai! Total %d acara baru berhasil digenerate dari %d jadwal berulang.', $totalCreated, $schedules->count()));

        return self::SUCCESS;
    }
}
