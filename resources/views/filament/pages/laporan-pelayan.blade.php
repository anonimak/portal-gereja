<x-filament-panels::page>
    @include('filament.pages._church-selector')
    @php($data = $this->getReportData())

    <div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-sm font-medium">Tipe</label>
                <select wire:model.live="type" class="mt-1 block rounded-lg border px-3 py-1.5 text-sm">
                    <option value="">Semua</option>
                    @foreach(['pelayan_tamu','majelis_lokal','pendeta_internal'] as $t)
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

    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mb-4">
        <h2 class="font-bold mb-2">Daftar Official</h2>
        <table class="w-full text-sm">
            <thead><tr class="border-b text-left text-gray-500"><th>Tipe</th><th>Nama</th><th>Asal</th><th>Mulai</th><th>Selesai</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($data['officials'] as $o)
                    <tr class="border-b">
                        <td>{{ ucwords(str_replace('_',' ',$o->type)) }}</td>
                        <td class="font-medium">{{ $o->display_name }}</td>
                        <td>{{ $o->origin_church ?: '-' }}</td>
                        <td>{{ $o->start_date?->format('d/m/Y') }}</td>
                        <td>{{ $o->end_date?->format('d/m/Y') ?: '-' }}</td>
                        <td>
                            @if($o->is_active)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">Aktif</span>
                            @else
                                <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-600">Nonaktif</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-3 text-gray-400">Tidak ada official.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h2 class="font-bold mb-2">Rekap Roster per Acara</h2>
        <table class="w-full text-sm">
            <thead><tr class="border-b text-left text-gray-500"><th>Tanggal</th><th>Acara</th><th>Petugas</th><th>Peran</th></tr></thead>
            <tbody>
                @forelse($data['roster'] as $event)
                    @foreach($event->rosters as $r)
                        <tr class="border-b">
                            <td>{{ $event->start_datetime?->format('d/m/Y') }}</td>
                            <td>{{ $event->name }}</td>
                            <td>{{ $r->member?->full_name ?? $r->official?->display_name }}</td>
                            <td>{{ $r->role?->name }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="4" class="py-3 text-gray-400">Tidak ada roster.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
