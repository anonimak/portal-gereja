<x-filament-panels::page>
    @include('filament.pages._church-selector')
    @php($data = $this->getReportData())

    <div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Dana</label>
                <select wire:model.live="fundId" class="mt-1 block rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">Semua Dana</option>
                    @foreach(\App\Models\Fund::query()->orderBy('name')->get() as $fund)
                        <option value="{{ $fund->id }}">{{ $fund->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Periode (bulan)</label>
                <input type="month" wire:model.live="month" class="mt-1 block rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
            </div>
            <div class="flex gap-2">
                <x-filament::button wire:click="downloadPdf" color="danger">Download PDF</x-filament::button>
                <x-filament::button wire:click="downloadExcel" color="success">Download Excel</x-filament::button>
            </div>
        </div>
    </div>

    @forelse($data['funds'] as $fund)
        <div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 print:break-before-page">
            <h2 class="mb-2 text-lg font-bold text-gray-900 dark:text-white">{{ $fund->name }}</h2>
            @php($income = $fund->transactions->where('type','debit'))
            @php($expense = $fund->transactions->where('type','credit'))
            @php($openingBalance = (int) ($data['openingBalances'][$fund->id] ?? 0))
            <div class="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-900/30">
                    <p class="text-sm text-slate-700 dark:text-slate-300">Saldo Awal</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">Rp {{ number_format($openingBalance, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-900/30">
                    <p class="text-sm text-emerald-700 dark:text-emerald-300">Pemasukan</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">Rp {{ number_format($income->sum('amount'), 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-rose-50 p-3 dark:bg-rose-900/30">
                    <p class="text-sm text-rose-700 dark:text-rose-300">Pengeluaran</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">Rp {{ number_format($expense->sum('amount'), 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-amber-50 p-3 dark:bg-amber-900/30">
                    <p class="text-sm text-amber-700 dark:text-amber-300">Saldo Akhir</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">Rp {{ number_format($openingBalance + $income->sum('amount') - $expense->sum('amount'), 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-gray-700 dark:text-gray-300">
                    <thead><tr class="border-b border-gray-200 text-left text-gray-500 dark:border-gray-700 dark:text-gray-400"><th>Tanggal</th><th>Kategori</th><th>Deskripsi</th><th>Tipe</th><th class="text-right">Jumlah</th></tr></thead>
                    <tbody>
                        @forelse($fund->transactions as $t)
                            <tr class="border-b border-gray-100 dark:border-gray-700/60">
                                <td>{{ $t->transaction_date?->format('d/m/Y') }}</td>
                                <td>{{ $t->category?->name ?? '-' }}</td>
                                <td>{{ $t->description }}</td>
                                <td>{{ $t->type === 'debit' ? 'Pemasukan' : 'Pengeluaran' }}</td>
                                <td class="text-right">Rp {{ number_format($t->amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-3 text-gray-400 dark:text-gray-500">Tidak ada transaksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="rounded-xl bg-white p-6 text-center text-gray-400 dark:bg-gray-900 dark:text-gray-500">Tidak ada dana.</div>
    @endforelse
</x-filament-panels::page>
