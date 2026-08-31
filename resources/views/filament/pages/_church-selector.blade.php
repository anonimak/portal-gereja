@if($this->canSelectChurch())
    <div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-center gap-3">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Gereja:</label>
            <select
                wire:model.live="churchSelect"
                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
            >
                <option value="">— Semua Gereja —</option>
                @foreach($this->churchOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                @if($this->isAllChurches()) Menampilkan semua gereja @else Menampilkan gereja terpilih @endif
            </span>
        </div>
    </div>
@endif
