<x-filament-panels::page>
    {{-- ═══ TARGET CHURCH SELECTOR ═══ --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Gereja Sasaran Migrasi</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Seluruh data yang diimpor akan diisolasi mutlak ke gereja ini.</p>
            </div>

            <div class="min-w-[280px]">
                @if(auth()->user()?->role === 'super_admin')
                    <select
                        wire:model.live="targetChurchId"
                        class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                    >
                        @foreach($this->churches as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }})</option>
                        @endforeach
                    </select>
                @else
                    <div class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-3.5 py-2 text-sm font-medium text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                        <x-heroicon-o-lock-closed class="h-4 w-4" />
                        <span>{{ $this->targetChurch?->name ?? 'Gereja Anda' }}</span>
                        <span class="rounded bg-emerald-200/60 px-1.5 py-0.5 text-xs font-semibold text-emerald-900 dark:bg-emerald-800 dark:text-emerald-100">Terkunci</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══ NAVIGATION TABS ═══ --}}
    <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex space-x-4 sm:space-x-8 overflow-x-auto">
            @php
                $tabs = [
                    'jemaat' => ['label' => 'Jemaat & Keluarga', 'icon' => 'heroicon-o-users'],
                    'keuangan' => ['label' => 'Master Keuangan', 'icon' => 'heroicon-o-banknotes'],
                    'pelayan' => ['label' => 'Pelayan & Pejabat', 'icon' => 'heroicon-o-academic-cap'],
                    'acara' => ['label' => 'Jadwal & Acara', 'icon' => 'heroicon-o-calendar-days'],
                ];
            @endphp

            @foreach($tabs as $key => $tab)
                <button
                    type="button"
                    wire:click="setTab('{{ $key }}')"
                    class="group inline-flex items-center gap-2 border-b-2 py-4 px-2 text-sm font-medium transition-colors whitespace-nowrap {{ $activeTab === $key ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
                >
                    <x-dynamic-component :component="$tab['icon']" class="h-5 w-5" />
                    <span>{{ $tab['label'] }}</span>
                </button>
            @endforeach
        </nav>
    </div>

    {{-- ═══ TAB CONTENT ═══ --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left 1 col: Template Guide & Download --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400">
                    <x-heroicon-o-arrow-down-tray class="h-6 w-6" />
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Langkah 1: Unduh Template</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Gunakan format spreadsheet resmi</p>
                </div>
            </div>

            <div class="mt-4 space-y-3 text-xs text-gray-600 dark:text-gray-300">
                @if($activeTab === 'jemaat')
                    <p>• Mengimpor data Kartu Keluarga, anggota jemaat, kontak, dan riwayat sakramen.</p>
                    <p>• <strong>Deduplikasi NIK otomatis</strong>: Baris dengan NIK yang sama akan diperbarui.</p>
                    <p>• Anggota dengan nomor KK sama otomatis dikelompokkan ke dalam satu Keluarga.</p>
                    <p>• Sakramen Baptis, Sidi, dan Nikah otomatis tercatat pada tabel sakramen.</p>
                @elseif($activeTab === 'keuangan')
                    <p>• Mengimpor pos kas/dana mandiri (Sheet 1) dan kategori transaksi (Sheet 2).</p>
                    <p>• <strong>Prinsip Kas Tunai</strong>: Bebas nomor rekening bank/giro (100% tunai per kantong).</p>
                    <p>• Normalisasi otomatis: Pemasukan (Debit) & Pengeluaran (Kredit).</p>
                @elseif($activeTab === 'pelayan')
                    <p>• Mengimpor jabatan pelayanan (Sheet 1) dan pejabat gereja (Sheet 2).</p>
                    <p>• Resolusi otomatis NIK warga jemaat untuk pejabat <strong>majelis_lokal</strong>.</p>
                    <p>• Mendukung data asal gereja untuk pejabat <strong>pelayan_tamu</strong>.</p>
                @elseif($activeTab === 'acara')
                    <p>• Mengimpor kategori acara (Sheet 1) dan jadwal rutin berulang (Sheet 2).</p>
                    <p>• Kategori acara baru akan otomatis dibuat jika belum terdaftar.</p>
                    <p>• Mendukung frekuensi mingguan, bulanan, dan harian beserta jam pelaksanaan.</p>
                @endif
            </div>

            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800">
                <a
                    href="{{ route('data-migration.template', ['module' => $activeTab]) }}"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                >
                    <x-heroicon-o-document-arrow-down class="h-5 w-5" />
                    <span>Unduh Template .XLSX</span>
                </a>
                <p class="mt-2 text-center text-[11px] text-gray-400">Format Microsoft Excel multi-sheet dengan petunjuk kamus nilai</p>
            </div>
        </div>

        {{-- Right 2 cols: Upload & Execution Form --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 lg:col-span-2">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-100 text-primary-700 dark:bg-primary-950 dark:text-primary-400">
                    <x-heroicon-o-arrow-up-tray class="h-6 w-6" />
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Langkah 2: Unggah & Proses Data</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Pilih file spreadsheet Excel yang sudah diisi</p>
                </div>
            </div>

            <form wire:submit.prevent="processImport" class="mt-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Pilih File Spreadsheet (.xlsx / .xls)
                    </label>
                    
                    <div class="flex justify-center rounded-xl border-2 border-dashed border-gray-300 px-6 pt-5 pb-6 dark:border-gray-700 hover:border-primary-500 transition-colors">
                        <div class="space-y-2 text-center">
                            <x-heroicon-o-arrow-up-tray class="mx-auto h-10 w-10 text-gray-400" />
                            <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                                <label class="relative cursor-pointer rounded-md font-semibold text-primary-600 hover:text-primary-500 focus-within:outline-none">
                                    <span>Pilih berkas dari perangkat</span>
                                    <input wire:model="file" type="file" accept=".xlsx,.xls,.csv" class="sr-only">
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">XLSX, XLS, atau CSV hingga 10MB</p>

                            @if($file)
                                <div class="mt-3 inline-flex items-center gap-2 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700 dark:bg-primary-950/50 dark:text-primary-300">
                                    <x-heroicon-o-document-check class="h-4 w-4" />
                                    <span>{{ $file->getClientOriginalName() }} ({{ number_format($file->getSize() / 1024, 1) }} KB)</span>
                                </div>
                            @endif

                            <div wire:loading wire:target="file" class="text-xs text-primary-600 font-medium">
                                Mengunggah berkas sementara...
                            </div>
                        </div>
                    </div>

                    @error('file')
                        <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        @if(!$file) disabled @endif
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="processImport">
                            Mulai Proses Impor {{ $tabs[$activeTab]['label'] }}
                        </span>
                        <span wire:loading wire:target="processImport" class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Memproses Data...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ REPORT & FEEDBACK SECTION ═══ --}}
    @if($importSummary)
        <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Laporan Hasil Impor Spreadsheet</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Ringkasan eksekusi batch data untuk modul <strong>{{ $tabs[$importSummary['module']]['label'] ?? $importSummary['module'] }}</strong></p>
                </div>
                <div>
                    @if($importSummary['success'])
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                            <x-heroicon-o-check-circle class="h-4 w-4" />
                            100% Berhasil Tanpa Galat
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                            <x-heroicon-o-exclamation-triangle class="h-4 w-4" />
                            Selesai dengan Catatan Galat
                        </span>
                    @endif
                </div>
            </div>

            {{-- Stat Cards --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800/50">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Baris Dibaca</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $importSummary['total_read'] }}</p>
                </div>
                <div class="rounded-lg bg-emerald-50 p-4 dark:bg-emerald-950/30">
                    <p class="text-xs text-emerald-700 dark:text-emerald-400">Data Baru Dibuat</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ $importSummary['total_created'] }}</p>
                </div>
                <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-950/30">
                    <p class="text-xs text-blue-700 dark:text-blue-400">Data Diperbarui (Upsert)</p>
                    <p class="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $importSummary['total_updated'] }}</p>
                </div>
                <div class="rounded-lg {{ $importSummary['total_skipped'] > 0 ? 'bg-red-50 dark:bg-red-950/30 text-red-700 dark:text-red-400' : 'bg-gray-50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300' }} p-4">
                    <p class="text-xs">Baris Dilewati / Galat</p>
                    <p class="mt-1 text-2xl font-bold">{{ $importSummary['total_skipped'] }}</p>
                </div>
            </div>

            {{-- Error Details Table --}}
            @if(!empty($importSummary['errors']))
                <div class="space-y-3 pt-2">
                    <h4 class="text-sm font-semibold text-red-700 dark:text-red-400 flex items-center gap-2">
                        <x-heroicon-o-x-circle class="h-5 w-5" />
                        Rincian Baris yang Memerlukan Perbaikan ({{ count($importSummary['errors']) }} baris)
                    </h4>
                    
                    <div class="overflow-x-auto rounded-lg border border-red-200 dark:border-red-900/50">
                        <table class="min-w-full divide-y divide-red-200 text-left text-xs dark:divide-red-900/50">
                            <thead class="bg-red-50 text-red-900 dark:bg-red-950/40 dark:text-red-200">
                                <tr>
                                    <th scope="col" class="px-3.5 py-2.5 font-semibold">No. Baris</th>
                                    <th scope="col" class="px-3.5 py-2.5 font-semibold">Kolom Bermasalah</th>
                                    <th scope="col" class="px-3.5 py-2.5 font-semibold">Nilai yang Terbaca</th>
                                    <th scope="col" class="px-3.5 py-2.5 font-semibold">Keterangan Galat & Solusi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-red-100 bg-white dark:divide-red-900/30 dark:bg-gray-900 text-gray-700 dark:text-gray-300">
                                @foreach($importSummary['errors'] as $err)
                                    <tr class="hover:bg-red-50/50 dark:hover:bg-red-950/20">
                                        <td class="px-3.5 py-2 font-mono font-bold text-red-600 dark:text-red-400">
                                            Baris {{ $err['row'] }}
                                        </td>
                                        <td class="px-3.5 py-2 font-mono font-semibold">
                                            {{ $err['column'] }}
                                        </td>
                                        <td class="px-3.5 py-2 text-gray-500 dark:text-gray-400 max-w-[180px] truncate">
                                            {{ $err['value'] !== '' ? $err['value'] : '(kosong)' }}
                                        </td>
                                        <td class="px-3.5 py-2 text-red-700 dark:text-red-300">
                                            {{ $err['message'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
