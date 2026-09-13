@extends('portal.layout')

@section('title', 'Warta Jemaat')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Warta Jemaat</h1>
            <p class="text-xs text-slate-500 mt-1">Edisi resmi Warta Jemaat {{ $user->church?->name }}</p>
        </div>
        <div>
            <a href="{{ route('public.warta.index') }}" target="_blank" class="text-xs font-bold text-amber-700 hover:underline">
                Buka Versi Publik ↗
            </a>
        </div>
    </div>

    @if ($publications->isEmpty())
        <div class="bg-white rounded-2xl p-12 text-center border border-slate-200 shadow-sm">
            <div class="text-4xl mb-3">📰</div>
            <h2 class="text-base font-bold text-slate-800">Belum ada Warta Jemaat yang diterbitkan</h2>
            <p class="text-xs text-slate-400 mt-1">Edisi warta jemaat baru akan otomatis muncul di sini setelah dipublikasikan oleh Majelis.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($publications as $pub)
                <a href="{{ route('portal.warta.show', $pub) }}" class="block bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:shadow-md hover:border-amber-300 transition space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200">
                            {{ $pub->periodLabel() }}
                        </span>
                        <span class="text-[11px] text-slate-400">
                            {{ $pub->published_at?->locale('id')->translatedFormat('d M Y') }}
                        </span>
                    </div>

                    <h2 class="text-base font-bold text-slate-900 group-hover:text-amber-600 transition">
                        {{ $pub->title }}
                    </h2>

                    @if (!empty($pub->content['theme']))
                        <p class="text-xs text-slate-600 italic">
                            Tema: "{{ $pub->content['theme'] }}"
                        </p>
                    @endif

                    <div class="pt-2 flex items-center justify-between text-xs text-slate-400 border-t border-slate-50">
                        <span>Diterbitkan {{ $pub->published_at?->diffForHumans() }}</span>
                        <span class="font-bold text-amber-700">Baca Selengkapnya →</span>
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
