<x-filament-panels::page>
    @include('filament.pages._church-selector')
    @php($data = $this->getReportData())

    <div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Tipe</label>
                <select wire:model.live="type" class="mt-1 block rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">Semua</option>
                    @foreach(['pelayan_tamu','majelis_lokal','pendeta_internal'] as $t)
                        <option value="{{ $t }}">{{ ucwords(str_replace('_',' ',$t)) }}</option>
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

    <div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h2 class="mb-2 font-bold text-gray-900 dark:text-white">Daftar Official</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-700 dark:text-gray-300">
                <thead><tr class="border-b border-gray-200 text-left text-gray-500 dark:border-gray-700 dark:text-gray-400"><th>Tipe</th><th>Nama</th><th>Asal</th><th>Mulai</th><th>Selesai</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($data['officials'] as $o)
                        <tr class="border-b border-gray-100 dark:border-gray-700/60">
                            <td>{{ ucwords(str_replace('_',' ',$o->type)) }}</td>
                            <td class="font-medium text-gray-900 dark:text-white">{{ $o->display_name }}</td>
                            <td>{{ $o->origin_church ?: '-' }}</td>
                            <td>{{ $o->start_date?->format('d/m/Y') }}</td>
                            <td>{{ $o->end_date?->format('d/m/Y') ?: '-' }}</td>
                            <td>
                                @if($o->is_active)
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                                @else
                                    <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">Nonaktif</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-3 text-gray-400 dark:text-gray-500">Tidak ada official.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h2 class="mb-2 font-bold text-gray-900 dark:text-white">Rekap Roster per Acara</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-700 dark:text-gray-300">
                <thead><tr class="border-b border-gray-200 text-left text-gray-500 dark:border-gray-700 dark:text-gray-400"><th>Tanggal</th><th>Acara</th><th>Petugas</th><th>Peran</th></tr></thead>
                <tbody>
                    @forelse($data['roster'] as $event)
                        @foreach($event->rosters as $r)
                            <tr class="border-b border-gray-100 dark:border-gray-700/60">
                                <td>{{ $event->start_datetime?->format('d/m/Y') }}</td>
                                <td>{{ $event->name }}</td>
                                <td>{{ $r->member?->full_name ?? $r->official?->display_name }}</td>
                                <td>{{ $r->role?->name }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="4" class="py-3 text-gray-400 dark:text-gray-500">Tidak ada roster.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
