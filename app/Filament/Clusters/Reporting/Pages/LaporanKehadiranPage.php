<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Reporting\Pages;

use App\Models\Event;

class LaporanKehadiranPage extends BaseReportPage
{
    protected string $view = 'filament.pages.laporan-kehadiran';

    protected static ?string $navigationLabel = 'Laporan Kehadiran';

    protected static ?string $title = 'Laporan Kehadiran Ibadah';

    protected static ?int $navigationSort = 4;

    protected static function allowedRoles(): array
    {
        // Matriks §1.1: super_admin, church_admin, report_viewer (view).
        return ['super_admin', 'church_admin', 'report_viewer'];
    }

    public ?int $eventId = null;

    public ?string $month = null;

    public function mount(): void
    {
        parent::mount();
        $this->month = now()->format('Y-m');
    }

    protected function reportTitle(): string
    {
        return 'Laporan-Kehadiran-'.($this->month ?: now()->format('Y-m'));
    }

    public function getReportData(): array
    {
        $month = $this->month ?: now()->format('Y-m');
        $start = \Illuminate\Support\Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $events = Event::query()
            ->with(['attendances' => fn ($q) => $q->with('member')->whereBetween('created_at', [$start, $end])])
            ->when($this->eventId, fn ($q) => $q->whereKey($this->eventId))
            ->whereBetween('start_datetime', [$start, $end])
            ->orderBy('start_datetime')
            ->get();

        return [
            'churchName' => $this->activeChurchName(),
            'events' => $events,
            'startDate' => $start,
            'endDate' => $end,
        ];
    }

    protected function exportBlocks(): array
    {
        $data = $this->getReportData();
        $blocks = [];

        $eventRows = $data['events']->map(function ($event) {
            $hadir = $event->attendances->where('status', 'hadir')->count();
            $tidak = $event->attendances->where('status', 'tidak_hadir')->count();

            return [
                $event->start_datetime?->format('d/m/Y H:i'),
                $event->name,
                $hadir,
                $tidak,
                $event->total_attendance,
            ];
        })->all();

        $blocks[] = [
            'title' => 'Per Acara',
            'headers' => ['Waktu', 'Acara', 'Hadir', 'Tidak Hadir', 'Total Kehadiran'],
            'rows' => $eventRows,
        ];

        $memberRows = $data['events']
            ->flatMap(fn ($e) => $e->attendances)
            ->groupBy(fn ($a) => $a->member_id)
            ->map(function ($group, $memberId) {
                $member = $group->first()?->member;
                $hadir = $group->where('status', 'hadir')->count();
                $total = $group->count();

                return [
                    $member?->full_name ?? 'Member #'.$memberId,
                    $hadir,
                    $total - $hadir,
                    $total > 0 ? round(($hadir / $total) * 100, 1).'%' : '0%',
                ];
            })
            ->values()
            ->all();

        $blocks[] = [
            'title' => 'Rekap per Anggota',
            'headers' => ['Nama', 'Hadir', 'Tidak Hadir', 'Persentase'],
            'rows' => $memberRows,
        ];

        return $blocks;
    }
}
