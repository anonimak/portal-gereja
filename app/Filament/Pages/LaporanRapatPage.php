<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Event;
use App\Models\Transaction;
use App\Support\ChurchContext;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use UnitEnum;

class LaporanRapatPage extends Page implements HasForms
{
    use InteractsWithForms;

    /**
     * Batasan role halaman (AC-T2-06 — BLOCK-3 Vera).
     * Laporan Rapat & Keuangan boleh diakses finance_admin (laporan keuangan).
     */
    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, ['super_admin', 'church_admin', 'finance_admin'], true);
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Laporan Rapat';

    protected static ?string $title = 'Laporan Rapat & Keuangan';

    protected string $view = 'filament.pages.laporan-rapat';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    /**
     * MED-2 Vera: halaman lama digantikan versi cluster (Fase 3A) —
     * jangan tampil dobel di menu. Tetap ter-register (route export masih
     * memakai class ini), hanya disembunyikan dari navigasi.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public ?int $churchSelect = null;

    #[\Livewire\Attributes\Computed]
    public function reportData(): array
    {
        return $this->getReportData();
    }

    public function mount(): void
    {
        $currentYear = (int) now()->year;
        $currentMonth = (int) now()->month;
        $currentQuarter = ceil($currentMonth / 3);

        $this->form->fill([
            'period_type' => 'monthly',
            'month' => $currentMonth,
            'quarter' => $currentQuarter,
            'year' => $currentYear,
        ]);
    }

    public function form(Schema $form): Schema
    {
        $currentYear = (int) now()->year;

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('period_type')
                            ->label('Tipe Periode')
                            ->options([
                                'monthly' => 'Bulanan',
                                'quarterly' => 'Pleno Triwulan',
                            ])
                            ->live()
                            ->required()
                            ->default('monthly'),

                        Select::make('month')
                            ->label('Bulan')
                            ->options([
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember',
                            ])
                            ->live()
                            ->visible(fn (Get $get) => $get('period_type') === 'monthly')
                            ->required(fn (Get $get) => $get('period_type') === 'monthly')
                            ->default((int) now()->month),

                        Select::make('quarter')
                            ->label('Triwulan')
                            ->options([
                                1 => 'Q1 (Jan–Mar)',
                                2 => 'Q2 (Apr–Jun)',
                                3 => 'Q3 (Jul–Sep)',
                                4 => 'Q4 (Okt–Des)',
                            ])
                            ->live()
                            ->visible(fn (Get $get) => $get('period_type') === 'quarterly')
                            ->required(fn (Get $get) => $get('period_type') === 'quarterly')
                            ->default((int) ceil(now()->month / 3)),

                        Select::make('year')
                            ->label('Tahun')
                            ->options(array_combine(
                                range($currentYear - 4, $currentYear),
                                range($currentYear - 4, $currentYear),
                            ))
                            ->live()
                            ->required()
                            ->default($currentYear),
                    ])
                    ->columns(4),
            ])
            ->statePath('data'); // ← INI yang penting
    }

    /**
     * Get report data based on form selection.
     *
     * @return array<string, mixed>
     */
    public function getReportData(): array
    {
        // Baca dari properti $data (ter-sync dengan form via statePath('data') di Livewire,
        // dan bisa diisi langsung dari route export tanpa lifecycle Livewire).
        $periodType = $this->data['period_type'] ?? 'monthly';
        $year = (int) ($this->data['year'] ?? now()->year);
        $month = (int) ($this->data['month'] ?? now()->month);
        $quarter = (int) ($this->data['quarter'] ?? ceil(now()->month / 3));

        if ($periodType === 'monthly') {
            $startDate = Carbon::create($year, $month, 1)->startOfDay();
            $endDate = $startDate->clone()->endOfMonth()->endOfDay();
        } else {
            $startMonth = ($quarter - 1) * 3 + 1;
            $startDate = Carbon::create($year, $startMonth, 1)->startOfDay();
            $endDate = $startDate->clone()->addMonths(2)->endOfMonth()->endOfDay();
        }

        // Scoping church_id TIDAK ditulis eksplisit DI SINI — dijamin global scope
        // BelongsToChurch (HIGH-1 Vera, konsisten dengan WartaJemaat/Stats/CashFlow):
        // - church_admin / finance_admin → hanya data gereja sendiri.
        // - super_admin → SEMUA gereja (tanpa ter-scope ke gereja seed-nya).
        // Menambahkan ->where('church_id', auth()->user()->church_id) justru akan
        // mengunci super_admin ke satu gereja — inkonsisten dengan AC-T1-04/09.

        // Fetch events with eager loading
        $events = $this->scopeChurch(Event::query())
            ->whereBetween('start_datetime', [$startDate, $endDate])
            ->with([
                // MED-2 Vera: eager-load attendances supaya $event->total_attendance
                // memakai relasi (tanpa N+1 exists+count per event).
                'attendances',
                'category',
                'rosters' => fn ($q) => $q->with([
                    'role',
                    'member',
                    'official.member',
                ]),
            ])
            ->orderBy('start_datetime')
            ->get();

        // Fetch financial data
        $openingBalance = $this->getOpeningBalance($startDate);
        $income = $this->getIncomeByCategory($startDate, $endDate);
        $expenses = $this->getExpensesByCategory($startDate, $endDate);
        $totalIncome = $income->sum('total');
        $totalExpenses = $expenses->sum('total');
        $closingBalance = $openingBalance + $totalIncome - $totalExpenses;

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'events' => $events,
            'openingBalance' => $openingBalance,
            'income' => $income,
            'expenses' => $expenses,
            'totalIncome' => $totalIncome,
            'totalExpenses' => $totalExpenses,
            'closingBalance' => $closingBalance,
            'churchName' => $this->getChurchName(),
            'periodLabel' => $this->getPeriodLabel($periodType, $month, $quarter, $year),
        ];
    }

    /**
     * Nama gereja pada kop laporan.
     * - super_admin → 'Semua Gereja' (data lintas gereja).
     * - church_admin / finance_admin → nama gereja sendiri.
     */
    protected function getChurchName(): string
    {
        // Pakai ChurchContext::churchName() agar pemilih gereja super_admin (§9)
        // ikut: super_admin 'All' -> 'Semua Gereja', pilih gereja -> nama gereja itu.
        return \App\Support\ChurchContext::churchName();
    }

    /**
     * Terapkan filter gereja aktif (pemilih super_admin §9) pada query laporan.
     * Hanya untuk laporan — resource CRUD tidak terpengaruh (Vera LOW).
     */
    protected function scopeChurch(\Illuminate\Database\Eloquent\Builder $builder): \Illuminate\Database\Eloquent\Builder
    {
        $active = \App\Support\ChurchContext::activeChurchId();
        if ($active !== null) {
            $builder->where('church_id', $active);
        }

        return $builder;
    }

    /**
     * Calculate opening balance (before start date).
     */
    private function getOpeningBalance(Carbon $startDate): int
    {
        $debit = $this->scopeChurch(Transaction::query())
            ->where('type', 'debit')
            ->whereDate('transaction_date', '<', $startDate)
            ->sum('amount');

        $credit = Transaction::query()
            ->where('type', 'credit')
            ->whereDate('transaction_date', '<', $startDate)
            ->sum('amount');

        return $debit - $credit;
    }

    /**
     * Get income grouped by category.
     *
     * @return Collection<int, array{category: string, total: int}>
     */
    private function getIncomeByCategory(Carbon $startDate, Carbon $endDate): Collection
    {
        return $this->scopeChurch(Transaction::query())
            ->where('type', 'debit')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with('category')
            ->get()
            // H4 Vera: kategori bisa di-soft-delete → relasi null. Kelompokkan
            // dengan fallback 'Tanpa kategori' agar laporan tidak 500.
            ->groupBy(fn ($t) => $t->category?->name ?? 'Tanpa kategori')
            ->map(fn ($transactions) => [
                'category' => $transactions[0]->category?->name ?? 'Tanpa kategori',
                'total' => $transactions->sum('amount'),
            ])
            ->values();
    }

    /**
     * Get expenses grouped by category.
     *
     * @return Collection<int, array{category: string, total: int}>
     */
    private function getExpensesByCategory(Carbon $startDate, Carbon $endDate): Collection
    {
        return $this->scopeChurch(Transaction::query())
            ->where('type', 'credit')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with('category')
            ->get()
            // H4 Vera: kategori bisa di-soft-delete → relasi null. Kelompokkan
            // dengan fallback 'Tanpa kategori' agar laporan tidak 500.
            ->groupBy(fn ($t) => $t->category?->name ?? 'Tanpa kategori')
            ->map(fn ($transactions) => [
                'category' => $transactions[0]->category?->name ?? 'Tanpa kategori',
                'total' => $transactions->sum('amount'),
            ])
            ->values();
    }

    /**
     * Get human-readable period label.
     */
    private function getPeriodLabel(string $periodType, int $month, int $quarter, int $year): string
    {
        if ($periodType === 'monthly') {
            $months = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember',
            ];

            return ($months[$month] ?? '')." {$year}";
        }

        $quarters = [
            1 => 'Q1 (Jan–Mar)',
            2 => 'Q2 (Apr–Jun)',
            3 => 'Q3 (Jul–Sep)',
            4 => 'Q4 (Okt–Des)',
        ];

        return ($quarters[$quarter] ?? '')." {$year}";
    }

    /**
     * Export report to Excel format.
     */
    public function exportToExcel(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $reportData = $this->getReportData();
        $fileName = "Laporan-Rapat-{$reportData['periodLabel']}.csv";

        return response()->streamDownload(function () use ($reportData) {
            $output = fopen('php://output', 'w');

            // Set header encoding
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header info
            fputcsv($output, [''], ';');
            fputcsv($output, [$reportData['churchName']], ';');
            fputcsv($output, ['Laporan Rapat & Keuangan'], ';');
            fputcsv($output, ['Periode: '.$reportData['periodLabel']], ';');
            fputcsv($output, [''], ';');

            // Events section
            fputcsv($output, ['LAPORAN PELAYANAN & KEHADIRAN'], ';');
            fputcsv($output, ['Tanggal', 'Kegiatan', 'Kategori', 'Pelayan', 'Laki-laki', 'Perempuan', 'Total'], ';');

            foreach ($reportData['events'] as $event) {
                foreach ($event->rosters as $roster) {
                    $pelayan = '';
                    if ($roster->member_id) {
                        $pelayan = $roster->member->full_name ?? '-';
                    } elseif ($roster->official_id) {
                        $pelayan = $roster->official->display_name ?? '-';
                    }

                    fputcsv($output, [
                        $event->start_datetime->locale('id')->format('d M Y'),
                        $event->title,
                        $event->category->name ?? '-',
                        ($roster->role->name ?? '-').': '.$pelayan,
                        $event->attendance_male ?? 0,
                        $event->attendance_female ?? 0,
                        $event->total_attendance,
                    ], ';');
                }
            }

            fputcsv($output, [''], ';');
            fputcsv($output, ['LAPORAN KEUANGAN'], ';');
            fputcsv($output, [''], ';');

            // Financial summary
            fputcsv($output, ['Saldo Awal', number_format($reportData['openingBalance'], 0, ',', '.')], ';');
            fputcsv($output, [''], ';');

            // Income
            fputcsv($output, ['PEMASUKAN'], ';');
            fputcsv($output, ['Kategori', 'Total'], ';');
            foreach ($reportData['income'] as $item) {
                fputcsv($output, [$item['category'], number_format($item['total'], 0, ',', '.')], ';');
            }
            fputcsv($output, ['TOTAL PEMASUKAN', number_format($reportData['totalIncome'], 0, ',', '.')], ';');

            fputcsv($output, [''], ';');

            // Expenses
            fputcsv($output, ['PENGELUARAN'], ';');
            fputcsv($output, ['Kategori', 'Total'], ';');
            foreach ($reportData['expenses'] as $item) {
                fputcsv($output, [$item['category'], number_format($item['total'], 0, ',', '.')], ';');
            }
            fputcsv($output, ['TOTAL PENGELUARAN', number_format($reportData['totalExpenses'], 0, ',', '.')], ';');

            fputcsv($output, [''], ';');
            fputcsv($output, ['SALDO AKHIR', number_format($reportData['closingBalance'], 0, ',', '.')], ';');

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function canSelectChurch(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    public function isAllChurches(): bool
    {
        return ChurchContext::isAll();
    }

    public function churchOptions(): array
    {
        return \App\Models\Church::query()
            ->withoutGlobalScopes()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function updatedChurchSelect(int|string|null $value): void
    {
        if (auth()->user()?->role !== 'super_admin') {
            return;
        }

        ChurchContext::setActiveChurch($value ? (int) $value : null);
        $this->dispatch('church-changed');
    }

    // ----- Notulen (Fase 3A §8) — diperlukan karena blade laporan-rapat.blade.php
    // memanggil canCreateMinutes()/getMinutes() (halaman lama & cluster berbagi view).

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

        \App\Models\MeetingMinutes::create([
            'title' => $data['minuteTitle'],
            'meeting_date' => \Illuminate\Support\Carbon::parse($data['minuteDate'])->toDateString(),
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
     * @return \Illuminate\Support\Collection<int, \App\Models\MeetingMinutes>
     */
    public function getMinutes(): Collection
    {
        [$start, $end] = $this->periodRange();

        return $this->scopeChurch(\App\Models\MeetingMinutes::query())
            ->with('event')
            ->whereBetween('meeting_date', [$start, $end])
            ->orderBy('meeting_date')
            ->get();
    }

    /**
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}
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
}
