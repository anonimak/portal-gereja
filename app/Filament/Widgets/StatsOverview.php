<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Member;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $churchId = auth()->user()?->church_id;

        $activeMembersCount = Member::where('status', 'aktif')
            ->when($churchId, fn ($query) => $query->where('church_id', $churchId))
            ->count();

        $incomeThisMonth = Transaction::where('type', 'debit')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->when($churchId, fn ($query) => $query->where('church_id', $churchId))
            ->sum('amount');

        $expenseThisMonth = Transaction::where('type', 'credit')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->when($churchId, fn ($query) => $query->where('church_id', $churchId))
            ->sum('amount');

        return [
            Stat::make('Total Jemaat Aktif', $activeMembersCount)
                ->description('Member aktif')
                ->color('success'),
            Stat::make('Pemasukan Bulan Ini', $this->formatCurrency($incomeThisMonth))
                ->description('Total income bulan ini')
                ->color('info'),
            Stat::make('Pengeluaran Bulan Ini', $this->formatCurrency($expenseThisMonth))
                ->description('Total expense bulan ini')
                ->color('danger'),
        ];
    }

    private function formatCurrency(int $amount): string
    {
        return 'Rp' . number_format($amount, 0, ',', '.');
    }
}
