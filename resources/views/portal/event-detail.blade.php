@extends('portal.layout')

@section('title', $event->title . ' — Detail Kegiatan')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">
    <!-- Top Back Navigation -->
    <div class="flex items-center justify-between">
        <a href="{{ route('portal.events') }}" class="text-xs font-bold uppercase tracking-wider text-gksbs-forest hover:text-gksbs-ocean flex items-center gap-1.5 transition">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            <span>Kembali ke Jadwal Ibadah</span>
        </a>
        @if ($event->category)
            <span class="px-3 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-slate-100 text-slate-700">
                {{ $event->category->name }}
            </span>
        @endif
    </div>

    <!-- Main Detail Card -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm space-y-6 sm:space-y-8">
        <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-gksbs-forest block mb-1">
                {{ $user->church?->name ?? 'Gereja' }}
            </span>
            <h1 class="font-heading text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                {{ $event->title }}
            </h1>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-5 rounded-2xl bg-[#fbfcfd] border border-slate-200/70 text-xs">
            <div>
                <span class="text-slate-400 font-semibold uppercase tracking-wider text-[10px] block mb-1">Hari & Tanggal</span>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gksbs-forest flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <span class="font-bold text-slate-900 text-sm">
                        {{ $event->start_datetime?->locale('id')->translatedFormat('l, d F Y') }}
                    </span>
                </div>
            </div>
            <div>
                <span class="text-slate-400 font-semibold uppercase tracking-wider text-[10px] block mb-1">Waktu Pelaksanaan</span>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gksbs-forest flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span class="font-bold text-slate-900 text-sm">
                        {{ $event->start_datetime?->format('H:i') }}
                        @if ($event->end_datetime)
                            – {{ $event->end_datetime->format('H:i') }} WIB
                        @else
                            WIB
                        @endif
                    </span>
                </div>
            </div>
            @if ($event->location)
                <div class="sm:col-span-2 pt-2 border-t border-slate-100">
                    <span class="text-slate-400 font-semibold uppercase tracking-wider text-[10px] block mb-1">Lokasi Gedung / Ruangan</span>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gksbs-forest flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-5.5-7-11a7 7 0 0 1 14 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        <span class="font-semibold text-slate-900 text-xs sm:text-sm">{{ $event->location }}</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Daftar Roster / Pelayan Ibadah -->
        <div class="space-y-3 pt-2">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <h2 class="font-heading text-base font-bold text-slate-900">
                    Jadwal Pelayan Ibadah
                </h2>
                <span class="text-xs text-slate-400">Total {{ $event->rosters->count() }} pelayan</span>
            </div>

            @if ($event->rosters->isEmpty())
                <p class="text-xs text-slate-400 italic py-3">Belum ada daftar pelayan yang ditentukan untuk kegiatan ini.</p>
            @else
                <div class="border border-slate-200/80 rounded-2xl overflow-hidden divide-y divide-slate-100 text-xs">
                    @foreach ($event->rosters as $roster)
                        @php
                            $isMe = $user->member_id && (int) $roster->member_id === (int) $user->member_id;
                        @endphp
                        <div class="p-4 flex items-center justify-between gap-4 {{ $isMe ? 'bg-amber-50/70 font-semibold' : 'bg-white' }}">
                            <div class="space-y-0.5">
                                <span class="text-slate-400 text-[10px] uppercase tracking-wider font-semibold block">
                                    {{ $roster->ministryRole?->name ?? 'Pelayan' }}
                                </span>
                                <span class="font-bold text-slate-900 text-sm">
                                    {{ $roster->member?->full_name ?? $roster->official?->name ?? 'Belum ditentukan' }}
                                </span>
                                @if ($roster->notes)
                                    <p class="text-[11px] text-slate-500 italic pt-0.5 leading-relaxed">{{ $roster->notes }}</p>
                                @endif
                            </div>

                            @if ($isMe)
                                <span class="px-3 py-1 rounded-full bg-amber-500 text-white font-black text-[10px] uppercase tracking-wider shadow-xs flex-shrink-0 flex items-center gap-1">
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                    <span>Tugas Anda</span>
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
