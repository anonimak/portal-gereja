<x-filament-panels::page>
    @include('filament.pages._church-selector')
    @php($data = $this->getReportData())

    <div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-sm font-medium">Acara</label>
                <select wire:model.live="eventId" class="mt-1 block rounded-lg border px-3 py-1.5 text-sm">
                    <option value="">Semua Acara</option>
                    @foreach(\App\Models\Event::query()->orderBy('start_datetime','desc')->get() as $event)
                        <option value="{{ $event->id }}">{{ $event->name }} — {{ $event->start_datetime?->format('d/m/Y') }}</option>
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
        <h2 class="font-bold mb-2">Per Acara</h2>
        <table class="w-full text-sm">
            <thead><tr class="border-b text-left text-gray-500"><th>Waktu</th><th>Acara</th><th>Hadir</th><th>Tidak Hadir</th><th>Total</th></tr></thead>
            <tbody>
                @forelse($data['events'] as $event)
                    @php($hadir = $event->attendances->where('status','hadir')->count())
                    @php($tidak = $event->attendances->where('status','tidak_hadir')->count())
                    <tr class="border-b">
                        <td>{{ $event->start_datetime?->format('d/m/Y H:i') }}</td>
                        <td class="font-medium">{{ $event->name }}</td>
                        <td>{{ $hadir }}</td>
                        <td>{{ $tidak }}</td>
                        <td>{{ $event->total_attendance }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-3 text-gray-400">Tidak ada acara.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
