@php
    $setting = \App\Models\LandingSetting::current();
    $cmsLogo = $setting?->logo_url ?: (auth()->user()?->church?->logo_url ?: null);
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Portal Mandiri Jemaat') — {{ auth()->user()?->church?->name ?? ($setting?->hero_title ?? 'Gereja Kristen Sumatera Bagian Selatan') }}</title>
    <link rel="icon" href="{{ $setting?->favicon_url ?: asset('favicon.ico') }}">

    <!-- Google Fonts: Playfair Display (Serif Elegan) & Plus Jakarta Sans (Modern & Bersih) -->
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
<body class="bg-[#fbfcfd] text-slate-800 antialiased min-h-screen flex flex-col selection:bg-gksbs-ocean selection:text-white">

    <!-- Header / Navbar Resmi GKSBS -->
    <header class="bg-gksbs-forest-deep text-white border-b border-white/10 sticky top-0 z-40 shadow-sm">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                
                <!-- Left: Identity & Logo -->
                <div class="flex items-center space-x-3.5">
                    <a href="{{ route('portal.profile') }}" class="flex items-center gap-3 group">
                        @if($cmsLogo)
                            <img src="{{ $cmsLogo }}" alt="Logo Gereja" class="h-9 sm:h-10 w-auto max-w-[120px] object-contain flex-shrink-0">
                        @else
                            <div class="h-10 w-10 flex-shrink-0 flex items-center justify-center p-1 rounded-full bg-black/30 border border-white/20 group-hover:border-gksbs-leaf-light transition">
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
                        @endif
                        <div>
                            <span class="font-heading text-lg sm:text-xl font-semibold tracking-tight text-white block leading-tight group-hover:text-emerald-300 transition">
                                {{ auth()->user()?->church?->name ?? ($setting?->hero_title ?? 'Portal Jemaat GKSBS') }}
                            </span>
                            <span class="text-[10px] text-white/70 uppercase tracking-widest font-medium block">
                                Portal Pelayanan Jemaat
                            </span>
                        </div>
                    </a>
                </div>

                @auth
                    <!-- Nav links desktop -->
                    <nav class="hidden md:flex items-center space-x-1.5 text-xs font-semibold uppercase tracking-wider">
                        <a href="{{ route('portal.profile') }}"
                           class="px-3.5 py-2 rounded-full transition flex items-center gap-1.5 {{ request()->routeIs('portal.profile') ? 'bg-white/15 text-white border border-white/20 shadow-xs' : 'text-white/80 hover:text-white hover:bg-white/10' }}">
                            <svg class="w-4 h-4 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <span>Data Diri</span>
                        </a>
                        <a href="{{ route('portal.events') }}"
                           class="px-3.5 py-2 rounded-full transition flex items-center gap-1.5 {{ request()->routeIs('portal.events*') || request()->routeIs('portal.schedules*') ? 'bg-white/15 text-white border border-white/20 shadow-xs' : 'text-white/80 hover:text-white hover:bg-white/10' }}">
                            <svg class="w-4 h-4 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <span>Jadwal Ibadah</span>
                        </a>
                        <a href="{{ route('portal.warta') }}"
                           class="px-3.5 py-2 rounded-full transition flex items-center gap-1.5 {{ request()->routeIs('portal.warta*') ? 'bg-white/15 text-white border border-white/20 shadow-xs' : 'text-white/80 hover:text-white hover:bg-white/10' }}">
                            <svg class="w-4 h-4 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 19.5A2.5 2.5 0 0 0 6.5 22H20V4.5A2.5 2.5 0 0 0 17.5 2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/></svg>
                            <span>Warta Jemaat</span>
                        </a>
                        <a href="{{ route('portal.offerings') }}"
                           class="px-3.5 py-2 rounded-full transition flex items-center gap-1.5 {{ request()->routeIs('portal.offerings*') ? 'bg-emerald-600/40 text-gksbs-leaf-light border border-gksbs-leaf-light/50 font-bold' : 'text-white/80 hover:text-white hover:bg-white/10' }}">
                            <svg class="w-4 h-4 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            <span>Persembahan</span>
                        </a>
                    </nav>

                    <!-- Right User Profile & Logout -->
                    <div class="flex items-center space-x-3.5">
                        <div class="hidden sm:block text-right">
                            <span class="text-xs font-bold text-white block leading-tight">{{ auth()->user()->name }}</span>
                            <span class="text-[10px] text-white/60 block uppercase tracking-wider font-semibold">
                                {{ auth()->user()->member?->family_relation ? str_replace('_', ' ', auth()->user()->member->family_relation) : 'Warga Jemaat' }}
                            </span>
                        </div>
                        <form action="{{ route('portal.logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" 
                                    title="Keluar dari Portal" 
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-rose-200 hover:text-white bg-rose-950/40 hover:bg-rose-900/60 rounded-full transition border border-rose-500/30">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                                <span>Keluar</span>
                            </button>
                        </form>
                    </div>
                @endauth

                @guest
                    <div class="flex items-center gap-2">
                        <a href="{{ url('/') }}" class="text-xs text-white/80 hover:text-white font-medium uppercase tracking-wider transition">
                            ← Beranda Utama
                        </a>
                    </div>
                @endguest
            </div>
        </div>

        @auth
            <!-- Mobile Navigation Bar (Bottom of Header) -->
            <div class="md:hidden border-t border-white/10 bg-gksbs-forest-dark/95 px-2 py-2 flex justify-around text-[11px] font-semibold uppercase tracking-wider">
                <a href="{{ route('portal.profile') }}" 
                   class="py-1.5 px-2.5 rounded-lg flex flex-col items-center gap-0.5 transition {{ request()->routeIs('portal.profile') ? 'text-white bg-white/15' : 'text-white/70 hover:text-white' }}">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>Profil</span>
                </a>
                <a href="{{ route('portal.events') }}" 
                   class="py-1.5 px-2.5 rounded-lg flex flex-col items-center gap-0.5 transition {{ request()->routeIs('portal.events*') || request()->routeIs('portal.schedules*') ? 'text-white bg-white/15' : 'text-white/70 hover:text-white' }}">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <span>Jadwal</span>
                </a>
                <a href="{{ route('portal.warta') }}" 
                   class="py-1.5 px-2.5 rounded-lg flex flex-col items-center gap-0.5 transition {{ request()->routeIs('portal.warta*') ? 'text-white bg-white/15' : 'text-white/70 hover:text-white' }}">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 19.5A2.5 2.5 0 0 0 6.5 22H20V4.5A2.5 2.5 0 0 0 17.5 2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/></svg>
                    <span>Warta</span>
                </a>
                <a href="{{ route('portal.offerings') }}" 
                   class="py-1.5 px-2.5 rounded-lg flex flex-col items-center gap-0.5 transition {{ request()->routeIs('portal.offerings*') ? 'text-gksbs-leaf-light bg-white/15' : 'text-white/70 hover:text-white' }}">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span>Persembahan</span>
                </a>
            </div>
        @endauth
    </header>

    <!-- Main Content Area -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 flex-1 w-full">
        @if (session('status'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50/90 border border-emerald-200 text-emerald-900 text-xs sm:text-sm font-medium flex items-center gap-3 shadow-xs">
                <div class="h-7 w-7 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0 text-emerald-700">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50/90 border border-emerald-200 text-emerald-900 text-xs sm:text-sm font-medium flex items-center gap-3 shadow-xs">
                <div class="h-7 w-7 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0 text-emerald-700">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 p-4 rounded-2xl bg-rose-50/90 border border-rose-200 text-rose-900 text-xs sm:text-sm font-medium flex items-center gap-3 shadow-xs">
                <div class="h-7 w-7 rounded-full bg-rose-100 flex items-center justify-center flex-shrink-0 text-rose-700">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer Pastoral GKSBS -->
    <footer class="bg-gksbs-forest-dark text-white/70 text-xs border-t border-white/10 py-10 mt-auto">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-5 text-center sm:text-left">
                <div>
                    <span class="font-heading text-base text-white font-medium block mb-1">
                        {{ auth()->user()?->church?->name ?? 'GKSBS' }}
                    </span>
                    <p class="text-white/50 text-[11px] leading-relaxed">
                        &copy; {{ date('Y') }} Sinode Gereja Kristen Sumatera Bagian Selatan (GKSBS). Pelayanan demi kemuliaan Allah.
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-center gap-4 font-medium uppercase tracking-wider text-[11px]">
                    <a href="{{ url('/') }}" class="text-white/70 hover:text-white transition">Beranda Utama</a>
                    <a href="{{ route('public.warta.index') }}" target="_blank" class="text-white/70 hover:text-white transition">Warta Publik</a>
                    <a href="{{ route('public.offering.index') }}" target="_blank" class="text-gksbs-leaf-light hover:text-white transition">Persembahan Digital</a>
                    <a href="{{ url('/admin/login') }}" class="text-white/60 hover:text-white transition">Area Majelis</a>
                </div>

                <div class="font-heading text-sm text-white/40 italic">
                    Soli Deo Gloria
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
