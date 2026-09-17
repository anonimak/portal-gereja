<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Warta Jemaat — {{ $church->name }}</title>
    <meta name="description" content="Edisi resmi Warta Jemaat dan Buletin Pelayanan {{ $church->name }} — Sinode GKSBS">
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
    <header class="bg-gksbs-forest-deep text-white border-b border-white/10 sticky top-0 z-40 shadow-sm">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <div class="h-9 w-9 p-1 rounded-full bg-black/25 border border-white/20 flex items-center justify-center flex-shrink-0">
                    <svg viewBox="0 0 100 100" class="w-7 h-7" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M50 8 C46 22 42 34 50 48 C58 34 54 22 50 8Z" fill="#16a34a"/>
                        <path d="M38 16 C30 27 30 38 43 47 C43 33 42 24 38 16Z" fill="#22c55e"/>
                        <path d="M62 16 C70 27 70 38 57 47 C57 33 58 24 62 16Z" fill="#22c55e"/>
                        <path d="M26 28 C16 38 20 50 36 52 C34 38 31 31 26 28Z" fill="#15803d"/>
                        <path d="M74 28 C84 38 80 50 64 52 C66 38 69 31 74 28Z" fill="#15803d"/>
                        <path d="M18 44 C8 54 14 65 31 60 C26 48 22 44 18 44Z" fill="#166534"/>
                        <path d="M82 44 C92 54 86 65 69 60 C74 48 78 44 82 44Z" fill="#166534"/>
                        <circle cx="50" cy="50" r="3.5" fill="#ffffff"/>
                        <path d="M20 68 Q50 64 80 68" stroke="#38bdf8" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M16 75 Q50 71 84 75" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M20 82 Q50 78 80 82" stroke="#0369a1" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M24 89 Q50 85 76 89" stroke="#0c4a6e" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <div>
                    <span class="font-heading text-lg font-bold block leading-none text-white">{{ $church->name }}</span>
                    <span class="text-[10px] text-white/70 uppercase tracking-widest font-medium">Sinode GKSBS</span>
                </div>
            </a>

            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}" class="text-xs uppercase tracking-widest text-white/80 hover:text-white transition font-medium hidden sm:inline-block">
                    &larr; Beranda
                </a>
                <a href="{{ route('portal.login') }}" class="px-3.5 py-1.5 rounded-full border border-white/40 text-white hover:bg-white/10 text-xs font-semibold uppercase tracking-wider transition">
                    Portal Jemaat
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14 flex-grow w-full">
        
        <!-- Church Banner & Sacred Kop Header -->
        <header class="mb-10 sm:mb-12 text-center space-y-4">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gksbs-forest/10 border border-gksbs-forest/20 text-gksbs-forest shadow-xs">
                @if ($church->logo_url)
                    <img src="{{ $church->logo_url }}" alt="{{ $church->name }}" class="h-10 w-auto max-w-[50px] object-contain">
                @else
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
                @endif
            </div>

            <div class="space-y-1">
                <span class="text-xs uppercase tracking-[0.2em] font-bold text-gksbs-forest block">
                    {{ $church->synod ?? 'Sinode Gereja Kristen Sumatera Bagian Selatan (GKSBS)' }}
                </span>
                <h1 class="font-heading text-3xl sm:text-4xl font-bold text-slate-900 tracking-tight">
                    {{ $church->name }}
                </h1>
                @if ($church->address)
                    <p class="text-xs sm:text-sm text-slate-500 max-w-lg mx-auto pt-1">{{ $church->address }}</p>
                @endif
            </div>

            <!-- Sacred Divider with Cross Motif -->
            <div class="flex items-center justify-center gap-3 py-1">
                <div class="w-16 h-px bg-slate-200"></div>
                <div class="text-gksbs-forest/60">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M7 8h10" stroke-linecap="round"/></svg>
                </div>
                <div class="w-16 h-px bg-slate-200"></div>
            </div>

            <div>
                <h2 class="font-heading text-xl sm:text-2xl font-normal italic text-slate-700">
                    Arsip Warta Jemaat & Buletin Mingguan
                </h2>
            </div>

            <!-- Pemilih Gereja Pills (jika ada > 1 gereja dengan warta published) -->
            @if ($churches->count() > 1)
                <div class="pt-3 flex flex-wrap items-center justify-center gap-2 text-xs">
                    <span class="text-slate-400 font-semibold uppercase tracking-wider text-[10px] mr-1">Pilih Jemaat:</span>
                    @foreach ($churches as $c)
                        <a href="{{ route('public.warta.index', ['church' => $c->code]) }}"
                           class="rounded-full px-4 py-1.5 font-bold uppercase tracking-wider transition text-[11px] {{ $c->id === $church->id ? 'bg-gksbs-forest text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200' }}">
                            {{ $c->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </header>

        <!-- List Publikasi Warta -->
        @if ($publications->isEmpty())
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500 shadow-xs space-y-3">
                <div class="h-12 w-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 19.5A2.5 2.5 0 0 0 6.5 22H20V4.5A2.5 2.5 0 0 0 17.5 2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/></svg>
                </div>
                <p class="font-heading text-lg font-bold text-slate-800">Belum Ada Warta yang Dipublikasikan</p>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">Silakan kunjungi kembali nanti untuk melihat edisi buletin pelayanan berikutnya.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($publications as $pub)
                    <a href="{{ route('public.warta.show', ['church' => $church->code, 'publication' => $pub->id]) }}"
                       class="group block rounded-3xl bg-white p-6 sm:p-7 border border-slate-200/80 shadow-xs transition hover:shadow-md hover:border-gksbs-forest/40 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gksbs-forest bg-gksbs-forest/10 px-3 py-1 rounded-full border border-gksbs-forest/20">
                                {{ $pub->content['period_label'] ?? $pub->periodLabel() }}
                            </span>
                            <span class="text-[11px] text-slate-400 font-medium">
                                {{ $pub->published_at?->locale('id')->translatedFormat('d F Y, H:i') }} WIB
                            </span>
                        </div>

                        <h3 class="font-heading text-xl sm:text-2xl font-bold text-slate-900 group-hover:text-gksbs-forest transition leading-snug">
                            {{ $pub->title }}
                        </h3>

                        @if (!empty($pub->content['theme']))
                            <p class="text-xs text-slate-600 italic font-heading">
                                Tema: &ldquo;{{ $pub->content['theme'] }}&rdquo;
                            </p>
                        @endif

                        <div class="pt-3 flex items-center justify-between text-xs text-slate-400 border-t border-slate-100">
                            <span class="text-[11px]">Diterbitkan melalui Portal Resmi Gereja</span>
                            <span class="font-bold text-gksbs-forest group-hover:translate-x-0.5 transition inline-flex items-center gap-1">
                                <span>Buka Warta Lengkap</span>
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </main>

    <!-- Pastoral Footer -->
    <footer class="bg-gksbs-forest-dark text-white/70 text-xs border-t border-white/10 py-10 mt-auto">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
                <div>
                    <span class="font-heading text-base text-white font-normal block mb-1">
                        {{ $church->name }}
                    </span>
                    <p class="text-white/50 text-[11px]">
                        &copy; {{ date('Y') }} Sinode Gereja Kristen Sumatera Bagian Selatan (GKSBS).
                    </p>
                </div>

                <div class="flex items-center space-x-4 font-medium uppercase tracking-wider text-[11px]">
                    <a href="{{ url('/') }}" class="text-white/70 hover:text-white transition">Beranda</a>
                    <a href="{{ route('public.offering.index') }}" class="text-gksbs-leaf-light hover:text-white transition">Persembahan</a>
                    <a href="{{ route('portal.login') }}" class="text-white/70 hover:text-white transition">Portal Jemaat</a>
                </div>

                <div class="font-heading text-sm text-white/40 italic">
                    Soli Deo Gloria
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
