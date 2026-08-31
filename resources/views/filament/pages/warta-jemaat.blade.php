<x-filament-panels::page>
    @include('filament.pages._church-selector')

    <div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Dari</label>
                <input type="date" wire:model.live="startDate" class="mt-1 block rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Sampai</label>
                <input type="date" wire:model.live="endDate" class="mt-1 block rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
            </div>
            <div class="flex gap-2">
                <x-filament::button wire:click="downloadPdf" color="danger">Download PDF</x-filament::button>
                <x-filament::button wire:click="downloadExcel" color="success">Download Excel</x-filament::button>
                <x-filament::button onclick="window.print()">Cetak</x-filament::button>
            </div>
        </div>
    </div>

    @php($data = $this->getReportData())

    <div class="space-y-4 print:space-y-2">
        <div class="rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 p-6 text-white shadow">
            <h1 class="text-2xl font-bold">{{ $data['churchName'] }}</h1>
            <p class="text-sm opacity-90">Warta Jemaat</p>
            <p class="text-sm opacity-90">{{ $data['startDate']->format('d F Y') }} – {{ $data['endDate']->format('d F Y') }}</p>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 print:break-after-page">
            <h2 class="text-lg font-bold mb-2">Jadwal Ibadah &amp; Pelayanan</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left text-gray-500"><th>Waktu</th><th>Acara</th><th>Lokasi</th><th>Petugas</th><th>Kehadiran</th></tr></thead>
                    <tbody>
                        @forelse($data['events'] as $event)
                            <tr class="border-b">
                                <td>{{ $event->start_datetime?->format('d/m/Y H:i') }}</td>
                                <td class="font-medium">{{ $event->name }}</td>
                                <td>{{ $event->location }}</td>
                                <td>{{ $event->rosters->map(fn($r) => $r->member?->full_name ?? $r->official?->display_name)->filter()->implode(', ') }}</td>
                                <td>{{ $event->total_attendance }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-3 text-gray-400">Tidak ada acara pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-lg font-bold mb-2">Ulang Tahun Jemaat</h2>
            <div class="flex flex-wrap gap-2">
                @forelse($data['birthdays'] as $member)
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-sm text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                        {{ \Illuminate\Support\Carbon::parse($member->birth_date)->format('d M') }} — {{ $member->full_name }}
                    </span>
                @empty
                    <span class="text-gray-400 text-sm">Tidak ada.</span>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-lg font-bold mb-2">Perayaan Sakramen</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left text-gray-500"><th>Tanggal</th><th>Jenis</th><th>Nama</th><th>Pelayan</th><th>No. Sertifikat</th></tr></thead>
                    <tbody>
                        @forelse($data['sacraments'] as $sacrament)
                            <tr class="border-b">
                                <td>{{ $sacrament->sacrament_date?->format('d/m/Y') }}</td>
                                <td>{{ $sacrament->type }}</td>
                                <td>{{ $sacrament->member?->full_name }}</td>
                                <td>{{ $sacrament->official?->display_name }}</td>
                                <td>{{ $sacrament->certificate_number }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-3 text-gray-400">Tidak ada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 print:break-before-page">
            <h2 class="text-lg font-bold mb-2">Laporan Keuangan Ringkas</h2>
            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-900/30">
                    <p class="text-sm text-emerald-700 dark:text-emerald-300">Pemasukan</p>
                    <p class="text-lg font-bold">Rp {{ number_format($data['transactions']->get('Pemasukan', collect())->sum('amount'), 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-rose-50 p-3 dark:bg-rose-900/30">
                    <p class="text-sm text-rose-700 dark:text-rose-300">Pengeluaran</p>
                    <p class="text-lg font-bold">Rp {{ number_format($data['transactions']->get('Pengeluaran', collect())->sum('amount'), 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-amber-50 p-3 dark:bg-amber-900/30">
                    <p class="text-sm text-amber-700 dark:text-amber-300">Selisih</p>
                    <p class="text-lg font-bold">Rp {{ number_format($data['transactions']->get('Pemasukan', collect())->sum('amount') - $data['transactions']->get('Pengeluaran', collect())->sum('amount'), 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
