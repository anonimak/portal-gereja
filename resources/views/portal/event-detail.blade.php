@extends('portal.layout')

@section('title', $event->title . ' — Detail Kegiatan')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">
    <div class="flex items-center justify-between">
        <a href="{{ route('portal.events') }}" class="text-xs font-bold text-amber-700 hover:text-amber-800 flex items-center gap-1">
            ← Kembali ke Jadwal Ibadah
        </a>
        @if ($event->category)
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-slate-100 text-slate-700">
                {{ $event->category->name }}
            </span>
        @endif
    </div>

    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $event->title }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $user->church?->name }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-100 text-xs">
            <div>
                <span class="text-slate-400 block mb-0.5">Hari / Tanggal:</span>
                <span class="font-bold text-slate-900 text-sm">
                    {{ $event->start_datetime?->locale('id')->translatedFormat('l, d F Y') }}
                </span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Waktu Pelaksanaan:</span>
                <span class="font-bold text-slate-900 text-sm">
                    {{ $event->start_datetime?->format('H:i') }}
                    @if ($event->end_datetime)
                        – {{ $event->end_datetime->format('H:i') }} WIB
                    @else
                        WIB
                    @endif
                </span>
            </div>
            @if ($event->location)
                <div class="sm:col-span-2">
                    <span class="text-slate-400 block mb-0.5">Lokasi / Ruangan:</span>
                    <span class="font-bold text-slate-900">{{ $event->location }}</span>
                </div>
            @endif
        </div>

        <!-- Daftar Roster / Pelayan -->
        <div class="space-y-3">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Jadwal Pelayan Ibadah</h2>

            @if ($event->rosters->isEmpty())
                <p class="text-xs text-slate-400 italic">Belum ada daftar pelayan yang ditentukan untuk kegiatan ini.</p>
            @else
                <div class="border border-slate-100 rounded-xl overflow-hidden divide-y divide-slate-100 text-xs">
                    @foreach ($event->rosters as $roster)
                        @php
                            $isMe = $user->member_id && (int) $roster->member_id === (int) $user->member_id;
                        @endphp
                        <div class="p-3.5 flex items-center justify-between {{ $isMe ? 'bg-amber-50/70 font-bold' : 'bg-white' }}">
                            <div>
                                <span class="text-slate-400 text-[11px] block">{{ $roster->ministryRole?->name ?? 'Pelayan' }}</span>
                                <span class="font-bold text-slate-900 text-sm">
                                    {{ $roster->member?->full_name ?? $roster->official?->name ?? 'Belum ditentukan' }}
                                </span>
                                @if ($roster->notes)
                                    <p class="text-[11px] text-slate-500 italic mt-0.5">{{ $roster->notes }}</p>
                                @endif
                            </div>

                            @if ($isMe)
                                <span class="px-2.5 py-1 rounded-full bg-amber-500 text-white font-black text-[10px] uppercase tracking-wider shadow-sm">
                                    Tugas Anda
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
