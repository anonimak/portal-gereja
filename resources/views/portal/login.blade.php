@php
    $setting = \App\Models\LandingSetting::current();
    $cmsLogo = $setting?->logo_url ?: null;
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — Portal Mandiri Jemaat {{ $setting?->hero_title ?? 'GKSBS' }}</title>
    <link rel="icon" href="{{ $setting?->favicon_url ?: asset('favicon.ico') }}">

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

    <!-- Top Minimal Navigation -->
    <header class="py-4 px-6 border-b border-slate-100 bg-white/80 backdrop-blur-md">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 text-gksbs-forest hover:text-gksbs-ocean transition">
                @if($cmsLogo)
                    <img src="{{ $cmsLogo }}" alt="Logo Gereja" class="h-8 w-auto max-w-[120px] object-contain flex-shrink-0">
                @else
                    <div class="h-8 w-8 flex-shrink-0 flex items-center justify-center p-1 rounded-full bg-gksbs-forest/10 border border-gksbs-forest/20">
                        <svg viewBox="0 0 100 100" class="w-6 h-6" fill="none" xmlns="http://www.w3.org/2000/svg">
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
                @endif
                <span class="font-heading text-lg font-semibold tracking-tight text-gksbs-forest-deep">
                    {{ $setting?->hero_title ?: 'GKSBS' }}
                </span>
            </a>
            <a href="{{ url('/') }}" class="text-xs font-semibold uppercase tracking-wider text-slate-500 hover:text-gksbs-forest transition flex items-center gap-1">
                ← Kembali ke Beranda
            </a>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-200/80 overflow-hidden">
            
            <!-- Sacred Card Header -->
            <div class="bg-gksbs-forest-deep p-8 text-white text-center relative overflow-hidden">
                <!-- Subtle background radial highlight -->
                <div class="absolute inset-0 bg-radial from-white/10 via-transparent to-transparent pointer-events-none"></div>

                @if($cmsLogo)
                    <div class="inline-flex items-center justify-center mb-3">
                        <img src="{{ $cmsLogo }}" alt="Logo" class="h-14 w-auto max-w-[140px] object-contain drop-shadow">
                    </div>
                @else
                    <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 backdrop-blur-xs border border-white/20 mb-3 shadow-inner">
                        <svg viewBox="0 0 100 100" class="w-9 h-9" fill="none" xmlns="http://www.w3.org/2000/svg">
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
                <h1 class="font-heading text-2xl font-bold tracking-tight text-white">Portal Mandiri Jemaat</h1>
                <p class="text-white/70 text-xs mt-1.5 font-light">Pelayanan Administrasi & Warta Jemaat Digital</p>

                <!-- Ayat Alkitab Penguat -->
                <div class="mt-4 pt-3 border-t border-white/10 text-[11px] text-emerald-200/90 italic font-heading">
                    &ldquo;Tuhan adalah terangku dan keselamatanku, kepada siapakah aku harus takut?&rdquo;
                    <span class="block not-italic text-[10px] text-white/50 mt-0.5 tracking-wider uppercase">— Mazmur 27:1</span>
                </div>
            </div>

            <!-- Card Body & Form -->
            <div class="p-7 sm:p-8">
                @if (session('status'))
                    <div class="mb-5 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-medium flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                        <div class="flex items-center gap-2 font-bold mb-1 text-rose-900">
                            <svg class="w-4 h-4 text-rose-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span>Gagal Masuk</span>
                        </div>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('portal.login.submit') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="login" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Email atau Nomor KTP / NIK
                        </label>
                        <div class="relative">
                            <input type="text"
                                   id="login"
                                   name="login"
                                   value="{{ old('login') }}"
                                   required
                                   autofocus
                                   placeholder="contoh@email.com atau 3171..."
                                   class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-gksbs-forest focus:border-gksbs-forest text-xs sm:text-sm text-slate-900 transition bg-slate-50/50 focus:bg-white placeholder:text-slate-400">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700">
                                Kata Sandi (Password)
                            </label>
                        </div>
                        <div class="relative">
                            <input type="password"
                                   id="password"
                                   name="password"
                                   required
                                   placeholder="••••••••"
                                   class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-gksbs-forest focus:border-gksbs-forest text-xs sm:text-sm text-slate-900 transition bg-slate-50/50 focus:bg-white placeholder:text-slate-400">
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs pt-1">
                        <label class="flex items-center space-x-2 cursor-pointer select-none">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-gksbs-forest focus:ring-gksbs-forest">
                            <span class="text-slate-600 text-[11px]">Ingat saya di perangkat ini</span>
                        </label>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                class="w-full py-3.5 px-5 rounded-full bg-gksbs-forest hover:bg-gksbs-forest-deep text-white font-bold text-xs uppercase tracking-wider shadow-md hover:shadow-lg transition transform active:scale-[0.99] flex items-center justify-center gap-2">
                            <span>Masuk ke Portal</span>
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    </div>
                </form>

                <div class="mt-8 pt-6 border-t border-slate-100 text-center text-xs text-slate-500 space-y-2.5">
                    <p class="leading-relaxed">
                        Belum memiliki akun atau butuh aktivasi NIK? Hubungi Sekretariat Majelis Jemaat setempat.
                    </p>
                    <div class="pt-1 flex items-center justify-center gap-4 text-xs font-semibold">
                        <a href="{{ route('public.warta.index') }}" class="text-gksbs-forest hover:text-gksbs-ocean transition">
                            Lihat Warta Publik →
                        </a>
                        <span class="text-slate-300">&bull;</span>
                        <a href="{{ route('public.offering.index') }}" class="text-gksbs-leaf hover:text-gksbs-forest transition">
                            Persembahan Digital →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Simple -->
    <footer class="py-6 text-center text-xs text-slate-400">
        <p>&copy; {{ date('Y') }} Sinode Gereja Kristen Sumatera Bagian Selatan (GKSBS) &bull; Soli Deo Gloria</p>
    </footer>

</body>
</html>
