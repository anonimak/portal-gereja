<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Event;
use App\Models\Transaction;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Collection;
use UnitEnum;

class LaporanRapatPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Laporan Rapat';

    protected static ?string $title = 'Laporan Rapat & Keuangan';

    protected string $view = 'filament.pages.laporan-rapat';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

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

    public function form(Schema  $form): Schema
    {
        $currentYear = (int) now()->year;

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('period_type')
                            ->label('Tipe Periode')
                            ->options([
                                'monthly'   => 'Bulanan',
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
                            ->visible(fn(Get $get) => $get('period_type') === 'monthly')
                            ->required(fn(Get $get) => $get('period_type') === 'monthly')
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
                            ->visible(fn(Get $get) => $get('period_type') === 'quarterly')
                            ->required(fn(Get $get) => $get('period_type') === 'quarterly')
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
        $data = $this->form->getRawState();
        // Fallback jika form belum ter-fill
        $periodType = $data['period_type'] ?? 'monthly';
        $year       = (int) ($data['year']    ?? now()->year);
        $month      = (int) ($data['month']   ?? now()->month);
        $quarter    = (int) ($data['quarter'] ?? ceil(now()->month / 3));

        $churchId = auth()->user()->church_id;

        if ($periodType === 'monthly') {
            $startDate = Carbon::create($year, $month, 1)->startOfDay();
            $endDate   = $startDate->clone()->endOfMonth()->endOfDay();
        } else {
            $startMonth = ($quarter - 1) * 3 + 1;
            $startDate  = Carbon::create($year, $startMonth, 1)->startOfDay();
            $endDate    = $startDate->clone()->addMonths(2)->endOfMonth()->endOfDay();
        }

        // Fetch events with eager loading
        $events = Event::query()
            ->where('church_id', $churchId)
            ->whereBetween('start_datetime', [$startDate, $endDate])
            ->with([
                'category',
                'rosters' => fn($q) => $q->with([
                    'role',
                    'member',
                    'official.member',
                ]),
            ])
            ->orderBy('start_datetime')
            ->get();

        // Fetch financial data
        $openingBalance = $this->getOpeningBalance($churchId, $startDate);
        $income = $this->getIncomeByCategory($churchId, $startDate, $endDate);
        $expenses = $this->getExpensesByCategory($churchId, $startDate, $endDate);
        $totalIncome = $income->sum('total');
        $totalExpenses = $expenses->sum('total');
        $closingBalance = $openingBalance + $totalIncome - $totalExpenses;

        return [
            // gunakan variabel lokal, bukan $data langsung
            'startDate'      => $startDate,
            'endDate'        => $endDate,
            'events'         => $events,
            'openingBalance' => $openingBalance,
            'income'         => $income,
            'expenses'       => $expenses,
            'totalIncome'    => $totalIncome,
            'totalExpenses'  => $totalExpenses,
            'closingBalance' => $closingBalance,
            'churchName'     => auth()->user()->church->name ?? 'Gereja',
            'periodLabel'    => $this->getPeriodLabel($periodType, $month, $quarter, $year),
        ];
    }

    /**
     * Calculate opening balance (before start date).
     */
    private function getOpeningBalance(int $churchId, Carbon $startDate): int
    {
        $debit = Transaction::query()
            ->where('church_id', $churchId)
            ->where('type', 'debit')
            ->whereDate('transaction_date', '<', $startDate)
            ->sum('amount');

        $credit = Transaction::query()
            ->where('church_id', $churchId)
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
    private function getIncomeByCategory(int $churchId, Carbon $startDate, Carbon $endDate): Collection
    {
        return Transaction::query()
            ->where('church_id', $churchId)
            ->where('type', 'debit')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with('category')
            ->get()
            ->groupBy('category.name')
            ->map(fn($transactions) => [
                'category' => $transactions[0]->category->name,
                'total' => $transactions->sum('amount'),
            ])
            ->values();
    }

    /**
     * Get expenses grouped by category.
     *
     * @return Collection<int, array{category: string, total: int}>
     */
    private function getExpensesByCategory(int $churchId, Carbon $startDate, Carbon $endDate): Collection
    {
        return Transaction::query()
            ->where('church_id', $churchId)
            ->where('type', 'credit')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with('category')
            ->get()
            ->groupBy('category.name')
            ->map(fn($transactions) => [
                'category' => $transactions[0]->category->name,
                'total' => $transactions->sum('amount'),
            ])
            ->values();
    }

    /**
     * Get human-readable period label.
     *
     * @param  array<string, mixed>  $data
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

            return ($months[$month] ?? '') . " {$year}";
        }

        $quarters = [
            1 => 'Q1 (Jan–Mar)',
            2 => 'Q2 (Apr–Jun)',
            3 => 'Q3 (Jul–Sep)',
            4 => 'Q4 (Okt–Des)',
        ];

        return ($quarters[$quarter] ?? '') . " {$year}";
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
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header info
            fputcsv($output, [''], ';');
            fputcsv($output, [$reportData['churchName']], ';');
            fputcsv($output, ['Laporan Rapat & Keuangan'], ';');
            fputcsv($output, ['Periode: ' . $reportData['periodLabel']], ';');
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
                        ($roster->role->name ?? '-') . ': ' . $pelayan,
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
}
