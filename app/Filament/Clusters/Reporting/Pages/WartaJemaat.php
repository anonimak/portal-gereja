<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Reporting\Pages;

use App\Models\Event;
use App\Models\Member;
use App\Models\MemberSacrament;
use App\Models\Transaction;
use BackedEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

class WartaJemaat extends BaseReportPage
{
    protected string $view = 'filament.pages.warta-jemaat';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Warta Jemaat';

    protected static ?string $title = 'Warta Jemaat';

    protected static ?int $navigationSort = 1;

    protected static function allowedRoles(): array
    {
        // Matriks §1.1: super_admin, church_admin, warta_editor.
        return ['super_admin', 'church_admin', 'warta_editor'];
    }

    public ?Carbon $startDate = null;

    public ?Carbon $endDate = null;

    public function mount(): void
    {
        parent::mount();

        // Default: minggu berjalan (Senin–Minggu). startOfWeek(SUNDAY) dipakai agar
        // konsisten dengan test WartaJemaatTest (weekStart = startOfWeek(Carbon::SUNDAY)).
        $now = Carbon::now();
        $this->startDate = $now->copy()->startOfWeek(Carbon::SUNDAY);
        $this->endDate = $now->copy()->endOfWeek(Carbon::SATURDAY);
    }

    /**
     * Preset periode untuk UX filter cepat.
     */
    public function setPeriod(string $period): void
    {
        $now = Carbon::now();

        match ($period) {
            'thisWeek' => $this->setWeekRange($now),
            'lastWeek' => $this->setWeekRange($now->copy()->subWeek()),
            'thisMonth' => $this->setMonthRange($now),
            default => $this->setWeekRange($now),
        };
    }

    /**
     * Geser rentang seminggu (prev/next) dari tanggal mulai sekarang.
     */
    public function shiftWeek(int $weeks): void
    {
        $base = $this->startDate?->copy()->addWeeks($weeks) ?? Carbon::now();
        $this->setWeekRange($base);
    }

    private function setWeekRange(Carbon $anchor): void
    {
        $this->startDate = $anchor->copy()->startOfWeek(Carbon::SUNDAY);
        $this->endDate = $anchor->copy()->endOfWeek(Carbon::SATURDAY);
    }

    private function setMonthRange(Carbon $anchor): void
    {
        $this->startDate = $anchor->copy()->startOfMonth();
        $this->endDate = $anchor->copy()->endOfMonth();
    }

    /**
     * Ketersediaan endpoint export PDF (disediakan backend, T1 Byte).
     * Guard Route::has() — kalau route belum terdaftar, tombol dirender DISABLED
     * dan route() tidak pernah dipanggil (Vera HIGH + MED).
     */
    public function canExportPdf(): bool
    {
        return Route::has('warta-jemaat.export-pdf');
    }

    /**
     * Ketersediaan endpoint export Excel (disediakan backend, T1 Byte).
     */
    public function canExportExcel(): bool
    {
        return Route::has('warta-jemaat.export-excel');
    }

    protected function reportTitle(): string
    {
        return 'Warta-Jemaat-'.$this->startDate?->format('d-m-Y').'_'.$this->endDate?->format('d-m-Y');
    }

    /**
     * Data tunggal untuk tampilan & export (AC-3A-02: single source).
     *
     * @return array<string, mixed>
     */
    public function getReportData(): array
    {
        $startDate = $this->startDate ?? Carbon::now()->startOfWeek(Carbon::SUNDAY);
        $endDate = $this->endDate ?? Carbon::now()->endOfWeek(Carbon::SATURDAY);

        // Scoping church_id dijamin global scope BelongsToChurch (T1) +
        // pemilih gereja super_admin (§9) via scopeToActiveChurch().

        // Agenda / jadwal ibadah + pelayanan. Eager-load attendances supaya
        // $event->total_attendance memakai relasi (MED-2 Vera, tanpa N+1).
        $events = $this->scopeToActiveChurch(Event::with([
            'category',
            'attendances',
            'rosters' => function ($query) {
                $query->with(['member', 'official', 'role']);
            },
        ]))
            ->whereBetween('start_datetime', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->orderBy('start_datetime')
            ->get();

        // Ulang tahun dalam rentang periode (status aktif, null-safe).
        $birthdays = $this->scopeToActiveChurch(Member::query())
            ->where('status', 'aktif')
            ->whereNotNull('birth_date')
            ->get()
            ->filter(function ($member) use ($startDate, $endDate) {
                if (blank($member->birth_date)) {
                    return false;
                }

                $birthDate = Carbon::parse($member->birth_date);
                $thisYear = $birthDate->copy()->year(Carbon::now()->year);

                return $thisYear->between($startDate, $endDate);
            })
            ->sortBy(function ($member) {
                return blank($member->birth_date)
                    ? 9999
                    : Carbon::parse($member->birth_date)->dayOfYear;
            })
            ->values();

        // Transaksi periode (grouped pemasukan/pengeluaran).
        $transactions = $this->scopeToActiveChurch(Transaction::with(['fund', 'category']))
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->get()
            ->groupBy(function ($transaction) {
                return $transaction->type === 'debit' ? 'Pemasukan' : 'Pengeluaran';
            });

        // Sakramen / berita jemaat periode.
        $sacraments = $this->scopeToActiveChurch(MemberSacrament::with(['member', 'official']))
            ->whereBetween('sacrament_date', [$startDate, $endDate])
            ->orderBy('sacrament_date')
            ->get();

        $openingBalance = $this->getOpeningBalance($startDate);
        $totalIncome = (int) ($transactions->get('Pemasukan')?->sum('amount') ?? 0);
        $totalExpenses = (int) ($transactions->get('Pengeluaran')?->sum('amount') ?? 0);
        $closingBalance = $openingBalance + $totalIncome - $totalExpenses;

        return [
            'events' => $events,
            'birthdays' => $birthdays,
            'transactions' => $transactions,
            'sacraments' => $sacraments,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'churchName' => $this->activeChurchName(),
            'churchAddress' => $this->getChurchAddress(),
            'openingBalance' => $openingBalance,
            'totalIncome' => $totalIncome,
            'totalExpenses' => $totalExpenses,
            'closingBalance' => $closingBalance,
            'periodLabel' => $this->formatPeriodLabel($startDate, $endDate),
            'editionLabel' => $this->formatEditionLabel($startDate),
        ];
    }

    protected function exportBlocks(): array
    {
        $data = $this->getReportData();

        $blocks = [];

        // Jadwal Ibadah & Pelayanan
        $rows = $data['events']->map(fn (Event $event) => [
            $event->start_datetime?->format('d/m/Y H:i'),
            $event->name,
            $event->location,
            $event->rosters->map(fn ($r) => $r->member?->full_name ?? $r->official?->display_name)->filter()->implode(', '),
            (string) $event->total_attendance,
        ])->all();

        $blocks[] = [
            'title' => 'Jadwal Ibadah & Pelayanan',
            'headers' => ['Waktu', 'Acara', 'Lokasi', 'Petugas', 'Kehadiran'],
            'rows' => $rows,
        ];

        // Ulang Tahun
        $blocks[] = [
            'title' => 'Ulang Tahun Jemaat',
            'headers' => ['Nama', 'Tanggal'],
            'rows' => $data['birthdays']->map(fn ($m) => [
                $m->full_name,
                Carbon::parse($m->birth_date)->format('d/m/Y'),
            ])->all(),
        ];

        // Sakramen
        $blocks[] = [
            'title' => 'Perayaan Sakramen',
            'headers' => ['Tanggal', 'Jenis', 'Nama', 'Pelayan', 'No. Sertifikat'],
            'rows' => $data['sacraments']->map(fn ($s) => [
                $s->sacrament_date?->format('d/m/Y'),
                $s->type,
                $s->member?->full_name,
                $s->official?->display_name,
                $s->certificate_number,
            ])->all(),
        ];

        // Keuangan Ringkas
        $income = $data['transactions']->get('Pemasukan', collect());
        $expense = $data['transactions']->get('Pengeluaran', collect());
        $blocks[] = [
            'title' => 'Laporan Keuangan Ringkas',
            'headers' => ['Keterangan', 'Jumlah (Rp)'],
            'rows' => [
                ['Total Pemasukan', number_format($income->sum('amount'), 0, ',', '.')],
                ['Total Pengeluaran', number_format($expense->sum('amount'), 0, ',', '.')],
                ['Selisih', number_format($income->sum('amount') - $expense->sum('amount'), 0, ',', '.')],
            ],
        ];

        return $blocks;
    }

    /**
     * Alamat gereja pada kop warta (opsional).
     */
    private function getChurchAddress(): string
    {
        $user = auth()->user();

        if (! $user || $user->role === 'super_admin') {
            return '';
        }

        return $user->church?->address ?? '';
    }

    /**
     * Saldo awal = total debit - total credit sebelum tanggal mulai.
     */
    private function getOpeningBalance(Carbon $startDate): int
    {
        $debit = Transaction::query()
            ->where('type', 'debit')
            ->whereDate('transaction_date', '<', $startDate)
            ->sum('amount');

        $credit = Transaction::query()
            ->where('type', 'credit')
            ->whereDate('transaction_date', '<', $startDate)
            ->sum('amount');

        return (int) $debit - (int) $credit;
    }

    /**
     * Label periode, mis. "9–15 Maret 2026".
     */
    private function formatPeriodLabel(Carbon $startDate, Carbon $endDate): string
    {
        $start = $startDate->locale('id')->isoFormat('D MMMM YYYY');
        $end = $endDate->locale('id')->isoFormat('D MMMM YYYY');

        return "{$start} – {$end}";
    }

    /**
     * Label edisi, mis. "Edisi Minggu ke-10 — 2026".
     */
    private function formatEditionLabel(Carbon $startDate): string
    {
        return 'Edisi Minggu ke-'.$startDate->weekOfYear.' — '.$startDate->year;
    }
}
