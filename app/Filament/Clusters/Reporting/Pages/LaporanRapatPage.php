<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Reporting\Pages;

use App\Filament\Clusters\Reporting\ReportingCluster;
use App\Models\MeetingMinutes;
use App\Services\ReportExporter;
use App\Support\ChurchContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Laporan Rapat — rombak total + isi substantif (Fase 3A §8).
 *
 * Mewarisi logika keuangan & getReportData() dari versi lama (regresi aman),
 * menambahkan notulen terstruktur (meeting_minutes) + export Excel/PDF.
 */
class LaporanRapatPage extends \App\Filament\Pages\LaporanRapatPage
{
    protected static ?string $cluster = ReportingCluster::class;

    protected static ?string $navigationLabel = 'Laporan Rapat';

    protected static ?string $title = 'Laporan Rapat & Notulen';

    protected static ?int $navigationSort = 7;

    // MED-2 Vera: parent (halaman lama) disembunyikan dari navigasi; versi
    // cluster ini yang tampil di menu Laporan.
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function canAccess(): bool
    {
        // Matriks §1.1: super_admin, church_admin, finance_admin, report_viewer (view).
        return in_array(auth()->user()?->role, ['super_admin', 'church_admin', 'finance_admin', 'report_viewer'], true);
    }

    // ----- Pemilih gereja super_admin (§9) -----

    public ?int $churchSelect = null;

    public function mount(): void
    {
        parent::mount();
        $this->churchSelect = ChurchContext::activeChurchId();
    }

    public function updatedChurchSelect(int|string|null $value): void
    {
        if (auth()->user()?->role !== 'super_admin') {
            return;
        }

        ChurchContext::setActiveChurch($value ? (int) $value : null);
        $this->dispatch('church-changed');
    }

    public function canSelectChurch(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    public function isAllChurches(): bool
    {
        return \App\Support\ChurchContext::isAll();
    }

    public function churchOptions(): array
    {
        return \App\Models\Church::query()
            ->withoutGlobalScopes()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    // ----- Notulen (AC-3A-12/13) -----

    public string $minuteTitle = '';

    public ?string $minuteDate = null;

    public string $minuteAgenda = '';

    public string $minuteNotes = '';

    public string $minuteDecisions = '';

    public function canCreateMinutes(): bool
    {
        return in_array(auth()->user()?->role, ['super_admin', 'church_admin'], true);
    }

    public function saveMinute(): void
    {
        abort_unless($this->canCreateMinutes(), 403, 'Tidak diizinkan membuat notulen.');

        $data = $this->validate([
            'minuteTitle' => ['required', 'string', 'max:255'],
            'minuteDate' => ['required', 'date'],
            'minuteAgenda' => ['nullable', 'string'],
            'minuteNotes' => ['nullable', 'string'],
            'minuteDecisions' => ['nullable', 'string'],
        ]);

        $agenda = array_values(array_filter(array_map('trim', explode("\n", $data['minuteAgenda']))));
        $decisions = array_values(array_filter(array_map('trim', explode("\n", $data['minuteDecisions']))));

        MeetingMinutes::create([
            'title' => $data['minuteTitle'],
            'meeting_date' => Carbon::parse($data['minuteDate'])->toDateString(),
            'agenda' => $agenda,
            'participants' => [],
            'notes' => $data['minuteNotes'],
            'decisions' => $decisions,
            'attachments' => [],
        ]);

        $this->reset('minuteTitle', 'minuteDate', 'minuteAgenda', 'minuteNotes', 'minuteDecisions');
        $this->dispatch('minutes-saved');
    }

    /**
     * @return Collection<int, MeetingMinutes>
     */
    public function getMinutes(): Collection
    {
        [$start, $end] = $this->periodRange();

        return $this->scopeChurch(MeetingMinutes::query())
            ->with('event')
            ->whereBetween('meeting_date', [$start, $end])
            ->orderBy('meeting_date')
            ->get();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function periodRange(): array
    {
        $periodType = $this->data['period_type'] ?? 'monthly';
        $year = (int) ($this->data['year'] ?? now()->year);
        $month = (int) ($this->data['month'] ?? now()->month);
        $quarter = (int) ($this->data['quarter'] ?? ceil(now()->month / 3));

        if ($periodType === 'monthly') {
            $start = Carbon::create($year, $month, 1)->startOfDay();
            $end = $start->copy()->endOfMonth()->endOfDay();
        } else {
            $startMonth = ($quarter - 1) * 3 + 1;
            $start = Carbon::create($year, $startMonth, 1)->startOfDay();
            $end = $start->copy()->addMonths(2)->endOfMonth()->endOfDay();
        }

        return [$start, $end];
    }

    protected function reportTitle(): string
    {
        return 'Laporan-Rapat-'.($this->data['periodLabel'] ?? now()->format('m-Y'));
    }

    /**
     * @return array<int, array{title: string, headers?: array<int, string>, rows: array<int, array<int, mixed>>}>
     */
    public function exportBlocks(): array
    {
        $data = $this->getReportData();
        $blocks = [];

        $blocks[] = [
            'title' => 'Ringkasan Keuangan',
            'headers' => ['Keterangan', 'Jumlah (Rp)'],
            'rows' => [
                ['Saldo Awal', number_format($data['openingBalance'], 0, ',', '.')],
                ['Total Pemasukan', number_format($data['totalIncome'], 0, ',', '.')],
                ['Total Pengeluaran', number_format($data['totalExpenses'], 0, ',', '.')],
                ['Saldo Akhir', number_format($data['closingBalance'], 0, ',', '.')],
            ],
            'options' => ['totalRows' => 1, 'currencyColumns' => [2]],
        ];

        $incomeRows = $data['income']->map(fn ($i) => [$i['category'], number_format($i['total'], 0, ',', '.')])->all();
        $blocks[] = ['title' => 'Pemasukan per Kategori', 'headers' => ['Kategori', 'Total (Rp)'], 'rows' => $incomeRows, 'options' => ['currencyColumns' => [2]]];

        $expenseRows = $data['expenses']->map(fn ($i) => [$i['category'], number_format($i['total'], 0, ',', '.')])->all();
        $blocks[] = ['title' => 'Pengeluaran per Kategori', 'headers' => ['Kategori', 'Total (Rp)'], 'rows' => $expenseRows, 'options' => ['currencyColumns' => [2]]];

        foreach ($this->getMinutes() as $minute) {
            $agendaRows = collect($minute->agenda ?: [])->map(fn ($a) => [$a])->all();
            $decisionRows = collect($minute->decisions ?: [])->map(fn ($d) => [$d])->all();

            $blocks[] = ['title' => 'Agenda: '.$minute->title, 'headers' => ['Agenda'], 'rows' => $agendaRows ?: [['-']]];
            $blocks[] = ['title' => 'Notulen: '.$minute->title, 'headers' => ['Notulen'], 'rows' => [[$minute->notes ?: '-']]];
            $blocks[] = ['title' => 'Keputusan: '.$minute->title, 'headers' => ['Keputusan'], 'rows' => $decisionRows ?: [['-']]];
        }

        return $blocks;
    }

    public function downloadExcel(): BinaryFileResponse
    {
        return ReportExporter::excel($this->reportTitle().'.xlsx', $this->exportBlocks());
    }

    public function downloadPdf(): BinaryFileResponse
    {
        $data = [
            'churchName' => $this->getChurchName(),
            'title' => 'Laporan Rapat & Notulen',
            'period' => $this->data['periodLabel'] ?? '',
            'blocks' => $this->exportBlocks(),
        ];

        return ReportExporter::pdf($this->reportTitle().'.pdf', view('pdf.report'), $data);
    }
}
