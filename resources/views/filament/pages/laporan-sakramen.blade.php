<x-filament-panels::page>
    @include('filament.pages._church-selector')
    @php($data = $this->getReportData())

    <div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Sakramen</label>
                <select wire:model.live="type" class="mt-1 block rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">Semua</option>
                    @foreach(['penyerahan','baptis_anak','sidi','baptis_dewasa','nikah'] as $t)
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

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="mb-2 font-bold text-gray-900 dark:text-white">Ringkasan per Jenis</h2>
            @foreach($data['byType'] as $type => $count)
                <div class="flex justify-between border-b border-gray-100 py-1 text-sm text-gray-700 dark:border-gray-700/60 dark:text-gray-300"><span>{{ ucwords(str_replace('_',' ',$type)) }}</span><span class="font-semibold text-gray-900 dark:text-white">{{ $count }}</span></div>
            @endforeach
        </div>
    </div>

    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h2 class="mb-2 font-bold text-gray-900 dark:text-white">Detail Sakramen</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-700 dark:text-gray-300">
                <thead><tr class="border-b border-gray-200 text-left text-gray-500 dark:border-gray-700 dark:text-gray-400"><th>Tanggal</th><th>Jenis</th><th>Nama</th><th>Pelayan</th><th>No. Sertifikat</th></tr></thead>
                <tbody>
                    @forelse($data['sacraments'] as $s)
                        <tr class="border-b border-gray-100 dark:border-gray-700/60">
                            <td>{{ $s->sacrament_date?->format('d/m/Y') }}</td>
                            <td>{{ ucwords(str_replace('_',' ',$s->type)) }}</td>
                            <td class="font-medium text-gray-900 dark:text-white">{{ $s->member?->full_name }}</td>
                            <td>{{ $s->official?->display_name }}</td>
                            <td>{{ $s->certificate_number }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-3 text-gray-400 dark:text-gray-500">Tidak ada sakramen.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
