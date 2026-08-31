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
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class WartaJemaat extends Page
{
    protected string $view = 'filament.pages.warta-jemaat';

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $cluster = ReportingCluster::class;

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
        // Set default to current week (Monday to Sunday)
        $now = Carbon::now();
        $this->startDate = $now->copy()->startOfWeek(Carbon::SUNDAY);
        $this->endDate = $now->copy()->endOfWeek(Carbon::SATURDAY);
    }

    public function getReportData(): array
    {
        $startDate = $this->startDate ?? Carbon::now()->startOfWeek(Carbon::SUNDAY);
        $endDate = $this->endDate ?? Carbon::now()->endOfWeek(Carbon::SATURDAY);

        // Scoping church_id TIDAK ditulis eksplisit DI SINI — dijamin oleh global scope
        // BelongsToChurch (T1): non-super_admin otomatis di-scope ke gereja sendiri,
        // super_admin melihat SEMUA gereja (AC-T1-04/09 — HIGH-1 Vera).
        // Menambahkan ->where('church_id', auth()->user()->church_id) justru akan
        // men-scope super_admin ke gereja seed-nya (Candimas) — inkonsisten.

        // Fetch events with rosters (scoped to church via global scope)
        $events = Event::with(['category', 'rosters' => function ($query) {
            $query->with(['member', 'official', 'role']);
        }])
            ->whereBetween('start_datetime', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->orderBy('start_datetime')
            ->get();

        // Fetch birthdays in date range (scoped to church, null-safe)
        $birthdays = Member::where('status', 'aktif')
            ->whereNotNull('birth_date')
            ->get()
            ->filter(function ($member) use ($startDate, $endDate) {
                $birthDate = Carbon::parse($member->birth_date);
                $thisYear = $birthDate->copy()->year(Carbon::now()->year);

                return $thisYear->between($startDate, $endDate);
            })
            ->sortBy(function ($member) {
                return Carbon::parse($member->birth_date)->dayOfYear;
            })
            ->values();

        // Fetch transactions grouped by type (scoped to church via global scope)
        $transactions = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->get()
            ->groupBy(function ($transaction) {
                return $transaction->type === 'debit' ? 'Pemasukan' : 'Pengeluaran';
            });

        // Fetch member sacraments (scoped to church via global scope)
        $sacraments = MemberSacrament::with(['member', 'official'])
            ->whereBetween('sacrament_date', [$startDate, $endDate])
            ->orderBy('sacrament_date')
            ->get();

        return [
            'events' => $events,
            'birthdays' => $birthdays,
            'transactions' => $transactions,
            'sacraments' => $sacraments,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }
}
