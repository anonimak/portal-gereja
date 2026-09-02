<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $content['church_name'] ?? $church->name }} — Warta Jemaat</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    <main class="mx-auto max-w-3xl px-4 py-8">
        <div class="mb-6 text-center">
            <a href="{{ route('public.warta.index', ['church' => $church->code]) }}"
               class="text-sm font-semibold text-amber-700 hover:underline">← Semua Warta {{ $church->name }}</a>
        </div>

        <article class="overflow-hidden rounded-2xl bg-white shadow ring-1 ring-gray-200">
            {{-- Kop --}}
            <header class="border-b border-amber-500/20 bg-gradient-to-b from-amber-50 to-white px-8 py-10 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-3xl shadow">
                    <span>⛪</span>
                </div>
                <h1 class="text-2xl font-extrabold uppercase tracking-tight text-gray-900">
                    {{ $content['church_name'] ?? $church->name }}
                </h1>
                @if ($content['church_address'] ?? null)
                    <p class="mt-1 text-sm text-gray-500">{{ $content['church_address'] }}</p>
                @endif
                <h2 class="mt-5 text-3xl font-black text-amber-700">Warta Jemaat</h2>
                @if ($publication->title)
                    <h3 class="mt-2 text-xl font-bold text-gray-800">{{ $publication->title }}</h3>
                @endif
                @if ($content['edition_label'] ?? null)
                    <p class="mt-1 text-xs font-semibold uppercase tracking-widest text-gray-400">{{ $content['edition_label'] }}</p>
                @endif
                @if ($content['period_label'] ?? null)
                    <p class="mt-1 text-sm font-semibold text-amber-700">{{ $content['period_label'] }}</p>
                @endif
            </header>

            <div class="space-y-8 px-8 py-8">
                {{-- Jadwal Ibadah & Pelayanan --}}
                @if (! empty($content['events']))
                    <section>
                        <h3 class="mb-3 border-b-2 border-amber-500/40 pb-1 text-lg font-bold text-gray-900">Jadwal Ibadah &amp; Pelayanan</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 text-left text-gray-500">
                                        <th class="py-2 pr-4">Waktu</th>
                                        <th class="py-2 pr-4">Acara</th>
                                        <th class="py-2 pr-4">Lokasi</th>
                                        <th class="py-2">Petugas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($content['events'] as $event)
                                        <tr class="border-b border-gray-100">
                                            <td class="py-2 pr-4 whitespace-nowrap">{{ $event['start'] }}</td>
                                            <td class="py-2 pr-4 font-semibold">{{ $event['name'] }}</td>
                                            <td class="py-2 pr-4">{{ $event['location'] }}</td>
                                            <td class="py-2">{{ $event['officials'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                {{-- Ulang Tahun --}}
                @if (! empty($content['birthdays']))
                    <section>
                        <h3 class="mb-3 border-b-2 border-amber-500/40 pb-1 text-lg font-bold text-gray-900">Ulang Tahun Jemaat</h3>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($content['birthdays'] as $b)
                                <div class="flex items-center justify-between rounded-lg bg-amber-50 px-4 py-2">
                                    <span class="font-medium">{{ $b['name'] }}</span>
                                    <span class="text-sm text-gray-500">{{ $b['date'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Sakramen --}}
                @if (! empty($content['sacraments']))
                    <section>
                        <h3 class="mb-3 border-b-2 border-amber-500/40 pb-1 text-lg font-bold text-gray-900">Perayaan Sakramen</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 text-left text-gray-500">
                                        <th class="py-2 pr-4">Tanggal</th>
                                        <th class="py-2 pr-4">Jenis</th>
                                        <th class="py-2 pr-4">Nama</th>
                                        <th class="py-2">Pelayan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($content['sacraments'] as $s)
                                        <tr class="border-b border-gray-100">
                                            <td class="py-2 pr-4 whitespace-nowrap">{{ $s['date'] }}</td>
                                            <td class="py-2 pr-4">{{ $s['type'] }}</td>
                                            <td class="py-2 pr-4 font-semibold">{{ $s['name'] }}</td>
                                            <td class="py-2">{{ $s['official'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                {{-- Keuangan Ringkas --}}
                @if (! empty($content['finance']))
                    @php
                        $fmt = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
                        $finance = $content['finance'];
                    @endphp
                    <section>
                        <h3 class="mb-3 border-b-2 border-amber-500/40 pb-1 text-lg font-bold text-gray-900">Laporan Keuangan Ringkas</h3>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-xl bg-gray-50 p-4 text-center">
                                <p class="text-xs text-gray-500">Saldo Awal</p>
                                <p class="mt-1 font-bold">{{ $fmt($finance['opening_balance'] ?? 0) }}</p>
                            </div>
                            <div class="rounded-xl bg-emerald-50 p-4 text-center">
                                <p class="text-xs text-emerald-600">Pemasukan</p>
                                <p class="mt-1 font-bold text-emerald-700">{{ $fmt($finance['total_income'] ?? 0) }}</p>
                            </div>
                            <div class="rounded-xl bg-red-50 p-4 text-center">
                                <p class="text-xs text-red-500">Pengeluaran</p>
                                <p class="mt-1 font-bold text-red-600">{{ $fmt($finance['total_expenses'] ?? 0) }}</p>
                            </div>
                            <div class="rounded-xl bg-amber-50 p-4 text-center">
                                <p class="text-xs text-amber-600">Saldo Akhir</p>
                                <p class="mt-1 font-bold text-amber-700">{{ $fmt($finance['closing_balance'] ?? 0) }}</p>
                            </div>
                        </div>
                    </section>
                @endif
            </div>

            <footer class="border-t border-gray-200 px-8 py-5 text-center text-xs text-gray-400">
                Diterbitkan {{ $publication->published_at?->locale('id')->translatedFormat('d F Y, H:i') }} • Portal Gereja
            </footer>
        </article>
    </main>
</body>
</html>
