<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $church->name }} — Warta Jemaat</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    <main class="mx-auto max-w-3xl px-4 py-10">
        <header class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-3xl shadow">
                <span>⛪</span>
            </div>
            <h1 class="text-2xl font-extrabold uppercase tracking-tight text-gray-900">{{ $church->name }}</h1>
            @if ($church->address)
                <p class="mt-1 text-sm text-gray-500">{{ $church->address }}</p>
            @endif
            <h2 class="mt-4 text-3xl font-black text-amber-700">Warta Jemaat</h2>

            {{-- Pemilih gereja (kalau ada > 1 gereja dengan warta) --}}
            @if ($churches->count() > 1)
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2 text-sm">
                    <span class="text-gray-500">Gereja:</span>
                    @foreach ($churches as $c)
                        <a href="{{ route('public.warta.index', ['church' => $c->code]) }}"
                           class="rounded-lg px-3 py-1 font-semibold {{ $c->id === $church->id ? 'bg-amber-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            {{ $c->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </header>

        @if ($publications->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">
                <p class="text-lg font-semibold">Belum ada Warta yang dipublikasikan.</p>
                <p class="mt-1 text-sm">Silakan kunjungi kembali nanti.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($publications as $pub)
                    <a href="{{ route('public.warta.show', ['church' => $church->code, 'publication' => $pub->id]) }}"
                       class="block rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:shadow-md">
                        <h3 class="text-lg font-bold text-gray-900">{{ $pub->title }}</h3>
                        @if ($pub->content['period_label'] ?? null)
                            <p class="mt-1 text-sm text-amber-700 font-semibold">{{ $pub->content['period_label'] }}</p>
                        @endif
                        <p class="mt-2 text-xs text-gray-400">Diterbitkan {{ $pub->published_at?->locale('id')->translatedFormat('d F Y, H:i') }}</p>
                    </a>
                @endforeach
            </div>
        @endif

        <footer class="mt-12 text-center text-xs text-gray-400">
            Diterbitkan melalui Portal Gereja
        </footer>
    </main>
</body>
</html>
