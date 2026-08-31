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

        // Scoping church_id dijamin global scope BelongsToChurch (T1/HIGH-1 Vera):
        // - church_admin/finance_admin → hanya data gereja sendiri.
        // - super_admin → SEMUA gereja (tanpa filter gereja seed-nya).
        // Tidak menulis ->where('church_id', auth()->user()->church_id) agar
        // super_admin tidak ter-scope ke gereja sendiri.

        $activeMembersCount = Member::where('status', 'aktif')->count();

        $incomeThisMonth = Transaction::where('type', 'debit')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $expenseThisMonth = Transaction::where('type', 'credit')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
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
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
