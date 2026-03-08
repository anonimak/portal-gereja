<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Section -->
        <div
            class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl shadow-sm border border-amber-200 dark:border-amber-800 p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Pilih Periode Laporan</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        📅 Dari Tanggal
                    </label>
                    <input type="date" wire:model.live="startDate"
                        class="w-full px-4 py-2.5 rounded-lg border-2 border-amber-200 dark:border-amber-700 dark:bg-gray-700 dark:text-white focus:border-amber-500 focus:ring-2 focus:ring-amber-200 dark:focus:ring-amber-800 transition-all duration-200">
                </div>
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        📅 Sampai Tanggal
                    </label>
                    <input type="date" wire:model.live="endDate"
                        class="w-full px-4 py-2.5 rounded-lg border-2 border-amber-200 dark:border-amber-700 dark:bg-gray-700 dark:text-white focus:border-amber-500 focus:ring-2 focus:ring-amber-200 dark:focus:ring-amber-800 transition-all duration-200">
                </div>
            </div>

            <div class="flex gap-3">
                <button onclick="window.print()"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-amber-600 hover:bg-amber-700 dark:bg-amber-700 dark:hover:bg-amber-600 text-white font-semibold rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                        </path>
                    </svg>
                    Cetak / PDF
                </button>
            </div>
        </div>

        <!-- Main Report Content -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 print:rounded-none print:shadow-none print:border-none p-8 print:p-0">
            @php
                $reportData = $this->getReportData();
                $startDate = $reportData['startDate'];
                $endDate = $reportData['endDate'];
            @endphp

            <!-- Header Section -->
            <div
                class="text-center py-8 border-b-2 border-gray-200 dark:border-gray-700 print:border-gray-300 mb-10 print:page-break-after-avoid">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Warta Jemaat</h1>
                <p class="text-gray-500 dark:text-gray-400">📰 Berita dan Laporan Mingguan Jemaah</p>
                <div
                    class="mt-4 inline-block bg-amber-50 dark:bg-amber-900/20 px-4 py-2 rounded-lg border border-amber-200 dark:border-amber-800">
                    <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                        {{ $startDate->format('d F Y') }} - {{ $endDate->format('d F Y') }}
                    </p>
                </div>
            </div>

            <!-- Section 1: Jadwal Pelayanan -->
            <div class="mb-12 print:page-break-inside-avoid">
                <div class="flex items-center gap-3 mb-6 print:page-break-after-avoid">
                    <div class="w-1 h-8 bg-gradient-to-b from-blue-500 to-blue-600 rounded-full"></div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">📅 Jadwal Pelayanan</h2>
                </div>

                @if ($reportData['events']->count() > 0)
                    <div class="space-y-4">
                        @foreach ($reportData['events'] as $event)
                            <div
                                class="border-l-4 border-blue-500 rounded-r-lg bg-blue-50 dark:bg-blue-900/10 p-5 hover:shadow-md transition-shadow duration-200 print:bg-white print:border-l-2 print:border-gray-400">
                                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                    <!-- Event Info -->
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                                {{ $event->start_datetime->format('d M Y') }}
                                            </span>
                                            <span
                                                class="text-sm font-semibold text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2.5 py-1 rounded">
                                                {{ $event->start_datetime->format('H:i') }} -
                                                {{ $event->end_datetime->format('H:i') }}
                                            </span>
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                            {{ $event->title }}</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                            <span
                                                class="inline-block bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 px-2 py-1 rounded text-xs font-medium">
                                                {{ $event->category->name ?? 'Kategori' }}
                                            </span>
                                        </p>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">
                                            <span class="font-semibold">📍 Lokasi:</span>
                                            {{ $event->location ?? 'TBD' }}
                                        </p>
                                    </div>

                                    <!-- Personnel -->
                                    <div class="md:w-80">
                                        <p
                                            class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-3">
                                            Petugas Pelayanan</p>
                                        @if ($event->rosters->count() > 0)
                                            <ul class="space-y-2">
                                                @foreach ($event->rosters as $roster)
                                                    <li class="flex items-center gap-2 text-sm">
                                                        <span
                                                            class="inline-block w-2 h-2 bg-blue-500 rounded-full"></span>
                                                        <span
                                                            class="font-medium text-gray-900 dark:text-white">{{ $roster->member->full_name }}</span>
                                                        <span
                                                            class="text-gray-500 dark:text-gray-400 text-xs">({{ $roster->role->name }})</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">Belum ada petugas
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-8 text-center">
                        <svg class="w-12 h-12 text-gray-400 dark:text-gray-600 mx-auto mb-3" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 font-medium">Tidak ada acara dalam periode ini</p>
                    </div>
                @endif
            </div>

            <!-- Section 2: Ulang Tahun -->
            <div class="mb-12 print:page-break-inside-avoid">
                <div class="flex items-center gap-3 mb-6 print:page-break-after-avoid">
                    <div class="w-1 h-8 bg-gradient-to-b from-pink-500 to-pink-600 rounded-full"></div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">🎂 Jemaat Ulang Tahun</h2>
                </div>

                @if ($reportData['birthdays']->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 print:grid-cols-2">
                        @foreach ($reportData['birthdays'] as $member)
                            <div
                                class="rounded-lg border-2 border-pink-200 dark:border-pink-800 bg-gradient-to-br from-pink-50 to-rose-50 dark:from-pink-900/20 dark:to-rose-900/20 p-5 hover:shadow-md transition-shadow duration-200 print:bg-white print:border-gray-300">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-10 h-10 rounded-full bg-pink-200 dark:bg-pink-900/50 flex items-center justify-center flex-shrink-0">
                                        <span class="text-lg">🎂</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-gray-900 dark:text-white text-sm truncate">
                                            {{ $member->full_name }}
                                        </h3>
                                        <p class="text-sm text-pink-600 dark:text-pink-400 font-medium mt-1">
                                            {{ \Illuminate\Support\Carbon::parse($member->birth_date)->format('d F') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-8 text-center">
                        <svg class="w-12 h-12 text-gray-400 dark:text-gray-600 mx-auto mb-3" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 font-medium">Tidak ada ulang tahun dalam periode ini
                        </p>
                    </div>
                @endif
            </div>

            <!-- Section 2.5: Sakramen Jemaat -->
            <div class="mb-12 print:page-break-inside-avoid">
                <div class="flex items-center gap-3 mb-6 print:page-break-after-avoid">
                    <div class="w-1 h-8 bg-gradient-to-b from-purple-500 to-purple-600 rounded-full"></div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">⛪ Perayaan Sakramen Jemaat</h2>
                </div>

                @if ($reportData['sacraments']->count() > 0)
                    <div class="space-y-3">
                        @foreach ($reportData['sacraments'] as $sacrament)
                            <div
                                class="border-l-4 border-purple-500 rounded-r-lg bg-purple-50 dark:bg-purple-900/10 p-4 hover:shadow-md transition-shadow duration-200 print:bg-white print:border-l-2 print:border-gray-400">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span
                                                class="inline-block px-3 py-1 rounded-full text-xs font-semibold text-white {{ match ($sacrament->type) {
                                                    'penyerahan' => 'bg-blue-500',
                                                    'baptis_anak' => 'bg-cyan-500',
                                                    'sidi' => 'bg-purple-500',
                                                    'baptis_dewasa' => 'bg-green-500',
                                                    'nikah' => 'bg-pink-500',
                                                    default => 'bg-gray-500',
                                                } }}">
                                                {{ match ($sacrament->type) {
                                                    'penyerahan' => 'Penyerahan',
                                                    'baptis_anak' => 'Baptis Anak',
                                                    'sidi' => 'Sidi',
                                                    'baptis_dewasa' => 'Baptis Dewasa',
                                                    'nikah' => 'Nikah',
                                                    default => $sacrament->type,
                                                } }}
                                            </span>
                                            <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                                                {{ $sacrament->sacrament_date->format('d M Y') }}
                                            </span>
                                        </div>
                                        <h3 class="font-semibold text-lg text-gray-900 dark:text-white">
                                            {{ $sacrament->member->full_name }}
                                        </h3>
                                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                            @if ($sacrament->minister_name)
                                                <p><span class="font-medium">Pendeta:</span>
                                                    {{ $sacrament->minister_name }}</p>
                                            @endif
                                            @if ($sacrament->certificate_number)
                                                <p><span class="font-medium">Sertifikat:</span>
                                                    {{ $sacrament->certificate_number }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-8 text-center">
                        <svg class="w-12 h-12 text-gray-400 dark:text-gray-600 mx-auto mb-3" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 font-medium">Tidak ada sakramen dalam periode ini
                        </p>
                    </div>
                @endif
            </div>

            <!-- Section 3: Laporan Keuangan -->
            <div class="print:page-break-inside-avoid">
                <div class="flex items-center gap-3 mb-6 print:page-break-after-avoid">
                    <div class="w-1 h-8 bg-gradient-to-b from-green-500 to-green-600 rounded-full"></div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">💰 Laporan Keuangan</h2>
                </div>

                @if ($reportData['transactions']->count() > 0)
                    <div class="space-y-8">
                        @foreach ($reportData['transactions'] as $type => $transactionList)
                            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                                <!-- Header -->
                                <div
                                    class="bg-gradient-to-r {{ $type === 'Pemasukan' ? 'from-green-500 to-emerald-500' : 'from-red-500 to-rose-500' }} px-6 py-4">
                                    <h3 class="font-bold text-white text-lg flex items-center gap-2">
                                        <span class="inline-block w-3 h-3 bg-white rounded-full"></span>
                                        {{ $type }}
                                    </h3>
                                </div>

                                <!-- Table -->
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr
                                                class="bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 print:bg-gray-200">
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                                    Tanggal
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                                    Dana
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                                    Kategori
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                                    Keterangan
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                                    Jumlah
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach ($transactionList as $transaction)
                                                <tr
                                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150 print:hover:bg-transparent">
                                                    <td
                                                        class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                                        {{ $transaction->transaction_date->format('d M Y') }}
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                        {{ $transaction->fund->name ?? '-' }}
                                                    </td>
                                                    <td class="px-4 py-3 text-sm">
                                                        <span
                                                            class="inline-block px-2.5 py-1 rounded text-xs font-medium {{ $type === 'Pemasukan' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                                            {{ $transaction->category->name ?? '-' }}
                                                        </span>
                                                    </td>
                                                    <td
                                                        class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-xs truncate">
                                                        {{ $transaction->description ?? '-' }}
                                                    </td>
                                                    <td
                                                        class="px-4 py-3 text-sm font-bold text-right {{ $type === 'Pemasukan' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                        Rp{{ number_format($transaction->amount, 0, ',', '.') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr
                                                class="bg-gray-100 dark:bg-gray-700 font-bold border-t-2 border-gray-300 dark:border-gray-600 print:bg-gray-200">
                                                <td colspan="4"
                                                    class="px-4 py-4 text-right text-gray-900 dark:text-white">
                                                    Total {{ $type }}:
                                                </td>
                                                <td
                                                    class="px-4 py-4 text-right {{ $type === 'Pemasukan' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    Rp{{ number_format($transactionList->sum('amount'), 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-8 text-center">
                        <svg class="w-12 h-12 text-gray-400 dark:text-gray-600 mx-auto mb-3" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 font-medium">Tidak ada transaksi dalam periode ini
                        </p>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div
                class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700 print:border-gray-300 text-center text-sm text-gray-500 dark:text-gray-400">
                <p>Laporan ini dicetak dari Sistem Manajemen Jemaat</p>
                <p class="text-xs mt-2">{{ now()->format('d F Y H:i') }}</p>
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
                margin: 0;
                padding: 0;
                background: white;
            }

            x-filament-panels--page {
                background: white;
            }

            /* Hide navigation elements */
            aside,
            nav,
            .fi-navbar,
            .fi-sidebar,
            [role="banner"] {
                display: none !important;
            }

            /* Adjust main content */
            main {
                width: 100%;
                margin: 0;
                padding: 0;
            }

            /* Page breaks */
            .print\:page-break-after-avoid {
                page-break-after: avoid;
            }

            .print\:page-break-inside-avoid {
                page-break-inside: avoid;
            }

            /* Spacing adjustments for print */
            .space-y-12>div {
                margin-bottom: 1.5rem;
            }
        }
    </style>
</x-filament-panels::page>
