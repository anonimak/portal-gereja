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
        $churchId = auth()->user()?->church_id;

        // Fetch events with rosters (scoped to church)
        $events = Event::with(['category', 'rosters' => function ($query) {
            $query->with(['member', 'official', 'role']);
        }])
            ->when($churchId, fn ($query) => $query->where('church_id', $churchId))
            ->whereBetween('start_datetime', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->orderBy('start_datetime')
            ->get();

        // Fetch birthdays in date range (scoped to church, null-safe)
        $birthdays = Member::where('status', 'aktif')
            ->when($churchId, fn ($query) => $query->where('church_id', $churchId))
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

        // Fetch transactions grouped by type (scoped to church)
        $transactions = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->when($churchId, fn ($query) => $query->where('church_id', $churchId))
            ->orderBy('transaction_date')
            ->get()
            ->groupBy(function ($transaction) {
                return $transaction->type === 'debit' ? 'Pemasukan' : 'Pengeluaran';
            });

        // Fetch member sacraments (scoped to church)
        $sacraments = MemberSacrament::with(['member', 'official'])
            ->when($churchId, fn ($query) => $query->where('church_id', $churchId))
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
