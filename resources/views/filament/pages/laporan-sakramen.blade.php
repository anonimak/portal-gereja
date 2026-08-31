<x-filament-panels::page>
    @include('filament.pages._church-selector')
    @php($data = $this->getReportData())

    <div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-sm font-medium">Jenis Sakramen</label>
                <select wire:model.live="type" class="mt-1 block rounded-lg border px-3 py-1.5 text-sm">
                    <option value="">Semua</option>
                    @foreach(['penyerahan','baptis_anak','sidi','baptis_dewasa','nikah'] as $t)
                        <option value="{{ $t }}">{{ ucwords(str_replace('_',' ',$t)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Periode (bulan)</label>
                <input type="month" wire:model.live="month" class="mt-1 block rounded-lg border px-3 py-1.5 text-sm">
            </div>
            <div class="flex gap-2">
                <x-filament::button wire:click="downloadPdf" color="danger">Download PDF</x-filament::button>
                <x-filament::button wire:click="downloadExcel" color="success">Download Excel</x-filament::button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="font-bold mb-2">Ringkasan per Jenis</h2>
            @foreach($data['byType'] as $type => $count)
                <div class="flex justify-between border-b py-1 text-sm"><span>{{ ucwords(str_replace('_',' ',$type)) }}</span><span class="font-semibold">{{ $count }}</span></div>
            @endforeach
        </div>
    </div>

    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h2 class="font-bold mb-2">Detail Sakramen</h2>
        <table class="w-full text-sm">
            <thead><tr class="border-b text-left text-gray-500"><th>Tanggal</th><th>Jenis</th><th>Nama</th><th>Pelayan</th><th>No. Sertifikat</th></tr></thead>
            <tbody>
                @forelse($data['sacraments'] as $s)
                    <tr class="border-b">
                        <td>{{ $s->sacrament_date?->format('d/m/Y') }}</td>
                        <td>{{ ucwords(str_replace('_',' ',$s->type)) }}</td>
                        <td class="font-medium">{{ $s->member?->full_name }}</td>
                        <td>{{ $s->official?->display_name }}</td>
                        <td>{{ $s->certificate_number }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-3 text-gray-400">Tidak ada sakramen.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
