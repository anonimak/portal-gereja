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
