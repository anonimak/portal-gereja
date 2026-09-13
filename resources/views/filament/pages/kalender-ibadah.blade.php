<x-filament-panels::page>
    @include('filament.pages._church-selector')
    @php($cal = $this->getCalendarData())

    {{-- Filter dan Navigasi Bulan --}}
    <div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <x-filament::button wire:click="prevMonth" color="gray" size="sm" icon="heroicon-m-chevron-left">
                    Sebelumnya
                </x-filament::button>
                <x-filament::button wire:click="todayMonth" color="gray" size="sm">
                    Bulan Ini
                </x-filament::button>
                <x-filament::button wire:click="nextMonth" color="gray" size="sm" icon="heroicon-m-chevron-right" icon-position="after">
                    Berikutnya
                </x-filament::button>

                <div class="ml-2">
                    <input
                        type="month"
                        wire:model.live="month"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                    />
                </div>

                <div class="ml-2">
                    <select
                        wire:model.live="categoryId"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                    >
                        <option value="">— Semua Kategori —</option>
                        @foreach($cal['categories'] as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a
                    href="{{ \App\Filament\Clusters\Events\Resources\Event\EventResource::getUrl('create') }}"
                    class="inline-flex items-center gap-1 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-primary-500"
                >
                    <x-filament::icon icon="heroicon-m-plus" class="h-4 w-4" />
                    Tambah Acara
                </a>
                <a
                    href="{{ \App\Filament\Clusters\Events\Resources\RecurringSchedule\RecurringScheduleResource::getUrl('index') }}"
                    class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <x-filament::icon icon="heroicon-m-arrow-path" class="h-4 w-4" />
                    Jadwal Berulang
                </a>
                <a
                    href="{{ \App\Filament\Clusters\Events\Resources\Event\EventResource::getUrl('index') }}"
                    class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <x-filament::icon icon="heroicon-m-table-cells" class="h-4 w-4" />
                    Tampilan Tabel
                </a>
            </div>
        </div>

        <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-400">
            <div>
                <span class="font-bold text-gray-900 dark:text-white">{{ $cal['monthLabel'] }}</span>
                @if($cal['churchName'])
                    • <span class="text-xs">{{ $cal['churchName'] }}</span>
                @endif
            </div>
            <div class="text-xs">
                Total: <span class="font-semibold text-primary-600 dark:text-primary-400">{{ $cal['totalInMonth'] }} acara</span> pada bulan ini
            </div>
        </div>
    </div>

    {{-- Grid Kalender --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
        {{-- Header Nama Hari --}}
        <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50 text-center text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300">
            <div class="py-2.5 text-red-600 dark:text-red-400">Minggu</div>
            <div class="py-2.5">Senin</div>
            <div class="py-2.5">Selasa</div>
            <div class="py-2.5">Rabu</div>
            <div class="py-2.5">Kamis</div>
            <div class="py-2.5">Jumat</div>
            <div class="py-2.5">Sabtu</div>
        </div>

        {{-- Isi Kalender Per Minggu --}}
        <div class="divide-y divide-gray-200 dark:divide-gray-800">
            @foreach($cal['weeks'] as $week)
                <div class="grid grid-cols-7 divide-x divide-gray-200 dark:divide-gray-800 min-h-[110px]">
                    @foreach($week as $day)
                        <div class="p-1.5 transition-colors flex flex-col justify-between {{ $day['isCurrentMonth'] ? 'bg-white dark:bg-gray-900' : 'bg-gray-50/50 text-gray-400 dark:bg-gray-950/30 dark:text-gray-600' }} {{ $day['isToday'] ? 'ring-2 ring-inset ring-primary-500/50 bg-primary-50/20 dark:bg-primary-950/20' : '' }}">
                            <div class="flex items-center justify-between mb-1">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold {{ $day['isToday'] ? 'bg-primary-600 text-white' : ($day['isCurrentMonth'] ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-600') }}">
                                    {{ $day['day'] }}
                                </span>
                                @if($day['events']->isNotEmpty())
                                    <span class="text-[10px] font-medium text-gray-400 dark:text-gray-500">
                                        {{ $day['events']->count() }} acara
                                    </span>
                                @endif
                            </div>

                            {{-- Daftar Acara Hari Ini --}}
                            <div class="space-y-1 overflow-y-auto max-h-24">
                                @foreach($day['events'] as $evt)
                                    <a
                                        href="{{ \App\Filament\Clusters\Events\Resources\Event\EventResource::getUrl('edit', ['record' => $evt->id]) }}"
                                        class="block rounded border border-gray-200 bg-white p-1 text-[11px] shadow-2xs hover:border-primary-400 hover:shadow-xs dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-500 transition-all"
                                        title="{{ $evt->title }} ({{ $evt->start_datetime->format('H:i') }})"
                                    >
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="font-bold text-primary-700 dark:text-primary-300">
                                                {{ $evt->start_datetime->format('H:i') }}
                                            </span>
                                            @if($evt->recurring_schedule_id)
                                                <span class="text-[10px] text-emerald-600 dark:text-emerald-400" title="Jadwal Berulang">🔄</span>
                                            @endif
                                        </div>
                                        <div class="truncate font-medium text-gray-900 dark:text-gray-100">
                                            {{ $evt->title }}
                                        </div>
                                        @if($evt->category)
                                            <div class="truncate text-[10px] text-gray-500 dark:text-gray-400">
                                                {{ $evt->category->name }}
                                            </div>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
