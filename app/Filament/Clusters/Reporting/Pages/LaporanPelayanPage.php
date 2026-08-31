<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Reporting\Pages;

use App\Models\Event;
use App\Models\Official;

class LaporanPelayanPage extends BaseReportPage
{
    protected string $view = 'filament.pages.laporan-pelayan';

    protected static ?string $navigationLabel = 'Laporan Pelayan';

    protected static ?string $title = 'Laporan Pelayan / Official';

    protected static ?int $navigationSort = 6;

    protected static function allowedRoles(): array
    {
        // Matriks §1.1: super_admin, church_admin, warta_editor (view), report_viewer (view).
        return ['super_admin', 'church_admin', 'warta_editor', 'report_viewer'];
    }

    public ?string $type = null;

    public ?string $month = null;

    public function mount(): void
    {
        parent::mount();
        $this->month = now()->format('Y-m');
    }

    protected function reportTitle(): string
    {
        return 'Laporan-Pelayan-'.($this->month ?: now()->format('Y-m'));
    }

    public function getReportData(): array
    {
        $month = $this->month ?: now()->format('Y-m');
        $start = \Illuminate\Support\Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $officials = Official::query()
            ->with('member')
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->orderBy('start_date')
            ->get();

        $roster = Event::query()
            ->with(['rosters' => fn ($q) => $q->with(['member', 'official', 'role'])])
            ->whereBetween('start_datetime', [$start, $end])
            ->orderBy('start_datetime')
            ->get();

        return [
            'churchName' => $this->activeChurchName(),
            'officials' => $officials,
            'roster' => $roster,
            'startDate' => $start,
            'endDate' => $end,
        ];
    }

    protected function exportBlocks(): array
    {
        $data = $this->getReportData();

        $officialRows = $data['officials']->map(function ($o) {
            return [
                $o->type,
                $o->display_name,
                $o->origin_church ?: '-',
                $o->start_date?->format('d/m/Y'),
                $o->end_date?->format('d/m/Y') ?: '-',
                $o->is_active ? 'Aktif' : 'Nonaktif',
            ];
        })->all();

        $rosterRows = $data['roster']->flatMap(function ($event) {
            return $event->rosters->map(fn ($r) => [
                $event->start_datetime?->format('d/m/Y'),
                $event->name,
                $r->member?->full_name ?? $r->official?->display_name,
                $r->role?->name,
            ]);
        })->all();

        return [
            ['title' => 'Daftar Official', 'headers' => ['Tipe', 'Nama', 'Asal', 'Mulai', 'Selesai', 'Status'], 'rows' => $officialRows],
            ['title' => 'Rekap Roster per Acara', 'headers' => ['Tanggal', 'Acara', 'Petugas', 'Peran'], 'rows' => $rosterRows],
        ];
    }
}
