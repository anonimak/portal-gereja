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
                @php
                    $churchInfo = $content['church'] ?? [];
                    $logoUrl = $churchInfo['logo_url'] ?? $church->logo_url ?? null;
                    $synodName = $churchInfo['synod'] ?? $church->synod ?? null;
                @endphp

                @if ($logoUrl)
                    <div class="mx-auto mb-4 flex items-center justify-center">
                        <img src="{{ $logoUrl }}" alt="Logo Gereja" class="h-16 w-auto max-h-16 object-contain">
                    </div>
                @else
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-3xl shadow">
                        <span>⛪</span>
                    </div>
                @endif

                @if ($synodName)
                    <div class="text-xs font-semibold uppercase tracking-wider text-amber-800 mb-1">
                        {{ $synodName }}
                    </div>
                @endif
                <h1 class="text-2xl font-extrabold uppercase tracking-tight text-gray-900">
                    {{ $content['church_name'] ?? $church->name }}
                </h1>
                @if ($content['church_address'] ?? null)
                    <p class="mt-1 text-sm text-gray-500">{{ $content['church_address'] }}</p>
                @endif
                @if (!empty($churchInfo['phone']) || !empty($churchInfo['email']))
                    <p class="mt-1 text-xs text-gray-400">
                        @if (!empty($churchInfo['phone'])) Telp: {{ $churchInfo['phone'] }} @endif
                        @if (!empty($churchInfo['phone']) && !empty($churchInfo['email'])) &bull; @endif
                        @if (!empty($churchInfo['email'])) Email: {{ $churchInfo['email'] }} @endif
                    </p>
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

                {{-- Keuangan Kas Tunai per Kantong --}}
                @if (! empty($content['finance']))
                    @php
                        $fmt = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
                        $finance = $content['finance'];
                        $funds = $finance['funds'] ?? [];
                    @endphp
                    <section>
                        <div class="mb-4 flex items-center justify-between border-b-2 border-amber-500/40 pb-1">
                            <h3 class="text-lg font-bold text-gray-900">Laporan Keuangan Kas Tunai</h3>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                100% Kas Tunai
                            </span>
                        </div>

                        {{-- Ringkasan Konsolidasi --}}
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 mb-6">
                            <div class="rounded-xl bg-gray-50 p-3.5 text-center border border-gray-200">
                                <p class="text-xs text-gray-500">Saldo Awal</p>
                                <p class="mt-1 font-bold text-gray-900">{{ $fmt($finance['opening_balance'] ?? 0) }}</p>
                            </div>
                            <div class="rounded-xl bg-emerald-50 p-3.5 text-center border border-emerald-100">
                                <p class="text-xs text-emerald-700 font-semibold">Total Masuk</p>
                                <p class="mt-1 font-black text-emerald-700">+{{ $fmt($finance['total_income'] ?? 0) }}</p>
                            </div>
                            <div class="rounded-xl bg-red-50 p-3.5 text-center border border-red-100">
                                <p class="text-xs text-red-700 font-semibold">Total Keluar</p>
                                <p class="mt-1 font-black text-red-700">−{{ $fmt($finance['total_expenses'] ?? 0) }}</p>
                            </div>
                            <div class="rounded-xl bg-amber-50 p-3.5 text-center border border-amber-100">
                                <p class="text-xs text-amber-700 font-semibold">Saldo Akhir</p>
                                <p class="mt-1 font-black text-amber-700">{{ $fmt($finance['closing_balance'] ?? 0) }}</p>
                            </div>
                        </div>

                        {{-- Rincian per Kantong Kas --}}
                        @if (! empty($funds))
                            <div class="space-y-4">
                                @foreach ($funds as $fund)
                                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-2.5">
                                            <div class="flex items-center gap-2">
                                                <div class="h-2.5 w-2.5 rounded-full bg-amber-500"></div>
                                                <h4 class="font-bold text-gray-900 text-sm">{{ $fund['name'] ?? 'Pos Kas' }}</h4>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Saldo Awal: <span class="font-bold text-gray-800">{{ $fmt($fund['opening_balance'] ?? 0) }}</span>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                                            {{-- Uang Masuk --}}
                                            <div class="rounded-lg bg-emerald-50/40 p-2.5 border border-emerald-100">
                                                <div class="flex items-center justify-between font-semibold text-[11px] text-emerald-800 mb-1.5 border-b border-emerald-200/50 pb-1">
                                                    <span>Pos Uang Masuk</span>
                                                    <span>Jumlah</span>
                                                </div>
                                                <ul class="space-y-1 text-xs">
                                                    @forelse ($fund['income']['items'] ?? [] as $item)
                                                        <li class="flex justify-between text-gray-700">
                                                            <span>{{ $item['category'] ?? '-' }}</span>
                                                            <span class="font-semibold text-emerald-700">+{{ $fmt($item['amount'] ?? 0) }}</span>
                                                        </li>
                                                    @empty
                                                        <li class="text-gray-400 italic text-center py-1">Tidak ada uang masuk</li>
                                                    @endforelse
                                                </ul>
                                                <div class="mt-2 pt-1.5 border-t border-emerald-200/60 flex justify-between font-bold text-xs text-emerald-800">
                                                    <span>Total Masuk</span>
                                                    <span>+{{ $fmt($fund['income']['total'] ?? 0) }}</span>
                                                </div>
                                            </div>

                                            {{-- Uang Keluar --}}
                                            <div class="rounded-lg bg-red-50/40 p-2.5 border border-red-100">
                                                <div class="flex items-center justify-between font-semibold text-[11px] text-red-800 mb-1.5 border-b border-red-200/50 pb-1">
                                                    <span>Pos Uang Keluar</span>
                                                    <span>Jumlah</span>
                                                </div>
                                                <ul class="space-y-1 text-xs">
                                                    @forelse ($fund['expense']['items'] ?? [] as $item)
                                                        <li class="flex justify-between text-gray-700">
                                                            <span>{{ $item['category'] ?? '-' }}</span>
                                                            <span class="font-semibold text-red-700">−{{ $fmt($item['amount'] ?? 0) }}</span>
                                                        </li>
                                                    @empty
                                                        <li class="text-gray-400 italic text-center py-1">Tidak ada uang keluar</li>
                                                    @endforelse
                                                </ul>
                                                <div class="mt-2 pt-1.5 border-t border-red-200/60 flex justify-between font-bold text-xs text-red-800">
                                                    <span>Total Keluar</span>
                                                    <span>−{{ $fmt($fund['expense']['total'] ?? 0) }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Saldo Akhir Kantong --}}
                                        <div class="mt-3 pt-2 border-t border-dashed border-gray-200 flex items-center justify-between bg-gray-50 rounded-lg px-3 py-1.5 text-xs">
                                            <span class="font-semibold text-gray-700">Saldo Akhir {{ $fund['name'] ?? '' }}</span>
                                            <span class="font-black text-amber-700">{{ $fmt($fund['closing_balance'] ?? 0) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
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
