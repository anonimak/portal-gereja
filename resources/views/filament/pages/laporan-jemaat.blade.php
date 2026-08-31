<x-filament-panels::page>
    @include('filament.pages._church-selector')
    @php($data = $this->getReportData())

    <div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select wire:model.live="status" class="mt-1 block rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">Semua</option>
                    @foreach(['aktif','titipan','pindah','meninggal'] as $s)
                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Kelamin</label>
                <select wire:model.live="gender" class="mt-1 block rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">Semua</option>
                    <option value="laki-laki">Laki-laki</option>
                    <option value="perempuan">Perempuan</option>
                </select>
            </div>
            <div class="flex gap-2">
                <x-filament::button wire:click="downloadPdf" color="danger">Download PDF</x-filament::button>
                <x-filament::button wire:click="downloadExcel" color="success">Download Excel</x-filament::button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="mb-2 font-bold text-gray-900 dark:text-white">Per Status</h2>
            <table class="w-full text-sm text-gray-700 dark:text-gray-300">
                @foreach($data['byStatus'] as $status => $count)
                    <tr class="border-b border-gray-100 dark:border-gray-700/60"><td>{{ $status }}</td><td class="text-right font-semibold">{{ $count }}</td></tr>
                @endforeach
                <tr class="font-bold text-gray-900 dark:text-white"><td>Total</td><td class="text-right">{{ $data['members']->count() }}</td></tr>
            </table>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="mb-2 font-bold text-gray-900 dark:text-white">Per Jenis Kelamin</h2>
            <table class="w-full text-sm text-gray-700 dark:text-gray-300">
                @foreach($data['byGender'] as $gender => $count)
                    <tr class="border-b border-gray-100 dark:border-gray-700/60"><td>{{ $gender }}</td><td class="text-right font-semibold">{{ $count }}</td></tr>
                @endforeach
                <tr class="font-bold text-gray-900 dark:text-white"><td>Total</td><td class="text-right">{{ $data['members']->count() }}</td></tr>
            </table>
        </div>
    </div>

    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h2 class="mb-2 font-bold text-gray-900 dark:text-white">Detail Anggota ({{ $data['members']->count() }})</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-700 dark:text-gray-300">
                <thead><tr class="border-b border-gray-200 text-left text-gray-500 dark:border-gray-700 dark:text-gray-400"><th>NIK</th><th>Nama</th><th>JK</th><th>Tgl Lahir</th><th>Keluarga</th><th>Hubungan</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($data['members'] as $member)
                        <tr class="border-b border-gray-100 dark:border-gray-700/60">
                            <td>{{ $member->id_card_number }}</td>
                            <td class="font-medium text-gray-900 dark:text-white">{{ $member->full_name }}</td>
                            <td>{{ $member->gender }}</td>
                            <td>{{ $member->birth_date?->format('d/m/Y') }}</td>
                            <td>{{ $data['familyMap']->get($member->family_id) ?: '-' }}</td>
                            <td>{{ $member->family_relation }}</td>
                            <td>{{ $member->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-3 text-gray-400 dark:text-gray-500">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
