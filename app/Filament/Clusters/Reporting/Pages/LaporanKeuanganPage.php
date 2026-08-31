<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Reporting\Pages;

use App\Models\Fund;
use App\Models\Transaction;

class LaporanKeuanganPage extends BaseReportPage
{
    protected string $view = 'filament.pages.laporan-keuangan';

    protected static ?string $navigationLabel = 'Laporan Keuangan';

    protected static ?string $title = 'Laporan Keuangan per Dana/Kas';

    protected static ?int $navigationSort = 3;

    protected static function allowedRoles(): array
    {
        // Matriks §1.1: super_admin, church_admin, finance_admin, report_viewer (view).
        return ['super_admin', 'church_admin', 'finance_admin', 'report_viewer'];
    }

    public ?int $fundId = null;

    public ?string $month = null;

    public function mount(): void
    {
        parent::mount();
        $this->month = now()->format('Y-m');
    }

    protected function reportTitle(): string
    {
        return 'Laporan-Keuangan-'.($this->month ?: now()->format('Y-m'));
    }

    public function getReportData(): array
    {
        $month = $this->month ?: now()->format('Y-m');
        $start = \Illuminate\Support\Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $funds = $this->scopeToActiveChurch(Fund::query())
            ->with(['transactions' => fn ($q) => $q->whereBetween('transaction_date', [$start, $end])->with('category')])
            ->when($this->fundId, fn ($q) => $q->whereKey($this->fundId))
            ->orderBy('name')
            ->get();

        // MED-3 Vera: saldo awal tiap dana = akumulasi transaksi SEBELUM periode.
        // Satu query agregat per periode, bukan N+1 per dana.
        $opening = Transaction::query()
            ->whereIn('fund_id', $funds->pluck('id'))
            ->whereDate('transaction_date', '<', $start)
            ->get()
            ->groupBy('fund_id')
            ->map(fn ($txns) => (int) $txns->where('type', 'debit')->sum('amount') - (int) $txns->where('type', 'credit')->sum('amount'));

        return [
            'churchName' => $this->activeChurchName(),
            'funds' => $funds,
            'openingBalances' => $opening,
            'startDate' => $start,
            'endDate' => $end,
        ];
    }

    protected function exportBlocks(): array
    {
        $data = $this->getReportData();
        $blocks = [];

        foreach ($data['funds'] as $fund) {
            $income = $fund->transactions->where('type', 'debit');
            $expense = $fund->transactions->where('type', 'credit');
            $openingBalance = (int) ($data['openingBalances'][$fund->id] ?? 0);

            $blocks[] = [
                'title' => $fund->name.' — Ringkasan',
                'headers' => ['Keterangan', 'Jumlah (Rp)'],
                'rows' => [
                    ['Saldo Awal (sebelum periode)', number_format($openingBalance, 0, ',', '.')],
                    ['Total Pemasukan', number_format($income->sum('amount'), 0, ',', '.')],
                    ['Total Pengeluaran', number_format($expense->sum('amount'), 0, ',', '.')],
                    ['Saldo Akhir', number_format($openingBalance + $income->sum('amount') - $expense->sum('amount'), 0, ',', '.')],
                ],
            ];

            $detail = $fund->transactions->map(fn (Transaction $t) => [
                $t->transaction_date?->format('d/m/Y'),
                $t->category?->name ?? '-',
                $t->description,
                $t->type === 'debit' ? 'Pemasukan' : 'Pengeluaran',
                number_format($t->amount, 0, ',', '.'),
            ])->all();

            $blocks[] = [
                'title' => $fund->name.' — Rincian Transaksi',
                'headers' => ['Tanggal', 'Kategori', 'Deskripsi', 'Tipe', 'Jumlah (Rp)'],
                'rows' => $detail,
            ];
        }

        return $blocks;
    }
}
