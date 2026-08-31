<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Reporting\Pages;

use App\Filament\Clusters\Reporting\ReportingCluster;
use App\Models\Event;
use App\Models\Member;
use App\Models\MemberSacrament;
use App\Models\Transaction;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

class WartaJemaat extends Page
{
    protected string $view = 'filament.pages.warta-jemaat';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $cluster = ReportingCluster::class;

    protected static ?int $navigationSort = 1;

    /**
     * Batasan role halaman (AC-T2-06 — BLOCK-3 Vera).
     * Warta berisi data jemaat/event/sakramen + ringkasan keuangan → super_admin & church_admin.
     * finance_admin TIDAK dibuka ke Warta (bukan murni laporan keuangan).
     */
    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, ['super_admin', 'church_admin'], true);
    }

    public ?Carbon $startDate = null;

    public ?Carbon $endDate = null;

    public function mount(): void
    {
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

    /**
     * Data tunggal untuk tampilan & export (AC-3A-02: single source).
     *
     * @return array<string, mixed>
     */
    public function getReportData(): array
    {
        $startDate = $this->startDate ?? Carbon::now()->startOfWeek(Carbon::SUNDAY);
        $endDate = $this->endDate ?? Carbon::now()->endOfWeek(Carbon::SATURDAY);

        // Scoping church_id TIDAK ditulis eksplisit DI SINI — dijamin oleh global scope
        // BelongsToChurch (T1): non-super_admin otomatis di-scope ke gereja sendiri,
        // super_admin melihat SEMUA gereja (AC-T1-04/09 — HIGH-1 Vera).

        // Agenda / jadwal ibadah + pelayanan. Eager-load attendances supaya
        // $event->total_attendance memakai relasi (MED-2 Vera, tanpa N+1).
        $events = Event::with([
            'category',
            'attendances',
            'rosters' => function ($query) {
                $query->with(['member', 'official', 'role']);
            },
        ])
            ->whereBetween('start_datetime', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->orderBy('start_datetime')
            ->get();

        // Ulang tahun dalam rentang periode (status aktif, null-safe).
        $birthdays = Member::where('status', 'aktif')
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
        $transactions = Transaction::with(['fund', 'category'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->get()
            ->groupBy(function ($transaction) {
                return $transaction->type === 'debit' ? 'Pemasukan' : 'Pengeluaran';
            });

        // Sakramen / berita jemaat periode.
        $sacraments = MemberSacrament::with(['member', 'official'])
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
            'churchName' => $this->getChurchName(),
            'churchAddress' => $this->getChurchAddress(),
            'openingBalance' => $openingBalance,
            'totalIncome' => $totalIncome,
            'totalExpenses' => $totalExpenses,
            'closingBalance' => $closingBalance,
            'periodLabel' => $this->formatPeriodLabel($startDate, $endDate),
            'editionLabel' => $this->formatEditionLabel($startDate),
        ];
    }

    /**
     * Nama gereja pada kop warta.
     * - super_admin → 'Semua Gereja' (data lintas gereja).
     * - church_admin → nama gereja sendiri.
     */
    private function getChurchName(): string
    {
        $user = auth()->user();

        if (! $user) {
            return 'Gereja';
        }

        if ($user->role === 'super_admin') {
            return 'Semua Gereja';
        }

        return $user->church?->name ?? 'Gereja';
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
