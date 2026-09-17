<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $publication->title }} — {{ $content['church_name'] ?? $church->name }}</title>
    <meta name="description" content="Edisi resmi Warta Jemaat {{ $content['church_name'] ?? $church->name }} — {{ $publication->title }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Fonts: Playfair Display & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN with GKSBS Theme -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        heading: ['"Playfair Display"', 'Georgia', 'serif'],
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        gksbs: {
                            forest: '#0f3d2e',
                            'forest-deep': '#09251c',
                            'forest-dark': '#061a13',
                            leaf: '#16a34a',
                            'leaf-light': '#22c55e',
                            ocean: '#0369a1',
                            'ocean-deep': '#0c4a6e',
                            sky: '#0284c7',
                            'sky-light': '#38bdf8',
                            teal: '#0d5d54',
                            sand: '#fbfcfd',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #fbfcfd;
            color: #1e293b;
            -webkit-font-smoothing: antialiased;
        }
        .font-heading {
            font-family: 'Playfair Display', Georgia, serif;
        }
    </style>
</head>
<body class="bg-[#fbfcfd] text-slate-800 antialiased min-h-screen flex flex-col justify-between selection:bg-gksbs-ocean selection:text-white">

    <!-- Top Official Navigation Bar -->
    <header class="bg-gksbs-forest-deep text-white border-b border-white/10 sticky top-0 z-40 shadow-sm print:hidden">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between">
            <a href="{{ route('public.warta.index', ['church' => $church->code]) }}" class="flex items-center gap-2 text-white/90 hover:text-white transition text-xs font-semibold uppercase tracking-wider">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                <span>Semua Warta {{ $church->name }}</span>
            </a>

            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}" class="text-xs uppercase tracking-widest text-white/70 hover:text-white transition font-medium hidden sm:inline-block">
                    Beranda
                </a>
                <a href="{{ route('public.offering.index') }}" class="text-xs uppercase tracking-widest text-gksbs-leaf-light hover:text-white transition font-medium hidden sm:inline-block">
                    Persembahan
                </a>
                <a href="{{ route('portal.login') }}" class="px-3.5 py-1.5 rounded-full border border-white/40 text-white hover:bg-white/10 text-xs font-semibold uppercase tracking-wider transition">
                    Portal Jemaat
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12 flex-grow w-full">
        
        <article class="overflow-hidden rounded-3xl bg-white border border-slate-200 shadow-sm">
            
            @php
                $churchInfo = $content['church'] ?? [];
                $logoUrl = $churchInfo['logo_url'] ?? $church->logo_url ?? null;
                $synodName = $churchInfo['synod'] ?? $church->synod ?? 'Sinode Gereja Kristen Sumatera Bagian Selatan (GKSBS)';
            @endphp

            <!-- Kop Surat & Buletin Resmi Gereja -->
            <header class="border-b border-slate-200 bg-[#fbfcfd] px-6 sm:px-12 py-8 sm:py-10 text-center relative">
                
                <!-- Logo -->
                <div class="mx-auto mb-4 flex items-center justify-center">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo Gereja" class="h-16 w-auto max-h-16 object-contain">
                    @else
                        <div class="h-16 w-16 rounded-2xl bg-gksbs-forest/10 border border-gksbs-forest/20 flex items-center justify-center text-gksbs-forest shadow-xs">
                            <svg viewBox="0 0 100 100" class="w-10 h-10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M50 8 C46 22 42 34 50 48 C58 34 54 22 50 8Z" fill="#16a34a"/>
                                <path d="M38 16 C30 27 30 38 43 47 C43 33 42 24 38 16Z" fill="#22c55e"/>
                                <path d="M62 16 C70 27 70 38 57 47 C57 33 58 24 62 16Z" fill="#22c55e"/>
                                <path d="M26 28 C16 38 20 50 36 52 C34 38 31 31 26 28Z" fill="#15803d"/>
                                <path d="M74 28 C84 38 80 50 64 52 C66 38 69 31 74 28Z" fill="#15803d"/>
                                <circle cx="50" cy="50" r="3.5" fill="#ffffff"/>
                                <path d="M20 68 Q50 64 80 68" stroke="#38bdf8" stroke-width="2.5" stroke-linecap="round"/>
                                <path d="M16 75 Q50 71 84 75" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round"/>
                                <path d="M20 82 Q50 78 80 82" stroke="#0369a1" stroke-width="2.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                    @endif
                </div>

                <!-- Synod & Church Name -->
                <div class="text-xs font-bold uppercase tracking-[0.16em] text-gksbs-forest mb-1">
                    {{ $synodName }}
                </div>
                <h1 class="font-heading text-2xl sm:text-3xl font-bold uppercase tracking-tight text-slate-900">
                    {{ $content['church_name'] ?? $church->name }}
                </h1>
                
                @if ($content['church_address'] ?? $church->address)
                    <p class="mt-1 text-xs sm:text-sm text-slate-600 max-w-lg mx-auto">
                        {{ $content['church_address'] ?? $church->address }}
                    </p>
                @endif

                @if (!empty($churchInfo['phone']) || !empty($churchInfo['email']) || !empty($church->phone) || !empty($church->email))
                    <p class="mt-1 text-xs text-slate-400">
                        @if (!empty($churchInfo['phone']) || !empty($church->phone)) 
                            Telp: {{ $churchInfo['phone'] ?? $church->phone }} 
                        @endif
                        @if ((!empty($churchInfo['phone']) || !empty($church->phone)) && (!empty($churchInfo['email']) || !empty($church->email))) 
                            &bull; 
                        @endif
                        @if (!empty($churchInfo['email']) || !empty($church->email)) 
                            Email: {{ $churchInfo['email'] ?? $church->email }} 
                        @endif
                    </p>
                @endif

                <!-- Classic Kop Divider -->
                <div class="my-5 flex items-center justify-center gap-3">
                    <div class="w-24 h-px bg-slate-300"></div>
                    <div class="text-gksbs-forest/60">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M7 8h10" stroke-linecap="round"/></svg>
                    </div>
                    <div class="w-24 h-px bg-slate-300"></div>
                </div>

                <!-- Warta Jemaat Title -->
                <h2 class="font-heading text-xl sm:text-2xl font-bold tracking-tight text-gksbs-forest uppercase">
                    Warta Jemaat
                </h2>
                @if ($publication->title)
                    <h3 class="font-heading text-lg sm:text-xl font-bold text-slate-900 mt-1">
                        {{ $publication->title }}
                    </h3>
                @endif

                <div class="mt-2.5 flex flex-wrap items-center justify-center gap-2 text-xs">
                    @if ($content['edition_label'] ?? null)
                        <span class="font-bold uppercase tracking-wider text-slate-500 bg-slate-100 px-3 py-0.5 rounded-full text-[10px]">
                            {{ $content['edition_label'] }}
                        </span>
                    @endif
                    @if ($content['period_label'] ?? null)
                        <span class="font-bold text-gksbs-forest bg-gksbs-forest/10 px-3 py-0.5 rounded-full border border-gksbs-forest/20 text-[10px]">
                            {{ $content['period_label'] }}
                        </span>
                    @endif
                </div>
            </header>

            <!-- Bulletin Body Content -->
            <div class="space-y-8 sm:space-y-10 px-6 sm:px-12 py-8 sm:py-10 text-xs sm:text-sm">

                <!-- Renungan / Firman -->
                @if (!empty($content['reflection']) || !empty($content['renungan']))
                    <section class="rounded-2xl bg-gksbs-forest/5 p-5 sm:p-6 border border-gksbs-forest/15 space-y-2">
                        <div class="flex items-center gap-2 text-gksbs-forest font-heading font-bold text-sm sm:text-base">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 19.5A2.5 2.5 0 0 0 6.5 22H20V4.5A2.5 2.5 0 0 0 17.5 2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/></svg>
                            <span>Renungan &amp; Firman Penguat</span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line font-heading italic">
                            {{ $content['reflection'] ?? $content['renungan'] }}
                        </p>
                    </section>
                @endif

                <!-- Jadwal Ibadah & Pelayanan -->
                @if (!empty($content['events']))
                    <section class="space-y-3">
                        <div class="border-b border-slate-200 pb-2">
                            <h3 class="font-heading text-base sm:text-lg font-bold text-slate-900">
                                Jadwal Ibadah &amp; Pelayanan
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs sm:text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 text-left text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                        <th class="py-2.5 pr-4">Waktu</th>
                                        <th class="py-2.5 pr-4">Acara</th>
                                        <th class="py-2.5 pr-4">Lokasi</th>
                                        <th class="py-2.5">Petugas</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($content['events'] as $event)
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="py-2.5 pr-4 whitespace-nowrap font-medium text-slate-600">{{ $event['start'] }}</td>
                                            <td class="py-2.5 pr-4 font-bold text-slate-900">{{ $event['name'] }}</td>
                                            <td class="py-2.5 pr-4 text-slate-600">{{ $event['location'] }}</td>
                                            <td class="py-2.5 text-slate-600">{{ $event['officials'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                <!-- Ulang Tahun Jemaat -->
                @if (!empty($content['birthdays']))
                    <section class="space-y-3">
                        <div class="border-b border-slate-200 pb-2">
                            <h3 class="font-heading text-base sm:text-lg font-bold text-slate-900">
                                Ulang Tahun Jemaat
                            </h3>
                        </div>
                        <div class="grid gap-2.5 sm:grid-cols-2">
                            @foreach ($content['birthdays'] as $b)
                                <div class="flex items-center justify-between rounded-xl bg-[#fbfcfd] border border-slate-200/80 px-4 py-2.5 text-xs">
                                    <span class="font-bold text-slate-900">{{ $b['name'] }}</span>
                                    <span class="text-slate-500 font-medium">{{ $b['date'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- Sakramen -->
                @if (!empty($content['sacraments']))
                    <section class="space-y-3">
                        <div class="border-b border-slate-200 pb-2">
                            <h3 class="font-heading text-base sm:text-lg font-bold text-slate-900">
                                Perayaan Sakramen
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs sm:text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 text-left text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                        <th class="py-2.5 pr-4">Tanggal</th>
                                        <th class="py-2.5 pr-4">Jenis</th>
                                        <th class="py-2.5 pr-4">Nama</th>
                                        <th class="py-2.5">Pelayan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($content['sacraments'] as $s)
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="py-2.5 pr-4 whitespace-nowrap text-slate-600">{{ $s['date'] }}</td>
                                            <td class="py-2.5 pr-4 font-semibold text-gksbs-forest">{{ $s['type'] }}</td>
                                            <td class="py-2.5 pr-4 font-bold text-slate-900">{{ $s['name'] }}</td>
                                            <td class="py-2.5 text-slate-600">{{ $s['official'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                <!-- Pengumuman Jemaat -->
                @if (!empty($content['announcements']) && is_array($content['announcements']))
                    <section class="space-y-3">
                        <div class="border-b border-slate-200 pb-2">
                            <h3 class="font-heading text-base sm:text-lg font-bold text-slate-900">
                                Warta &amp; Pengumuman Jemaat
                            </h3>
                        </div>
                        <div class="space-y-3">
                            @foreach ($content['announcements'] as $item)
                                <div class="p-4 rounded-2xl bg-[#fbfcfd] border border-slate-200/80 text-xs sm:text-sm space-y-1">
                                    <h4 class="font-bold text-slate-900 text-sm sm:text-base">{{ $item['title'] ?? 'Pengumuman' }}</h4>
                                    <p class="text-slate-600 leading-relaxed">{{ $item['body'] ?? $item['content'] ?? '' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- Laporan Keuangan Kas Tunai per Kantong -->
                @if (!empty($content['finance']))
                    @php
                        $fmt = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
                        $finance = $content['finance'];
                        $funds = $finance['funds'] ?? [];
                    @endphp
                    <section class="space-y-4 pt-2">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                            <h3 class="font-heading text-base sm:text-lg font-bold text-slate-900">
                                Laporan Keuangan Kas Tunai
                            </h3>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-0.5 text-xs font-bold text-emerald-800 border border-emerald-200">
                                100% Kas Tunai
                            </span>
                        </div>

                        <!-- Ringkasan Konsolidasi -->
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 mb-6">
                            <div class="rounded-2xl bg-[#fbfcfd] p-3.5 text-center border border-slate-200">
                                <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-400">Saldo Awal</p>
                                <p class="mt-1 font-bold text-slate-900 text-xs sm:text-sm">{{ $fmt($finance['opening_balance'] ?? 0) }}</p>
                            </div>
                            <div class="rounded-2xl bg-emerald-50/70 p-3.5 text-center border border-emerald-200/80">
                                <p class="text-[10px] uppercase tracking-wider font-bold text-emerald-700">Total Masuk</p>
                                <p class="mt-1 font-black text-emerald-700 text-xs sm:text-sm">+{{ $fmt($finance['total_income'] ?? 0) }}</p>
                            </div>
                            <div class="rounded-2xl bg-rose-50/70 p-3.5 text-center border border-rose-200/80">
                                <p class="text-[10px] uppercase tracking-wider font-bold text-rose-700">Total Keluar</p>
                                <p class="mt-1 font-black text-rose-700 text-xs sm:text-sm">−{{ $fmt($finance['total_expenses'] ?? 0) }}</p>
                            </div>
                            <div class="rounded-2xl bg-amber-50/70 p-3.5 text-center border border-amber-200/80">
                                <p class="text-[10px] uppercase tracking-wider font-bold text-amber-800">Saldo Akhir</p>
                                <p class="mt-1 font-black text-amber-900 text-xs sm:text-sm">{{ $fmt($finance['closing_balance'] ?? 0) }}</p>
                            </div>
                        </div>

                        <!-- Rincian per Kantong Kas -->
                        @if (!empty($funds))
                            <div class="space-y-4">
                                @foreach ($funds as $fund)
                                    <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs">
                                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                                            <div class="flex items-center gap-2">
                                                <div class="h-2.5 w-2.5 rounded-full bg-gksbs-forest"></div>
                                                <h4 class="font-heading font-bold text-slate-900 text-sm sm:text-base">{{ $fund['name'] ?? 'Pos Kas' }}</h4>
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                Saldo Awal: <span class="font-bold text-slate-800">{{ $fmt($fund['opening_balance'] ?? 0) }}</span>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mt-3 text-xs">
                                            <!-- Uang Masuk -->
                                            <div class="rounded-xl bg-emerald-50/40 p-3 border border-emerald-200/60">
                                                <div class="flex items-center justify-between font-bold text-[11px] uppercase tracking-wider text-emerald-900 mb-1.5 border-b border-emerald-200/60 pb-1">
                                                    <span>Pos Uang Masuk</span>
                                                    <span>Jumlah</span>
                                                </div>
                                                <ul class="space-y-1.5 text-xs">
                                                    @forelse ($fund['income']['items'] ?? [] as $item)
                                                        <li class="flex justify-between text-slate-700">
                                                            <span>{{ $item['category'] ?? '-' }}</span>
                                                            <span class="font-semibold text-emerald-700">+{{ $fmt($item['amount'] ?? 0) }}</span>
                                                        </li>
                                                    @empty
                                                        <li class="text-slate-400 italic text-center py-1">Tidak ada uang masuk</li>
                                                    @endforelse
                                                </ul>
                                                <div class="mt-2.5 pt-1.5 border-t border-emerald-200/80 flex justify-between font-bold text-xs text-emerald-900">
                                                    <span>Total Masuk</span>
                                                    <span>+{{ $fmt($fund['income']['total'] ?? 0) }}</span>
                                                </div>
                                            </div>

                                            <!-- Uang Keluar -->
                                            <div class="rounded-xl bg-rose-50/40 p-3 border border-rose-200/60">
                                                <div class="flex items-center justify-between font-bold text-[11px] uppercase tracking-wider text-rose-900 mb-1.5 border-b border-rose-200/60 pb-1">
                                                    <span>Pos Uang Keluar</span>
                                                    <span>Jumlah</span>
                                                </div>
                                                <ul class="space-y-1.5 text-xs">
                                                    @forelse ($fund['expense']['items'] ?? [] as $item)
                                                        <li class="flex justify-between text-slate-700">
                                                            <span>{{ $item['category'] ?? '-' }}</span>
                                                            <span class="font-semibold text-rose-700">−{{ $fmt($item['amount'] ?? 0) }}</span>
                                                        </li>
                                                    @empty
                                                        <li class="text-slate-400 italic text-center py-1">Tidak ada uang keluar</li>
                                                    @endforelse
                                                </ul>
                                                <div class="mt-2.5 pt-1.5 border-t border-rose-200/80 flex justify-between font-bold text-xs text-rose-900">
                                                    <span>Total Keluar</span>
                                                    <span>−{{ $fmt($fund['expense']['total'] ?? 0) }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Saldo Akhir Kantong -->
                                        <div class="mt-3 pt-2.5 border-t border-dashed border-slate-200 flex items-center justify-between bg-[#fbfcfd] rounded-xl px-3.5 py-2 text-xs">
                                            <span class="font-semibold text-slate-700">Saldo Akhir {{ $fund['name'] ?? '' }}</span>
                                            <span class="font-black text-gksbs-forest text-sm">{{ $fmt($fund['closing_balance'] ?? 0) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endif
            </div>

            <!-- Pastoral Bulletin Footer -->
            <footer class="border-t border-slate-200 bg-[#fbfcfd] px-6 sm:px-12 py-5 text-center text-xs text-slate-400 flex flex-col sm:flex-row items-center justify-between gap-2">
                <span>Diterbitkan {{ $publication->published_at?->locale('id')->translatedFormat('d F Y, H:i') }} WIB &bull; Portal Resmi Gereja</span>
                <span class="font-heading italic text-slate-500">Soli Deo Gloria</span>
            </footer>
        </article>
    </main>

    <!-- Page Footer -->
    <footer class="bg-gksbs-forest-dark text-white/70 text-xs border-t border-white/10 py-8 print:hidden mt-auto">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
            <div>
                <span class="font-heading text-sm text-white font-normal block mb-0.5">{{ $church->name }}</span>
                <p class="text-white/50 text-[11px]">&copy; {{ date('Y') }} Sinode GKSBS. Pelayanan untuk kemuliaan Kristus.</p>
            </div>
            <div class="flex items-center space-x-4 uppercase tracking-wider text-[11px]">
                <a href="{{ url('/') }}" class="text-white/70 hover:text-white transition">Beranda</a>
                <a href="{{ route('public.warta.index', ['church' => $church->code]) }}" class="text-white/70 hover:text-white transition">Warta</a>
                <a href="{{ route('portal.login') }}" class="text-white/70 hover:text-white transition">Portal</a>
            </div>
        </div>
    </footer>

</body>
</html>
