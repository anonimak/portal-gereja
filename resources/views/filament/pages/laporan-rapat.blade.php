<x-filament-panels::page>

    @php
        $reportData = $this->reportData;
    @endphp

    <div class="laporan-wrap">

        {{-- ═══ FILTER & EXPORT ═══ --}}
        <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700 print:hidden" wire:key="filter-section">
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[1fr_auto]">

                {{-- Filter --}}
                <div wire:key="form-container">
                    <p class="mb-3 text-[11px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">Filter Laporan</p>
                    {{ $this->form }}
                </div>

                {{-- Export --}}
                <div class="min-w-[220px]">
                    <p class="mb-3 text-[11px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">Export</p>
                    <div class="flex flex-col gap-2">

                        <button type="button" onclick="setTimeout(() => window.print(), 300)"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-2H7v2a2 2 0 002 2z" />
                            </svg>
                            Cetak PDF
                        </button>

                        <a href="{{ route('laporan-rapat.export-excel') }}" class="btn-excel inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600"
                            onclick="document.getElementById('export-form').submit(); return false;">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export Excel
                        </a>

                        <form id="export-form" action="{{ route('laporan-rapat.export-excel') }}" method="POST" class="hidden">
                            @csrf
                            <input type="hidden" name="period_type" value="">
                            <input type="hidden" name="month" value="">
                            <input type="hidden" name="quarter" value="">
                            <input type="hidden" name="year" value="">
                        </form>

                        <script>
                            document.getElementById('export-form').addEventListener('submit', function() {
                                const inputs = document.querySelectorAll('form:not(#export-form) input[name]');
                                inputs.forEach(input => {
                                    const target = document.querySelector('#export-form input[name="' + input.name + '"]');
                                    if (target) target.value = input.value;
                                });
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ PRINT HEADER ═══ --}}
        <div class="print-header mb-8 hidden border-b-2 border-gray-200 pb-5 text-center dark:border-gray-700 print:block"
            style="margin-bottom: 32px; padding-bottom: 20px;">
            <h1 class="m-0 text-2xl font-extrabold text-gray-900 dark:text-white">{{ $reportData['churchName'] }}</h1>
            <h2 class="m-0 mb-1 text-[15px] font-semibold text-gray-500 dark:text-gray-400">Laporan Rapat & Keuangan</h2>
            <p class="m-0 text-[13px] text-gray-400 dark:text-gray-500">Periode: <strong class="text-gray-700 dark:text-gray-300">{{ $reportData['periodLabel'] }}</strong></p>
        </div>

        {{-- ═══ LAPORAN PELAYANAN ═══ --}}
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
            <div class="flex items-center gap-3 border-b border-gray-200 px-6 py-5 dark:border-gray-700">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/40">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-blue-700 dark:text-blue-300">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="m-0 text-[15px] font-bold text-gray-900 dark:text-white">Laporan Pelayanan & Kehadiran</h2>
                    <p class="m-0 mt-0.5 text-xs text-gray-500 dark:text-gray-400">Data kegiatan dan kehadiran jemaat</p>
                </div>
            </div>

            @if ($reportData['events']->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gray-400 dark:text-gray-500">
                    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="mx-auto mb-3 opacity-40">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    Tidak ada kegiatan pada periode ini
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/40">
                                <th class="whitespace-nowrap px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tanggal</th>
                                <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Kegiatan</th>
                                <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Kategori</th>
                                <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Pelayan</th>
                                <th class="px-5 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">♂ L</th>
                                <th class="px-5 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">♀ P</th>
                                <th class="px-5 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            @foreach ($reportData['events'] as $event)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/20">
                                    <td class="whitespace-nowrap px-5 py-3.5 text-[13px] text-gray-500 dark:text-gray-400">
                                        {{ $event->start_datetime->locale('id')->format('d M Y') }}
                                    </td>
                                    <td class="min-w-[160px] px-5 py-3.5 text-[13px] font-semibold text-gray-900 dark:text-white">
                                        {{ $event->title }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="inline-block rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">{{ $event->category->name ?? '-' }}</span>
                                    </td>
                                    <td class="min-w-[160px] px-5 py-3.5">
                                        @forelse ($event->rosters as $roster)
                                            <div class="mb-0.5 text-[11.5px] leading-snug text-gray-500 dark:text-gray-400">
                                                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $roster->role->name ?? '-' }}:</span>
                                                @if ($roster->member_id)
                                                    {{ $roster->member->full_name ?? '-' }}
                                                @elseif ($roster->official_id)
                                                    {{ $roster->official->display_name ?? '-' }}
                                                @else
                                                    -
                                                @endif
                                            </div>
                                        @empty
                                            <span class="text-xs text-gray-300 dark:text-gray-600">—</span>
                                        @endforelse
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $event->attendance_male ?? 0 }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $event->attendance_female ?? 0 }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="text-sm font-extrabold text-blue-700 dark:text-blue-400">{{ $event->total_attendance }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- ═══ LAPORAN KEUANGAN ═══ --}}
        <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
            <div class="flex items-center gap-3 border-b border-gray-200 px-6 py-5 dark:border-gray-700">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/40">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-emerald-700 dark:text-emerald-300">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 01-.75.75h-.75M6.75 8.25h10.5" />
                    </svg>
                </div>
                <div>
                    <h2 class="m-0 text-[15px] font-bold text-gray-900 dark:text-white">Laporan Keuangan</h2>
                    <p class="m-0 mt-0.5 text-xs text-gray-500 dark:text-gray-400">Ringkasan pemasukan, pengeluaran, dan saldo</p>
                </div>
            </div>

            {{-- Summary --}}
            <div class="grid grid-cols-1 border-b border-gray-200 sm:grid-cols-3 dark:border-gray-700">
                <div class="border-b border-gray-200 p-6 text-center sm:border-b-0 sm:border-r dark:border-gray-700">
                    <p class="mb-2 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">Saldo Awal</p>
                    <p class="m-0 text-2xl font-extrabold leading-none text-blue-700 dark:text-blue-400">Rp {{ number_format($reportData['openingBalance'], 0, ',', '.') }}</p>
                </div>
                <div class="border-b border-gray-200 p-6 text-center sm:border-b-0 sm:border-r dark:border-gray-700">
                    <p class="mb-2 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">Pemasukan</p>
                    <p class="m-0 text-2xl font-extrabold leading-none text-emerald-700 dark:text-emerald-400">Rp {{ number_format($reportData['totalIncome'], 0, ',', '.') }}</p>
                </div>
                <div class="p-6 text-center">
                    <p class="mb-2 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">Pengeluaran</p>
                    <p class="m-0 text-2xl font-extrabold leading-none text-red-700 dark:text-red-400">Rp {{ number_format($reportData['totalExpenses'], 0, ',', '.') }}</p>
                </div>
            </div>

            {{-- Income & Expense tables --}}
            <div class="grid grid-cols-1 border-b border-gray-200 md:grid-cols-2 dark:border-gray-700">
                {{-- Income --}}
                <div class="md:border-r border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2 border-b border-gray-200 bg-emerald-50 px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-emerald-700 dark:border-gray-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" />
                        </svg>
                        Pemasukan
                    </div>
                    @if ($reportData['income']->isEmpty())
                        <div class="px-6 py-6 text-center text-sm text-gray-400 dark:text-gray-500">Tidak ada data</div>
                    @else
                        @foreach ($reportData['income'] as $item)
                            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3 text-[13px] last:border-b-0 dark:border-gray-700/60">
                                <span class="text-gray-500 dark:text-gray-400">{{ $item['category'] }}</span>
                                <span class="font-bold text-gray-900 dark:text-white">Rp {{ number_format($item['total'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- Expenses --}}
                <div>
                    <div class="flex items-center gap-2 border-b border-gray-200 bg-red-50 px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-red-700 dark:border-gray-700 dark:bg-red-900/20 dark:text-red-300">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                        </svg>
                        Pengeluaran
                    </div>
                    @if ($reportData['expenses']->isEmpty())
                        <div class="px-6 py-6 text-center text-sm text-gray-400 dark:text-gray-500">Tidak ada data</div>
                    @else
                        @foreach ($reportData['expenses'] as $item)
                            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3 text-[13px] last:border-b-0 dark:border-gray-700/60">
                                <span class="text-gray-500 dark:text-gray-400">{{ $item['category'] }}</span>
                                <span class="font-bold text-gray-900 dark:text-white">Rp {{ number_format($item['total'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- Closing Balance --}}
            <div class="p-8 text-center">
                <p class="mb-2.5 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">Saldo Akhir</p>
                <p class="m-0 text-3xl font-extrabold leading-none {{ $reportData['closingBalance'] >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400' }}">
                    Rp {{ number_format($reportData['closingBalance'], 0, ',', '.') }}
                </p>
                <div class="mt-3">
                    <span class="inline-flex items-center gap-1 rounded-full px-4 py-1.5 text-xs font-semibold {{ $reportData['closingBalance'] >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                        @if ($reportData['closingBalance'] >= 0)
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Surplus
                        @else
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            Defisit
                        @endif
                    </span>
                </div>
            </div>
        </div>

    </div>{{-- end .laporan-wrap --}}

    {{-- Pemilih gereja super_admin (Fase 3A §9) --}}
    @include('filament.pages._church-selector')

    <div class="mb-4 flex gap-2 print:hidden">
        <x-filament::button wire:click="downloadPdf" color="danger">Download PDF</x-filament::button>
        <x-filament::button wire:click="downloadExcel" color="success">Download Excel</x-filament::button>
    </div>

    {{-- Notulen Rapat (Fase 3A §8) --}}
    <div class="laporan-wrap mt-6">
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
            <div class="flex items-center gap-3 border-b border-gray-200 px-6 py-5 dark:border-gray-700">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <h2 class="m-0 text-[15px] font-bold text-gray-900 dark:text-white">Notulen Rapat</h2>
                    <p class="m-0 mt-0.5 text-xs text-gray-500 dark:text-gray-400">Agenda, peserta, notulen, keputusan</p>
                </div>
            </div>

            @if($this->canCreateMinutes())
                <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700">
                    <h3 class="mb-3 m-0 text-sm font-bold text-gray-900 dark:text-white">Tambah Notulen</h3>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <input wire:model="minuteTitle" type="text" placeholder="Judul rapat"
                               class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-[13px] text-gray-900 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500">
                        <input wire:model="minuteDate" type="date"
                               class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-[13px] text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        <textarea wire:model="minuteAgenda" placeholder="Agenda (satu per baris)" rows="3"
                                  class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-[13px] text-gray-900 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500 md:col-span-2"></textarea>
                        <textarea wire:model="minuteNotes" placeholder="Notulen / jalannya rapat" rows="3"
                                  class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-[13px] text-gray-900 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500 md:col-span-2"></textarea>
                        <textarea wire:model="minuteDecisions" placeholder="Keputusan (satu per baris)" rows="3"
                                  class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-[13px] text-gray-900 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500 md:col-span-2"></textarea>
                    </div>
                    <div class="mt-3">
                        <x-filament::button wire:click="saveMinute" color="primary">Simpan Notulen</x-filament::button>
                    </div>
                </div>
            @endif

            <div class="px-6 py-5">
                @forelse($this->getMinutes() as $minute)
                    <div class="mb-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <h4 class="m-0 text-sm font-bold text-gray-900 dark:text-white">{{ $minute->title }}</h4>
                            <span class="shrink-0 text-xs text-gray-400 dark:text-gray-500">{{ $minute->meeting_date?->format('d M Y') }}</span>
                        </div>
                        @if($minute->agenda)
                            <p class="mb-1 mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400">Agenda</p>
                            <ul class="mb-2 m-0 pl-4 text-[13px] text-gray-700 dark:text-gray-300">
                                @foreach($minute->agenda as $item)<li>{{ $item }}</li>@endforeach
                            </ul>
                        @endif
                        @if($minute->notes)
                            <p class="mb-1 mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400">Notulen</p>
                            <p class="m-0 text-[13px] whitespace-pre-wrap text-gray-700 dark:text-gray-300">{{ $minute->notes }}</p>
                        @endif
                        @if($minute->decisions)
                            <p class="mb-1 mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400">Keputusan</p>
                            <ul class="m-0 pl-4 text-[13px] text-gray-700 dark:text-gray-300">
                                @foreach($minute->decisions as $d)<li>{{ $d }}</li>@endforeach
                            </ul>
                        @endif
                    </div>
                @empty
                    <p class="m-0 text-[13px] text-gray-400 dark:text-gray-500">Belum ada notulen pada periode ini.</p>
                @endforelse
            </div>
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

            main {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .laporan-wrap {
                max-width: 100% !important;
            }

            .laporan-wrap .dark\:bg-gray-800 {
                background: #ffffff !important;
            }

            .laporan-wrap .dark\:divide-gray-700\/60 > :not([hidden]) ~ :not([hidden]),
            .laporan-wrap .dark\:border-gray-700 {
                border-color: #e5e7eb !important;
            }

            .laporan-wrap .dark\:text-white {
                color: #111827 !important;
            }

            .laporan-wrap .dark\:text-gray-300,
            .laporan-wrap .dark\:text-gray-400,
            .laporan-wrap .dark\:text-gray-500 {
                color: #4b5563 !important;
            }

            @page {
                size: A4;
                margin: 12mm;
            }
        }
    </style>
</x-filament-panels::page>
