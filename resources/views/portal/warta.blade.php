@extends('portal.layout')

@section('title', 'Warta Jemaat')

@section('content')
<div class="space-y-6 sm:space-y-8 w-full">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200/80">
        <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-gksbs-forest block">
                Buletin Resmi Mingguan
            </span>
            <h1 class="font-heading text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                Warta Jemaat
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Edisi resmi buletin dan warta pelayanan {{ $user->church?->name ?? 'Gereja' }}
            </p>
        </div>
        <div>
            <a href="{{ route('public.warta.index') }}" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-slate-50 hover:bg-gksbs-forest hover:text-white text-slate-700 font-bold text-xs uppercase tracking-wider transition border border-slate-200">
                <span>Versi Publik</span>
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/></svg>
            </a>
        </div>
    </div>

    @if ($publications->isEmpty())
        <div class="bg-white rounded-3xl p-12 text-center border border-slate-200/80 shadow-xs">
            <div class="h-14 w-14 rounded-2xl bg-gksbs-forest/10 border border-gksbs-forest/20 text-gksbs-forest flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 19.5A2.5 2.5 0 0 0 6.5 22H20V4.5A2.5 2.5 0 0 0 17.5 2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/></svg>
            </div>
            <h2 class="font-heading text-lg font-bold text-slate-800">Belum Ada Warta Jemaat yang Diterbitkan</h2>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                Edisi warta jemaat baru akan otomatis muncul di sini setelah dipublikasikan oleh Majelis Jemaat.
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach ($publications as $pub)
                <a href="{{ route('portal.warta.show', $pub) }}" class="group block bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs hover:shadow-md hover:border-gksbs-forest/40 transition space-y-3.5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gksbs-forest bg-gksbs-forest/10 px-3 py-1 rounded-full border border-gksbs-forest/20">
                            {{ $pub->periodLabel() }}
                        </span>
                        <span class="text-[11px] text-slate-400 font-medium">
                            {{ $pub->published_at?->locale('id')->translatedFormat('d M Y') }}
                        </span>
                    </div>

                    <h2 class="font-heading text-lg sm:text-xl font-bold text-slate-900 group-hover:text-gksbs-forest transition leading-snug">
                        {{ $pub->title }}
                    </h2>

                    @if (!empty($pub->content['theme']))
                        <p class="text-xs text-slate-600 italic font-heading line-clamp-2">
                            Tema: &ldquo;{{ $pub->content['theme'] }}&rdquo;
                        </p>
                    @endif

                    <div class="pt-3 flex items-center justify-between text-xs text-slate-400 border-t border-slate-100">
                        <span class="text-[11px]">Diterbitkan {{ $pub->published_at?->diffForHumans() }}</span>
                        <span class="font-bold text-gksbs-forest group-hover:translate-x-0.5 transition inline-flex items-center gap-1">
                            <span>Baca Selengkapnya</span>
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="pt-4">
            {{ $publications->links() }}
        </div>
    @endif
</div>
@endsection
