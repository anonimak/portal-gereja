<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Reporting\Pages;

use App\Models\MemberSacrament;

class LaporanSakramenPage extends BaseReportPage
{
    protected string $view = 'filament.pages.laporan-sakramen';

    protected static ?string $navigationLabel = 'Laporan Sakramen';

    protected static ?string $title = 'Laporan Sakramen / Lifecycle';

    protected static ?int $navigationSort = 5;

    protected static function allowedRoles(): array
    {
        // Matriks §1.1: super_admin, church_admin, jemaat_admin (view), report_viewer (view).
        return ['super_admin', 'church_admin', 'jemaat_admin', 'report_viewer'];
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
        return 'Laporan-Sakramen-'.($this->month ?: now()->format('Y-m'));
    }

    public function getReportData(): array
    {
        $month = $this->month ?: now()->format('Y-m');
        $start = \Illuminate\Support\Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $sacraments = MemberSacrament::query()
            ->with(['member', 'official'])
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->whereBetween('sacrament_date', [$start, $end])
            ->orderBy('sacrament_date')
            ->get();

        return [
            'churchName' => $this->activeChurchName(),
            'sacraments' => $sacraments,
            'byType' => $sacraments->groupBy(fn ($s) => $s->type ?: 'tanpa-jenis')->map->count(),
            'startDate' => $start,
            'endDate' => $end,
        ];
    }

    protected function exportBlocks(): array
    {
        $data = $this->getReportData();

        $summary = $data['byType']->map(fn ($count, $type) => [$type, $count])->values()->all();

        $detail = $data['sacraments']->map(fn ($s) => [
            $s->sacrament_date?->format('d/m/Y'),
            $s->type,
            $s->member?->full_name,
            $s->official?->display_name,
            $s->certificate_number,
        ])->all();

        return [
            ['title' => 'Ringkasan per Jenis', 'headers' => ['Jenis', 'Jumlah'], 'rows' => $summary],
            ['title' => 'Detail Sakramen', 'headers' => ['Tanggal', 'Jenis', 'Nama', 'Pelayan', 'No. Sertifikat'], 'rows' => $detail],
        ];
    }
}
