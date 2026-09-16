@extends('portal.layout')

@section('title', $publication->title . ' — Warta Jemaat')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">
    <div class="flex items-center justify-between">
        <a href="{{ route('portal.warta') }}" class="text-xs font-bold text-amber-700 hover:text-amber-800 flex items-center gap-1">
            ← Kembali ke Daftar Warta
        </a>
        <span class="text-xs text-slate-400">
            Diterbitkan: {{ $publication->published_at?->locale('id')->translatedFormat('d F Y, H:i') }} WIB
        </span>
    </div>

    <article class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Header Warta -->
        <header class="p-6 sm:p-8 bg-gradient-to-b from-amber-50 to-white border-b border-amber-100 text-center">
            <span class="inline-block px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-500 text-white mb-3 shadow-sm">
                {{ $publication->periodLabel() }}
            </span>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $publication->title }}</h1>
            <p class="text-xs text-slate-500 mt-1">{{ $user->church?->name }}</p>
        </header>

        <!-- Body / Content Blocks -->
        <div class="p-6 sm:p-8 space-y-6 text-sm">
            @php
                $content = $publication->content ?? [];
            @endphp

            @if (empty($content))
                <div class="text-center py-8 text-slate-400 italic">
                    Konten warta belum diisi.
                </div>
            @else
                <!-- Jika ada pesan/renungan -->
                @if (!empty($content['reflection']) || !empty($content['renungan']))
                    <section class="p-4 rounded-xl bg-amber-50/50 border border-amber-100">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-amber-800 mb-2">Renungan / Firman</h2>
                        <p class="text-xs text-slate-700 leading-relaxed whitespace-pre-line">
                            {{ $content['reflection'] ?? $content['renungan'] }}
                        </p>
                    </section>
                @endif

                <!-- Jika ada events snapshot -->
                @if (!empty($content['events']) && is_array($content['events']))
                    <section class="space-y-3">
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-1 border-b border-slate-100">
                            Jadwal Kebaktian & Pelayanan
                        </h2>
                        <div class="divide-y divide-slate-100 text-xs">
                            @foreach ($content['events'] as $evt)
                                <div class="py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $evt['name'] ?? $evt['title'] ?? 'Ibadah' }}</p>
                                        <p class="text-[11px] text-slate-400">
                                            {{ $evt['start'] ?? $evt['start_datetime'] ?? '' }} &bull; {{ $evt['location'] ?? 'Gedung Gereja' }}
                                        </p>
                                    </div>
                                    @if (!empty($evt['rosters']) && is_array($evt['rosters']))
                                        <div class="text-[11px] text-slate-500">
                                            @foreach ($evt['rosters'] as $r)
                                                <span class="inline-block mr-2">{{ is_array($r) ? ($r['role'] ?? '') . ': ' . ($r['name'] ?? '') : $r }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- Pengumuman Umum / Raw Content -->
                @if (!empty($content['announcements']) && is_array($content['announcements']))
                    <section class="space-y-3">
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-1 border-b border-slate-100">
                            Warta & Pengumuman Jemaat
                        </h2>
                        <div class="space-y-3">
                            @foreach ($content['announcements'] as $item)
                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                    <h3 class="font-bold text-slate-900 mb-1">{{ $item['title'] ?? 'Pengumuman' }}</h3>
                                    <p class="text-slate-600 leading-relaxed">{{ $item['body'] ?? $item['content'] ?? '' }}</p>
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
                    <section class="space-y-4">
                        <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                                Laporan Keuangan Kas Tunai
                            </h2>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                100% Kas Tunai
                            </span>
                        </div>

                        <!-- Ringkasan Konsolidasi -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-center">
                                <p class="text-[10px] font-medium text-slate-400">Saldo Awal</p>
                                <p class="text-xs font-bold text-slate-800 mt-0.5">{{ $fmt($fin['opening_balance'] ?? 0) }}</p>
                            </div>
                            <div class="p-3 rounded-xl bg-emerald-50/50 border border-emerald-100 text-center">
                                <p class="text-[10px] font-bold text-emerald-600">Total Masuk</p>
                                <p class="text-xs font-black text-emerald-700 mt-0.5">+{{ $fmt($fin['total_income'] ?? 0) }}</p>
                            </div>
                            <div class="p-3 rounded-xl bg-rose-50/50 border border-rose-100 text-center">
                                <p class="text-[10px] font-bold text-rose-600">Total Keluar</p>
                                <p class="text-xs font-black text-rose-700 mt-0.5">−{{ $fmt($fin['total_expenses'] ?? 0) }}</p>
                            </div>
                            <div class="p-3 rounded-xl bg-amber-50/50 border border-amber-100 text-center">
                                <p class="text-[10px] font-bold text-amber-600">Saldo Akhir</p>
                                <p class="text-xs font-black text-amber-700 mt-0.5">{{ $fmt($fin['closing_balance'] ?? 0) }}</p>
                            </div>
                        </div>

                        <!-- Rincian per Kantong Kas -->
                        @if (!empty($funds))
                            <div class="space-y-3">
                                @foreach ($funds as $fund)
                                    <div class="p-4 rounded-xl border border-slate-200 bg-white shadow-xs">
                                        <div class="flex items-center justify-between pb-2 border-b border-slate-100 text-xs">
                                            <div class="flex items-center gap-2">
                                                <div class="h-2 w-2 rounded-full bg-amber-500"></div>
                                                <span class="font-bold text-slate-900">{{ $fund['name'] ?? 'Pos Kas' }}</span>
                                            </div>
                                            <span class="text-slate-400">
                                                Saldo Awal: <strong class="text-slate-700">{{ $fmt($fund['opening_balance'] ?? 0) }}</strong>
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2.5 text-xs">
                                            <!-- Uang Masuk -->
                                            <div class="p-2.5 rounded-lg bg-emerald-50/40 border border-emerald-100/60">
                                                <div class="flex justify-between font-bold text-[11px] text-emerald-800 pb-1 border-b border-emerald-100">
                                                    <span>Uang Masuk</span>
                                                    <span>Jumlah</span>
                                                </div>
                                                <ul class="mt-1.5 space-y-1 text-[11px]">
                                                    @forelse ($fund['income']['items'] ?? [] as $item)
                                                        <li class="flex justify-between text-slate-600">
                                                            <span>{{ $item['category'] ?? '-' }}</span>
                                                            <span class="font-semibold text-emerald-700">+{{ $fmt($item['amount'] ?? 0) }}</span>
                                                        </li>
                                                    @empty
                                                        <li class="text-slate-400 italic text-center py-0.5">Tidak ada pemasukan</li>
                                                    @endforelse
                                                </ul>
                                                <div class="mt-2 pt-1 border-t border-emerald-200/50 flex justify-between font-bold text-[11px] text-emerald-800">
                                                    <span>Total Masuk</span>
                                                    <span>+{{ $fmt($fund['income']['total'] ?? 0) }}</span>
                                                </div>
                                            </div>

                                            <!-- Uang Keluar -->
                                            <div class="p-2.5 rounded-lg bg-rose-50/40 border border-rose-100/60">
                                                <div class="flex justify-between font-bold text-[11px] text-rose-800 pb-1 border-b border-rose-100">
                                                    <span>Uang Keluar</span>
                                                    <span>Jumlah</span>
                                                </div>
                                                <ul class="mt-1.5 space-y-1 text-[11px]">
                                                    @forelse ($fund['expense']['items'] ?? [] as $item)
                                                        <li class="flex justify-between text-slate-600">
                                                            <span>{{ $item['category'] ?? '-' }}</span>
                                                            <span class="font-semibold text-rose-700">−{{ $fmt($item['amount'] ?? 0) }}</span>
                                                        </li>
                                                    @empty
                                                        <li class="text-slate-400 italic text-center py-0.5">Tidak ada pengeluaran</li>
                                                    @endforelse
                                                </ul>
                                                <div class="mt-2 pt-1 border-t border-rose-200/50 flex justify-between font-bold text-[11px] text-rose-800">
                                                    <span>Total Keluar</span>
                                                    <span>−{{ $fmt($fund['expense']['total'] ?? 0) }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-2.5 pt-2 border-t border-dashed border-slate-200 flex justify-between items-center bg-slate-50/80 rounded-lg px-2.5 py-1.5 text-xs">
                                            <span class="font-semibold text-slate-700">Saldo Akhir {{ $fund['name'] ?? '' }}</span>
                                            <span class="font-black text-amber-700">{{ $fmt($fund['closing_balance'] ?? 0) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endif

                <!-- Fallback JSON viewer jika format kustom lain -->
                @if (empty($content['events']) && empty($content['announcements']) && empty($content['reflection']) && empty($content['renungan']))
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <pre class="text-xs text-slate-700 whitespace-pre-wrap font-sans leading-relaxed">{{ is_string($content) ? $content : json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @endif
            @endif
        </div>
    </article>
</div>
@endsection
