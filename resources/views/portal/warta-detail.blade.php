@extends('portal.layout')

@section('title', $publication->title . ' — Warta Jemaat')

@section('content')
<div class="space-y-6 w-full">
    <!-- Top Bar Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <a href="{{ route('portal.warta') }}" class="text-xs font-bold uppercase tracking-wider text-gksbs-forest hover:text-gksbs-ocean flex items-center gap-1.5 transition">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            <span>Kembali ke Daftar Warta</span>
        </a>
        <span class="text-xs text-slate-400">
            Diterbitkan: {{ $publication->published_at?->locale('id')->translatedFormat('d F Y, H:i') }} WIB
        </span>
    </div>

    <!-- Bulletin Article Card -->
    <article class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        
        <!-- Kop Buletin Resmi Warta -->
        <header class="p-6 sm:p-10 bg-[#fbfcfd] border-b border-slate-200/70 text-center relative">
            <div class="inline-block px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-gksbs-forest text-white mb-3 shadow-xs">
                {{ $publication->periodLabel() }}
            </div>
            <h1 class="font-heading text-2xl sm:text-4xl font-bold text-slate-900 tracking-tight leading-tight">
                {{ $publication->title }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-2 font-medium">
                {{ $user->church?->name ?? 'GKSBS' }}
            </p>
        </header>

        <!-- Body Content -->
        <div class="p-6 sm:p-10 space-y-8 text-sm">
            @php
                $content = $publication->content ?? [];
            @endphp

            @if (empty($content))
                <div class="text-center py-12 text-slate-400 italic">
                    Konten warta belum diisi.
                </div>
            @else
                <!-- Renungan / Firman -->
                @if (!empty($content['reflection']) || !empty($content['renungan']))
                    <section class="p-5 sm:p-6 rounded-2xl bg-gksbs-forest/5 border border-gksbs-forest/15 space-y-2">
                        <div class="flex items-center gap-2 text-gksbs-forest font-heading font-bold text-sm sm:text-base">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 19.5A2.5 2.5 0 0 0 6.5 22H20V4.5A2.5 2.5 0 0 0 17.5 2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/></svg>
                            <span>Renungan & Firman Penguat</span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line font-heading italic">
                            {{ $content['reflection'] ?? $content['renungan'] }}
                        </p>
                    </section>
                @endif

                <!-- Jadwal Kebaktian & Pelayanan -->
                @if (!empty($content['events']) && is_array($content['events']))
                    <section class="space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                            <h2 class="font-heading text-base sm:text-lg font-bold text-slate-900">
                                Jadwal Kebaktian & Pelayanan
                            </h2>
                            <span class="text-xs text-slate-400">Minggu Pelayanan</span>
                        </div>

                        <div class="divide-y divide-slate-100 border border-slate-100 rounded-2xl overflow-hidden text-xs">
                            @foreach ($content['events'] as $evt)
                                <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:bg-slate-50/60 transition">
                                    <div class="space-y-0.5">
                                        <p class="font-bold text-slate-900 text-sm">{{ $evt['name'] ?? $evt['title'] ?? 'Ibadah' }}</p>
                                        <p class="text-[11px] text-slate-500 flex items-center gap-2">
                                            <span class="font-medium text-slate-700">{{ $evt['start'] ?? $evt['start_datetime'] ?? '' }}</span>
                                            <span class="text-slate-300">&bull;</span>
                                            <span>{{ $evt['location'] ?? 'Gedung Gereja' }}</span>
                                        </p>
                                    </div>
                                    @if (!empty($evt['rosters']) && is_array($evt['rosters']))
                                        <div class="text-[11px] text-slate-600 sm:text-right">
                                            @foreach ($evt['rosters'] as $r)
                                                <span class="inline-block bg-slate-100 px-2 py-0.5 rounded-md mr-1 mb-1">{{ is_array($r) ? ($r['role'] ?? '') . ': ' . ($r['name'] ?? '') : $r }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- Pelayanan & Kegiatan yang Telah Terlaksana (Minggu Lalu) -->
                @if (!empty($content['past_events']) && is_array($content['past_events']))
                    <section class="space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                            <h2 class="font-heading text-base sm:text-lg font-bold text-slate-900">
                                Pelayanan &amp; Kegiatan yang Telah Terlaksana
                            </h2>
                            <span class="text-xs text-slate-400">Minggu Lalu</span>
                        </div>

                        <div class="divide-y divide-slate-100 border border-slate-100 rounded-2xl overflow-hidden text-xs">
                            @foreach ($content['past_events'] as $past)
                                <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/60 transition">
                                    <div class="space-y-1 min-w-0">
                                        <p class="font-bold text-slate-900 text-sm">{{ $past['name'] ?? $past['title'] ?? 'Kegiatan' }}</p>
                                        <p class="text-[11px] text-slate-500 flex flex-wrap items-center gap-2">
                                            <span class="font-medium text-slate-700">{{ $past['start'] ?? '' }}</span>
                                            <span class="text-slate-300">&bull;</span>
                                            <span>{{ $past['location'] ?? 'Gedung Gereja' }}</span>
                                            @if (!empty($past['officials']))
                                                <span class="text-slate-300">&bull;</span>
                                                <span class="text-slate-600">Pelayan: {{ $past['officials'] }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="shrink-0">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-200/80 px-3 py-1 text-xs font-bold text-emerald-800">
                                            <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px;" class="h-3.5 w-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            {{ (int) ($past['total_attendance'] ?? 0) }} Jiwa
                                            @if (!empty($past['attendance_male']) || !empty($past['attendance_female']))
                                                <span class="text-[10px] text-emerald-600 font-normal">
                                                    (L: {{ $past['attendance_male'] ?? 0 }}, P: {{ $past['attendance_female'] ?? 0 }})
                                                </span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- Warta & Pengumuman Jemaat -->
                @if (!empty($content['announcements']) && is_array($content['announcements']))
                    <section class="space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                            <h2 class="font-heading text-base sm:text-lg font-bold text-slate-900">
                                Warta & Pengumuman Jemaat
                            </h2>
                        </div>
                        <div class="space-y-3">
                            @foreach ($content['announcements'] as $item)
                                <div class="p-4 rounded-2xl bg-[#fbfcfd] border border-slate-200/70 text-xs sm:text-sm space-y-1">
                                    <h3 class="font-bold text-slate-900 text-sm sm:text-base">{{ $item['title'] ?? 'Pengumuman' }}</h3>
                                    <p class="text-slate-600 leading-relaxed text-xs sm:text-sm">{{ $item['body'] ?? $item['content'] ?? '' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- Laporan Keuangan Kas Tunai per Kantong -->
                @if (!empty($content['finance']) && is_array($content['finance']))
                    @php
                        $fmt = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
                        $fin = $content['finance'];
                        $funds = $fin['funds'] ?? [];
                    @endphp
                    <section class="space-y-4 pt-2">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                            <h2 class="font-heading text-base sm:text-lg font-bold text-slate-900">
                                Laporan Keuangan Kas Tunai
                            </h2>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-0.5 text-xs font-bold text-emerald-800 border border-emerald-200">
                                100% Kas Tunai
                            </span>
                        </div>

                        <!-- Ringkasan Konsolidasi -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="p-3.5 rounded-2xl bg-[#fbfcfd] border border-slate-200 text-center">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Saldo Awal</p>
                                <p class="text-xs sm:text-sm font-bold text-slate-800 mt-1">{{ $fmt($fin['opening_balance'] ?? 0) }}</p>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 text-center">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Total Masuk</p>
                                <p class="text-xs sm:text-sm font-black text-emerald-700 mt-1">+{{ $fmt($fin['total_income'] ?? 0) }}</p>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-rose-50/70 border border-rose-200/80 text-center">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-rose-700">Total Keluar</p>
                                <p class="text-xs sm:text-sm font-black text-rose-700 mt-1">−{{ $fmt($fin['total_expenses'] ?? 0) }}</p>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200/80 text-center">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-amber-800">Saldo Akhir</p>
                                <p class="text-xs sm:text-sm font-black text-amber-900 mt-1">{{ $fmt($fin['closing_balance'] ?? 0) }}</p>
                            </div>
                        </div>

                        <!-- Rincian per Kantong Kas -->
                        @if (!empty($funds))
                            <div class="space-y-4 pt-2">
                                @foreach ($funds as $fund)
                                    <div class="p-5 rounded-2xl border border-slate-200/90 bg-white shadow-xs">
                                        <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100 text-xs">
                                            <div class="flex items-center gap-2">
                                                <div class="h-2.5 w-2.5 rounded-full bg-gksbs-forest"></div>
                                                <span class="font-heading font-bold text-slate-900 text-sm">{{ $fund['name'] ?? 'Pos Kas' }}</span>
                                            </div>
                                            <span class="text-slate-500 text-xs">
                                                Saldo Awal: <strong class="text-slate-800">{{ $fmt($fund['opening_balance'] ?? 0) }}</strong>
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mt-3 text-xs">
                                            <!-- Uang Masuk -->
                                            <div class="p-3 rounded-xl bg-emerald-50/40 border border-emerald-200/60">
                                                <div class="flex justify-between font-bold text-[11px] text-emerald-900 pb-1.5 border-b border-emerald-200/60 uppercase tracking-wider">
                                                    <span>Pos Uang Masuk</span>
                                                    <span>Jumlah</span>
                                                </div>
                                                <ul class="mt-2 space-y-1.5 text-xs">
                                                    @forelse ($fund['income']['items'] ?? [] as $item)
                                                        <li class="flex justify-between text-slate-700">
                                                            <span>{{ $item['category'] ?? '-' }}</span>
                                                            <span class="font-semibold text-emerald-700">+{{ $fmt($item['amount'] ?? 0) }}</span>
                                                        </li>
                                                    @empty
                                                        <li class="text-slate-400 italic text-center py-1">Tidak ada pemasukan</li>
                                                    @endforelse
                                                </ul>
                                                <div class="mt-2.5 pt-1.5 border-t border-emerald-200 flex justify-between font-bold text-xs text-emerald-900">
                                                    <span>Total Masuk</span>
                                                    <span>+{{ $fmt($fund['income']['total'] ?? 0) }}</span>
                                                </div>
                                            </div>

                                            <!-- Uang Keluar -->
                                            <div class="p-3 rounded-xl bg-rose-50/40 border border-rose-200/60">
                                                <div class="flex justify-between font-bold text-[11px] text-rose-900 pb-1.5 border-b border-rose-200/60 uppercase tracking-wider">
                                                    <span>Pos Uang Keluar</span>
                                                    <span>Jumlah</span>
                                                </div>
                                                <ul class="mt-2 space-y-1.5 text-xs">
                                                    @forelse ($fund['expense']['items'] ?? [] as $item)
                                                        <li class="flex justify-between text-slate-700">
                                                            <span>{{ $item['category'] ?? '-' }}</span>
                                                            <span class="font-semibold text-rose-700">−{{ $fmt($item['amount'] ?? 0) }}</span>
                                                        </li>
                                                    @empty
                                                        <li class="text-slate-400 italic text-center py-1">Tidak ada pengeluaran</li>
                                                    @endforelse
                                                </ul>
                                                <div class="mt-2.5 pt-1.5 border-t border-rose-200 flex justify-between font-bold text-xs text-rose-900">
                                                    <span>Total Keluar</span>
                                                    <span>−{{ $fmt($fund['expense']['total'] ?? 0) }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3 pt-2.5 border-t border-dashed border-slate-200 flex justify-between items-center bg-[#fbfcfd] rounded-xl px-3.5 py-2 text-xs">
                                            <span class="font-semibold text-slate-700">Saldo Akhir {{ $fund['name'] ?? '' }}</span>
                                            <span class="font-black text-gksbs-forest text-sm">{{ $fmt($fund['closing_balance'] ?? 0) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endif

                <!-- Fallback JSON viewer -->
                @if (empty($content['events']) && empty($content['past_events']) && empty($content['announcements']) && empty($content['reflection']) && empty($content['renungan']) && empty($content['finance']))
                    <div class="p-4 rounded-2xl bg-[#fbfcfd] border border-slate-200">
                        <pre class="text-xs text-slate-700 whitespace-pre-wrap font-mono leading-relaxed">{{ is_string($content) ? $content : json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @endif
            @endif
        </div>
    </article>
</div>
@endsection
