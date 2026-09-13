@extends('portal.layout')

@section('title', 'Jadwal Ibadah & Kegiatan')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Jadwal Ibadah & Kegiatan</h1>
            <p class="text-xs text-slate-500 mt-1">Jadwal ibadah dan kegiatan mendatang di {{ $user->church?->name }}</p>
        </div>
        <div class="text-xs text-slate-400">
            Zona Waktu: WIB (GMT+7)
        </div>
    </div>

    @if ($events->isEmpty())
        <div class="bg-white rounded-2xl p-12 text-center border border-slate-200 shadow-sm">
            <div class="text-4xl mb-3">📅</div>
            <h2 class="text-base font-bold text-slate-800">Belum ada jadwal kegiatan mendatang</h2>
            <p class="text-xs text-slate-400 mt-1">Jadwal ibadah dan kegiatan baru akan ditampilkan di sini.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($events as $event)
                @php
                    $isMyDuty = false;
                    if ($user->member_id) {
                        foreach ($event->rosters as $roster) {
                            if ((int) $roster->member_id === (int) $user->member_id) {
                                $isMyDuty = true;
                                break;
                            }
                        }
                    }
                @endphp
                <div class="bg-white rounded-2xl p-6 border {{ $isMyDuty ? 'border-amber-400 ring-2 ring-amber-100 shadow-md' : 'border-slate-200 shadow-sm' }} transition hover:shadow-md">
                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                        <div class="space-y-2 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($event->category)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700">
                                        {{ $event->category->name }}
                                    </span>
                                @endif
                                @if ($isMyDuty)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-500 text-white shadow-sm flex items-center gap-1">
                                        <span>⭐</span> Tugas Pelayanan Anda
                                    </span>
                                @endif
                            </div>

                            <h2 class="text-lg font-black text-slate-900">
                                <a href="{{ route('portal.events.show', $event) }}" class="hover:text-amber-600 transition">
                                    {{ $event->title }}
                                </a>
                            </h2>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-600 pt-1">
                                <div class="flex items-center space-x-2">
                                    <span>🗓️</span>
                                    <span class="font-medium text-slate-800">
                                        {{ $event->start_datetime?->locale('id')->translatedFormat('l, d F Y') }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span>⏰</span>
                                    <span>
                                        {{ $event->start_datetime?->format('H:i') }}
                                        @if ($event->end_datetime)
                                            – {{ $event->end_datetime->format('H:i') }} WIB
                                        @else
                                            WIB
                                        @endif
                                    </span>
                                </div>
                                @if ($event->location)
                                    <div class="flex items-center space-x-2 sm:col-span-2">
                                        <span>📍</span>
                                        <span>{{ $event->location }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="md:text-right shrink-0">
                            <a href="{{ route('portal.events.show', $event) }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-50 hover:bg-amber-50 text-slate-700 hover:text-amber-700 border border-slate-200 hover:border-amber-200 text-xs font-bold transition">
                                Rincian & Pelayan →
                            </a>
                        </div>
                    </div>

                    @if ($event->rosters->isNotEmpty())
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Pelayan Ibadah:</h3>
                            <div class="flex flex-wrap gap-2 text-xs">
                                @foreach ($event->rosters as $roster)
                                    @php
                                        $isMe = $user->member_id && (int) $roster->member_id === (int) $user->member_id;
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $isMe ? 'bg-amber-100 text-amber-900 font-bold ring-1 ring-amber-300' : 'bg-slate-50 text-slate-700 border border-slate-100' }}">
                                        <span class="text-slate-400 font-normal mr-1.5">{{ $roster->ministryRole?->name ?? 'Pelayan' }}:</span>
                                        <span>{{ $roster->member?->full_name ?? $roster->official?->name ?? 'Belum ditentukan' }}</span>
                                        @if ($isMe)
                                            <span class="ml-1 text-[10px] text-amber-700 font-black">(Anda)</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="pt-4">
                {{ $events->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
