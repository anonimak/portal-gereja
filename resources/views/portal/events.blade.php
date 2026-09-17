@extends('portal.layout')

@section('title', 'Jadwal Ibadah & Pelayanan')

@section('content')
<div class="space-y-6 sm:space-y-8 w-full">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200/80">
        <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-gksbs-forest block">
                Penjadwalan Kebaktian & Acara
            </span>
            <h1 class="font-heading text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                Jadwal Ibadah & Kegiatan
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Jadwal ibadah raya, persekutuan doa, dan kegiatan pelayanan di {{ $user->church?->name ?? 'Gereja' }}
            </p>
        </div>
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold self-start sm:self-auto">
            <svg class="w-3.5 h-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>WIB (GMT+7)</span>
        </div>
    </div>

    @if ($events->isEmpty())
        <div class="bg-white rounded-3xl p-12 text-center border border-slate-200/80 shadow-xs">
            <div class="h-14 w-14 rounded-2xl bg-gksbs-forest/10 border border-gksbs-forest/20 text-gksbs-forest flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </div>
            <h2 class="font-heading text-lg font-bold text-slate-800">Belum Ada Jadwal Ibadah Mendatang</h2>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                Jadwal ibadah dan kegiatan baru akan otomatis ditampilkan setelah ditetapkan oleh Majelis Jemaat.
            </p>
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
                    $startDate = $event->start_datetime;
                @endphp
                <div class="bg-white rounded-3xl p-5 sm:p-7 border {{ $isMyDuty ? 'border-amber-400/90 ring-2 ring-amber-100/80 shadow-sm' : 'border-slate-200/80 shadow-xs' }} transition hover:shadow-md">
                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-5">
                        
                        <!-- Date Block + Info -->
                        <div class="flex items-start gap-4 sm:gap-5 flex-1">
                            <!-- Liturgical Calendar Block -->
                            <div class="flex-shrink-0 w-16 sm:w-20 rounded-2xl overflow-hidden border border-slate-200 text-center shadow-xs bg-[#fbfcfd]">
                                <div class="bg-gksbs-forest-deep text-white py-1 text-[10px] sm:text-[11px] font-bold uppercase tracking-widest">
                                    {{ $startDate ? $startDate->locale('id')->translatedFormat('M') : 'WIB' }}
                                </div>
                                <div class="py-2 sm:py-2.5">
                                    <span class="font-heading text-2xl sm:text-3xl font-bold text-slate-900 block leading-none">
                                        {{ $startDate ? $startDate->format('d') : '-' }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider block mt-1">
                                        {{ $startDate ? $startDate->locale('id')->translatedFormat('D') : '' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Content Block -->
                            <div class="space-y-2 flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($event->category)
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700">
                                            {{ $event->category->name }}
                                        </span>
                                    @endif
                                    @if ($isMyDuty)
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-500 text-white shadow-xs flex items-center gap-1">
                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                            <span>Tugas Pelayanan Anda</span>
                                        </span>
                                    @endif
                                </div>

                                <h2 class="font-heading text-lg sm:text-xl font-bold text-slate-900 leading-snug">
                                    <a href="{{ route('portal.events.show', $event) }}" class="hover:text-gksbs-forest transition">
                                        {{ $event->title }}
                                    </a>
                                </h2>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-600 pt-1">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-slate-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        <span class="font-medium text-slate-800">
                                            {{ $startDate?->format('H:i') }}
                                            @if ($event->end_datetime)
                                                – {{ $event->end_datetime->format('H:i') }} WIB
                                            @else
                                                WIB
                                            @endif
                                        </span>
                                    </div>
                                    @if ($event->location)
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4 text-slate-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-5.5-7-11a7 7 0 0 1 14 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                            <span class="truncate">{{ $event->location }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Right Action Button -->
                        <div class="md:text-right shrink-0 pt-2 md:pt-0">
                            <a href="{{ route('portal.events.show', $event) }}" class="inline-flex items-center justify-center px-4 py-2 rounded-full bg-slate-50 hover:bg-gksbs-forest hover:text-white text-slate-700 border border-slate-200 text-xs font-bold uppercase tracking-wider transition">
                                Rincian & Pelayan →
                            </a>
                        </div>
                    </div>

                    @if ($event->rosters->isNotEmpty())
                        <div class="mt-4 pt-3.5 border-t border-slate-100">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2">Pelayan Ibadah Bertugas:</span>
                            <div class="flex flex-wrap gap-2 text-xs">
                                @foreach ($event->rosters as $roster)
                                    @php
                                        $isMe = $user->member_id && (int) $roster->member_id === (int) $user->member_id;
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full {{ $isMe ? 'bg-amber-100 text-amber-950 font-bold border border-amber-300' : 'bg-slate-50 text-slate-700 border border-slate-200/80' }}">
                                        <span class="text-slate-400 font-normal mr-1.5">{{ $roster->ministryRole?->name ?? 'Pelayan' }}:</span>
                                        <span>{{ $roster->member?->full_name ?? $roster->official?->name ?? 'Belum ditentukan' }}</span>
                                        @if ($isMe)
                                            <span class="ml-1 text-[10px] text-amber-800 font-black">(Anda)</span>
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
