<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CashFlowChart extends ChartWidget
{
    protected ?string $heading = 'Arus Kas Tahun Ini';

    protected function getData(): array
    {
        $year = Carbon::now()->year;
        $churchId = auth()->user()?->church_id;
        $incomeByMonth = [];
        $expenseByMonth = [];

        // Initialize all months with zero
        for ($month = 1; $month <= 12; $month++) {
            $incomeByMonth[$month] = 0;
            $expenseByMonth[$month] = 0;
        }

        // Fetch all transactions for the current year
        $transactions = Transaction::whereYear('transaction_date', $year)
            ->when($churchId, fn ($query) => $query->where('church_id', $churchId))
            ->get(['transaction_date', 'type', 'amount']);

        // Group transactions by month and type
        foreach ($transactions as $transaction) {
            $month = $transaction->transaction_date->month;
            if ($transaction->type === 'debit') {
                $incomeByMonth[$month] += $transaction->amount;
            } else {
                $expenseByMonth[$month] += $transaction->amount;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => array_values($incomeByMonth),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => array_values($expenseByMonth),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'tension' => 0.4,
                ],
            ],
            'labels' => [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
