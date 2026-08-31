<x-filament-panels::page>

    @php
        $reportData = $this->getReportData();
        $startDate = $reportData['startDate'];
        $endDate = $reportData['endDate'];
        $churchName = $reportData['churchName'];
        $churchAddress = $reportData['churchAddress'];
        $periodLabel = $reportData['periodLabel'];
        $editionLabel = $reportData['editionLabel'];
    @endphp

    {{-- ══════════════════ TOOLBAR FILTER (hidden saat print) ══════════════════ --}}
    <div class="print:hidden">
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-5">
            <div class="flex flex-col gap-4">
                {{-- Preset periode --}}
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Periode:</span>
                    <button type="button" wire:click="setPeriod('thisWeek')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 px-3 py-1.5 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Minggu Ini
                    </button>
                    <button type="button" wire:click="setPeriod('lastWeek')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 px-3 py-1.5 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Minggu Lalu
                    </button>
                    <button type="button" wire:click="setPeriod('thisMonth')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 px-3 py-1.5 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Bulan Ini
                    </button>
                </div>

                <div class="flex flex-wrap items-end gap-4">
                    {{-- Rentang tanggal --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1" for="warta-start">
                            Dari Tanggal
                        </label>
                        <input id="warta-start" type="date" wire:model.live="startDate"
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1" for="warta-end">
                            Sampai Tanggal
                        </label>
                        <input id="warta-end" type="date" wire:model.live="endDate"
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>

                    {{-- Navigasi minggu --}}
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="shiftWeek(-1)" aria-label="Minggu sebelumnya"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <button type="button" wire:click="shiftWeek(1)" aria-label="Minggu berikutnya"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- Aksi: print + export --}}
                    <div class="ms-auto flex flex-wrap items-center gap-2">
                        <button type="button" onclick="window.print()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                            Cetak / PDF
                        </button>

                        @if (\Illuminate\Support\Facades\Route::has('warta-jemaat.export-pdf'))
                            <form method="POST" action="{{ route('warta-jemaat.export-pdf') }}" class="inline">
                                @csrf
                                <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                                <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white px-3 py-2 text-sm font-semibold transition-colors shadow-sm">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Unduh PDF
                                </button>
                            </form>
                        @else
                            <span title="Endpoint export PDF disiapkan backend (Byte)"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 px-3 py-2 text-sm font-semibold cursor-not-allowed">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Unduh PDF
                            </span>
                        @endif

                        @if (\Illuminate\Support\Facades\Route::has('warta-jemaat.export-excel'))
                            <form method="POST" action="{{ route('warta-jemaat.export-excel') }}" class="inline">
                                @csrf
                                <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                                <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 text-sm font-semibold transition-colors shadow-sm">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Unduh Excel
                                </button>
                            </form>
                        @else
                            <span title="Endpoint export Excel disiapkan backend (Byte)"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 px-3 py-2 text-sm font-semibold cursor-not-allowed">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Unduh Excel
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════ DOKUMEN WARTA ══════════════════ --}}
    <div class="fi-warta-wrap mx-auto max-w-4xl bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden print:shadow-none print:ring-0 print:rounded-none">

        {{-- 1. Header / Kop --}}
        <div class="px-8 py-10 text-center border-b-2 border-amber-500/30 bg-gradient-to-b from-amber-50/60 to-white dark:from-amber-900/10 dark:to-gray-800 print:bg-white print:border-b-2">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-3xl shadow-md">
                <span>⛪</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white uppercase sm:text-3xl print:text-2xl">
                {{ $churchName }}
            </h1>
            @if ($churchAddress)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $churchAddress }}</p>
            @endif
            <div class="mt-6">
                <h2 class="text-3xl font-black text-amber-700 dark:text-amber-400 tracking-wide">Warta Jemaat</h2>
                <p class="mt-1 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                    {{ $editionLabel }}
                </p>
            </div>
            <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-amber-100/80 dark:bg-amber-900/30 px-4 py-1.5 text-sm font-semibold text-amber-900 dark:text-amber-200 ring-1 ring-amber-300/60 dark:ring-amber-700/50">
                📅 {{ $periodLabel }}
            </div>
        </div>

        {{-- 2. Salam / Renungan --}}
        <div class="px-8 pt-8 print:pt-6">
            <div class="rounded-xl bg-gray-50 dark:bg-gray-700/40 p-6 ring-1 ring-gray-100 dark:ring-gray-600/50">
                <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                    <span class="font-bold text-amber-700 dark:text-amber-400">Salam Damai Sejahtera,</span><br>
                    Selamat datang dalam Warta Jemaat minggu ini. Kiranya damai sejahtera Kristus senantiasa menyertai
                    kita sekalian. Selamat beribadah dan Tuhan Yesus memberkati.
                </p>
            </div>
        </div>

        {{-- 3. Agenda / Jadwal Ibadah & Pelayanan --}}
        <div class="px-8 pt-10 print:pt-8">
            <div class="flex items-center gap-3 mb-5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Agenda &amp; Jadwal Ibadah</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Kegiatan beserta petugas pelayanan</p>
                </div>
            </div>

            @if ($reportData['events']->count() > 0)
                <div class="space-y-4">
                    @foreach ($reportData['events'] as $event)
                        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 print:border-gray-400">
                            <div class="flex items-center gap-4 border-l-4 border-blue-500 bg-blue-50/70 dark:bg-blue-900/20 px-5 py-4 print:bg-white print:border-l-2">
                                <div class="text-center shrink-0">
                                    <div class="text-lg font-black leading-none text-blue-700 dark:text-blue-300">
                                        {{ $event->start_datetime->format('d') }}
                                    </div>
                                    <div class="text-xs font-semibold uppercase text-blue-600/80 dark:text-blue-400/80">
                                        {{ $event->start_datetime->locale('id')->translatedFormat('M') }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-bold text-gray-900 dark:text-white">{{ $event->title }}</h3>
                                        <span class="rounded-full bg-blue-100 dark:bg-blue-900/40 px-2.5 py-0.5 text-xs font-semibold text-blue-800 dark:text-blue-300">
                                            {{ $event->category->name ?? 'Ibadah' }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ $event->start_datetime->format('H:i') }}–{{ $event->end_datetime->format('H:i') }}
                                        </span>
                                        <span class="mx-2 text-gray-300 dark:text-gray-600">•</span>
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            {{ $event->location ?? 'Gereja' }}
                                        </span>
                                        @if ($event->total_attendance > 0)
                                            <span class="mx-2 text-gray-300 dark:text-gray-600">•</span>
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                                {{ $event->total_attendance }} jemaat
                                            </span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if ($event->rosters->count() > 0)
                                <div class="bg-white dark:bg-gray-800 px-5 py-3">
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        Petugas Pelayanan
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($event->rosters as $roster)
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs font-medium text-gray-700 dark:text-gray-200">
                                                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                                {{ $roster->member?->full_name ?? $roster->official?->display_name ?? 'Petugas' }}
                                                <span class="text-gray-400 dark:text-gray-500">({{ $roster->role->name ?? 'Pelayan' }})</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-600 p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    Belum ada kegiatan pada periode ini.
                </div>
            @endif
        </div>

        {{-- 4. Ulang Tahun Jemaat --}}
        <div class="px-8 pt-10 print:pt-8">
            <div class="flex items-center gap-3 mb-5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-pink-100 dark:bg-pink-900/40 text-pink-700 dark:text-pink-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12h18m-9-9a3 3 0 00-3 3h6a3 3 0 00-3-3zm6 9v6a3 3 0 01-3 3H9a3 3 0 01-3-3v-6m12 0a6 6 0 00-12 0"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Ulang Tahun Jemaat</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Mari doakan saudara/i kita yang berulang tahun</p>
                </div>
            </div>

            @if ($reportData['birthdays']->count() > 0)
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 print:grid-cols-3">
                    @foreach ($reportData['birthdays'] as $member)
                        <div class="flex items-center gap-3 rounded-xl border border-pink-200 dark:border-pink-800 bg-pink-50/50 dark:bg-pink-900/10 p-4 print:border-gray-400 print:bg-white">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-pink-200 dark:bg-pink-900/50 text-lg">🎂</div>
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $member->full_name }}</h3>
                                @if ($member->birth_date)
                                    <p class="text-xs font-medium text-pink-600 dark:text-pink-400">
                                        {{ \Illuminate\Support\Carbon::parse($member->birth_date)->locale('id')->translatedFormat('d F') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-600 p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    Tidak ada ulang tahun pada periode ini.
                </div>
            @endif
        </div>

        {{-- 5. Perayaan Sakramen / Berita Jemaat --}}
        <div class="px-8 pt-10 print:pt-8">
            <div class="flex items-center gap-3 mb-5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Berita Jemaat &amp; Perayaan Sakramen</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Peristiwa syukur dalam kehidupan jemaat</p>
                </div>
            </div>

            @if ($reportData['sacraments']->count() > 0)
                <div class="space-y-3">
                    @foreach ($reportData['sacraments'] as $sacrament)
                        @php
                            $badge = match ($sacrament->type) {
                                'penyerahan' => ['bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300', 'Penyerahan Anak'],
                                'baptis_anak' => ['bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300', 'Baptis Anak'],
                                'sidi' => ['bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300', 'Sidi'],
                                'baptis_dewasa' => ['bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300', 'Baptis Dewasa'],
                                'nikah' => ['bg-pink-100 text-pink-800 dark:bg-pink-900/40 dark:text-pink-300', 'Pemberkatan Nikah'],
                                default => ['bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', $sacrament->type],
                            };
                        @endphp
                        <div class="flex flex-col gap-2 rounded-xl border border-gray-200 dark:border-gray-700 p-4 sm:flex-row sm:items-center sm:justify-between print:border-gray-400">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $badge[0] }} text-lg">✦</div>
                                <div>
                                    <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-bold {{ $badge[0] }}">
                                        {{ $badge[1] }}
                                    </span>
                                    <h3 class="mt-1 font-semibold text-gray-900 dark:text-white">
                                        {{ $sacrament->member?->full_name ?? 'Jemaat' }}
                                    </h3>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-600 dark:text-gray-400 sm:shrink-0">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ $sacrament->sacrament_date->locale('id')->translatedFormat('d F Y') }}
                                </span>
                                @if ($sacrament->official?->display_name)
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        {{ $sacrament->official->display_name }}
                                    </span>
                                @endif
                                @if ($sacrament->certificate_number)
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                                        </svg>
                                        No. {{ $sacrament->certificate_number }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-600 p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    Tidak ada perayaan sakramen pada periode ini.
                </div>
            @endif
        </div>

        {{-- 6. Laporan Keuangan Ringkas --}}
        <div class="px-8 pt-10 print:pt-8">
            <div class="flex items-center gap-3 mb-5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h2m5 0h2m-9 4h10a2 2 0 002-2V8a2 2 0 00-2-2H7a2 2 0 00-2 2v9a2 2 0 002 2zm0-13V3m2 1h6"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Laporan Keuangan Ringkas</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Ringkasan kas gereja periode ini</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 print:grid-cols-3">
                <div class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50/60 dark:bg-emerald-900/10 p-4 text-center print:bg-white print:border-gray-400">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Pemasukan</p>
                    <p class="mt-1 text-xl font-black text-emerald-700 dark:text-emerald-300">
                        Rp{{ number_format($reportData['totalIncome'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50/60 dark:bg-red-900/10 p-4 text-center print:bg-white print:border-gray-400">
                    <p class="text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-300">Pengeluaran</p>
                    <p class="mt-1 text-xl font-black text-red-700 dark:text-red-300">
                        Rp{{ number_format($reportData['totalExpenses'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40 p-4 text-center print:bg-white print:border-gray-400">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Saldo Akhir</p>
                    <p class="mt-1 text-xl font-black text-gray-900 dark:text-white">
                        Rp{{ number_format($reportData['closingBalance'], 0, ',', '.') }}
                    </p>
                </div>
            </div>

            @if ($reportData['transactions']->count() > 0)
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <th scope="col" class="px-3 py-2 font-semibold">Tanggal</th>
                                <th scope="col" class="px-3 py-2 font-semibold">Kategori</th>
                                <th scope="col" class="px-3 py-2 font-semibold">Keterangan</th>
                                <th scope="col" class="px-3 py-2 text-right font-semibold">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            @foreach ($reportData['transactions'] as $type => $transactionList)
                                @foreach ($transactionList as $transaction)
                                    <tr class="text-gray-700 dark:text-gray-300">
                                        <td class="px-3 py-2 whitespace-nowrap">{{ $transaction->transaction_date->locale('id')->translatedFormat('d M') }}</td>
                                        <td class="px-3 py-2">
                                            <span class="inline-block rounded px-2 py-0.5 text-xs font-semibold {{ $type === 'Pemasukan' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' }}">
                                                {{ $transaction->category->name ?? ($type === 'Pemasukan' ? 'Pemasukan' : 'Pengeluaran') }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 max-w-xs truncate">{{ $transaction->description ?? '-' }}</td>
                                        <td class="px-3 py-2 text-right font-bold {{ $type === 'Pemasukan' ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                                            {{ $type === 'Pemasukan' ? '+' : '−' }}Rp{{ number_format($transaction->amount, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="mt-4 rounded-xl border border-dashed border-gray-300 dark:border-gray-600 p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    Tidak ada transaksi pada periode ini.
                </div>
            @endif
        </div>

        {{-- 7. Footer --}}
        <div class="mt-10 border-t border-gray-200 dark:border-gray-700 px-8 py-8 text-center print:border-gray-300">
            <div class="mx-auto grid max-w-xl grid-cols-2 gap-8">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Pendeta</p>
                    <div class="mt-16 border-t border-dotted border-gray-300 dark:border-gray-600 pt-2">
                        <p class="text-xs text-gray-400 dark:text-gray-500">( .................................... )</p>
                    </div>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Sekretaris</p>
                    <div class="mt-16 border-t border-dotted border-gray-300 dark:border-gray-600 pt-2">
                        <p class="text-xs text-gray-400 dark:text-gray-500">( .................................... )</p>
                    </div>
                </div>
            </div>
            <p class="mt-6 text-xs text-gray-400 dark:text-gray-500">Diterbitkan oleh Portal Gereja • {{ now()->locale('id')->translatedFormat('d F Y H:i') }}</p>
        </div>
    </div>

    <style>
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                background: #fff !important;
            }

            .fi-sidebar,
            .fi-topbar,
            [role="banner"],
            aside,
            nav {
                display: none !important;
            }

            .fi-warta-wrap {
                max-width: 100% !important;
                box-shadow: none !important;
            }

            main {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .fi-warta-wrap .px-8 {
                padding-left: 1.25rem;
                padding-right: 1.25rem;
            }

            @page {
                size: A4;
                margin: 12mm;
            }
        }
    </style>
</x-filament-panels::page>
