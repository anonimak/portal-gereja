<x-filament-panels::page>
    @include('filament.pages._church-selector')
    @php($data = $this->getReportData())

    <div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Acara</label>
                <select wire:model.live="eventId" class="mt-1 block rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">Semua Acara</option>
                    @foreach(\App\Models\Event::query()->orderBy('start_datetime','desc')->get() as $event)
                        <option value="{{ $event->id }}">{{ $event->name }} — {{ $event->start_datetime?->format('d/m/Y') }}</option>
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
        <h2 class="mb-2 font-bold text-gray-900 dark:text-white">Per Acara</h2>
        <table class="w-full text-sm text-gray-700 dark:text-gray-300">
            <thead><tr class="border-b border-gray-200 text-left text-gray-500 dark:border-gray-700 dark:text-gray-400"><th>Waktu</th><th>Acara</th><th>Hadir</th><th>Tidak Hadir</th><th>Total</th></tr></thead>
            <tbody>
                @forelse($data['events'] as $event)
                    @php($hadir = $event->attendances->where('status','hadir')->count())
                    @php($tidak = $event->attendances->where('status','tidak_hadir')->count())
                    <tr class="border-b border-gray-100 dark:border-gray-700/60">
                        <td>{{ $event->start_datetime?->format('d/m/Y H:i') }}</td>
                        <td class="font-medium text-gray-900 dark:text-white">{{ $event->name }}</td>
                        <td>{{ $hadir }}</td>
                        <td>{{ $tidak }}</td>
                        <td>{{ $event->total_attendance }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-3 text-gray-400 dark:text-gray-500">Tidak ada acara.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
