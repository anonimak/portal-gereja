@php
    $setting = $setting ?? \App\Models\LandingSetting::current();
    $churches = $churches ?? \App\Models\Church::all();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $setting->hero_title ?? 'GKSBS Filadelfia' }} — Website Resmi Gereja</title>
    <meta name="description" content="Website Resmi {{ $setting->hero_title ?? 'GKSBS Filadelfia' }}. {{ $setting->hero_tagline ?? 'Gereja yang Terbuka, Oikumenis, dan Berakar dalam Kasih Kristus' }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Fonts: Plus Jakarta Sans & Cormorant Garamond / Serif for sacred scriptures -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700;800&family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400;1,600&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        serif: ['"Playfair Display"', 'Georgia', 'serif'],
                        cinzel: ['"Cinzel"', 'serif'],
                    },
                    colors: {
                        sacred: {
                            50: '#fdfbf7',
                            100: '#f7f4ed',
                            200: '#eee7d8',
                            300: '#dfd2b9',
                            400: '#cdb694',
                            500: '#b89970',
                            600: '#9d7c54',
                            700: '#7e6141',
                            800: '#644e36',
                            900: '#52402e',
                        },
                        amber: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                            950: '#451a03',
                        },
                        emerald: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .font-serif-sacred {
            font-family: 'Playfair Display', Georgia, serif;
        }
        .font-cinzel {
            font-family: 'Cinzel', serif;
        }
        .pattern-crosses {
            background-image: radial-gradient(rgba(180, 83, 9, 0.07) 1px, transparent 1px);
            background-size: 28px 28px;
        }
        .glass-header {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
    </style>
</head>
<body class="bg-[#faf8f5] text-slate-800 antialiased selection:bg-amber-600 selection:text-white min-h-screen flex flex-col">

    <!-- ================================================================= -->
    <!-- 1. HEADER & STICKY NAVBAR -->
    <!-- ================================================================= -->
    <header class="sticky top-0 z-50 glass-header border-b border-amber-900/10 transition-all duration-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                
                <!-- Logo & Church Brand -->
                <a href="#beranda" class="flex items-center gap-3.5 group">
                    <div class="h-11 w-11 rounded-2xl bg-gradient-to-br from-amber-700 via-amber-600 to-amber-800 flex items-center justify-center text-white shadow-md shadow-amber-900/20 group-hover:scale-105 transition-transform duration-200 ring-2 ring-amber-500/30">
                        <!-- Cross Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-amber-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="2" x2="12" y2="22"></line>
                            <line x1="6" y1="8" x2="18" y2="8"></line>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight leading-tight">
                                {{ $setting->hero_title ?? 'GKSBS Filadelfia' }}
                            </span>
                            <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-amber-100 text-amber-900 border border-amber-200">
                                Gereja Induk
                            </span>
                        </div>
                        <p class="text-[11px] sm:text-xs text-amber-800/80 font-medium tracking-normal line-clamp-1">
                            {{ $setting->hero_subtitle ?? 'Gereja Kristen Sumatera Bagian Selatan' }}
                        </p>
                    </div>
                </a>

                <!-- Desktop Nav Anchor Links -->
                <nav class="hidden xl:flex items-center space-x-1 font-medium text-sm">
                    <a href="#beranda" class="px-3 py-2 text-slate-700 hover:text-amber-800 hover:bg-amber-50/80 rounded-xl transition">
                        Beranda
                    </a>
                    <a href="#sambutan" class="px-3 py-2 text-slate-700 hover:text-amber-800 hover:bg-amber-50/80 rounded-xl transition">
                        Sambutan
                    </a>
                    <a href="#profil" class="px-3 py-2 text-slate-700 hover:text-amber-800 hover:bg-amber-50/80 rounded-xl transition">
                        Profil & Visi
                    </a>
                    <a href="#pos-pelayanan" class="px-3 py-2 text-slate-700 hover:text-amber-800 hover:bg-amber-50/80 rounded-xl transition">
                        Pos Pelayanan
                    </a>
                    <a href="#jadwal" class="px-3 py-2 text-slate-700 hover:text-amber-800 hover:bg-amber-50/80 rounded-xl transition">
                        Jadwal Ibadah
                    </a>
                    <a href="#kontak" class="px-3 py-2 text-slate-700 hover:text-amber-800 hover:bg-amber-50/80 rounded-xl transition">
                        Kontak
                    </a>
                </nav>

                <!-- Two Dedicated Access Doors: Portal Jemaat & Area Majelis -->
                <div class="hidden sm:flex items-center gap-2.5">
                    <!-- 1. Pintu Portal Jemaat -->
                    <a href="{{ route('portal.login') }}" 
                       title="Bagi warga jemaat untuk akses data diri, keluarga, dan sakramen"
                       class="inline-flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl border border-slate-300 hover:border-amber-600 bg-white hover:bg-amber-50/50 text-slate-800 hover:text-amber-900 font-semibold text-xs sm:text-sm shadow-xs transition duration-150 group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-700 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Portal Jemaat</span>
                    </a>

                    <!-- 2. Pintu Area Majelis -->
                    <a href="{{ url('/admin/login') }}" 
                       title="Bagi majelis dan admin untuk mengelola administrasi & pelayanan"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-amber-100 font-semibold text-xs sm:text-sm shadow-sm transition duration-150 group border border-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-400 group-hover:rotate-12 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>Area Majelis</span>
                    </a>
                </div>

                <!-- Mobile Hamburger Button -->
                <div class="flex items-center gap-2 xl:hidden">
                    <a href="{{ route('portal.login') }}" class="sm:hidden inline-flex items-center px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-800">
                        Jemaat
                    </a>
                    <button type="button" id="mobile-menu-button" aria-label="Buka Menu Navigasi" class="p-2.5 rounded-xl text-slate-700 hover:text-slate-900 hover:bg-slate-100 focus:outline-none transition">
                        <svg id="icon-menu-open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <svg id="icon-menu-close" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <div id="mobile-menu" class="hidden xl:hidden border-t border-slate-200 bg-white/98 px-4 pt-3 pb-6 space-y-3 shadow-lg">
            <div class="flex flex-col space-y-1 text-sm font-medium">
                <a href="#beranda" class="mobile-nav-link px-3 py-2.5 rounded-lg text-slate-700 hover:bg-amber-50 hover:text-amber-800">Beranda</a>
                <a href="#sambutan" class="mobile-nav-link px-3 py-2.5 rounded-lg text-slate-700 hover:bg-amber-50 hover:text-amber-800">Sambutan Gembala</a>
                <a href="#profil" class="mobile-nav-link px-3 py-2.5 rounded-lg text-slate-700 hover:bg-amber-50 hover:text-amber-800">Profil & Visi Misi</a>
                <a href="#pos-pelayanan" class="mobile-nav-link px-3 py-2.5 rounded-lg text-slate-700 hover:bg-amber-50 hover:text-amber-800">Pos Pelayanan & Cabang</a>
                <a href="#jadwal" class="mobile-nav-link px-3 py-2.5 rounded-lg text-slate-700 hover:bg-amber-50 hover:text-amber-800">Jadwal Ibadah Induk</a>
                <a href="#kontak" class="mobile-nav-link px-3 py-2.5 rounded-lg text-slate-700 hover:bg-amber-50 hover:text-amber-800">Kontak & Lokasi</a>
            </div>

            <div class="pt-3 border-t border-slate-200 grid grid-cols-1 gap-2">
                <a href="{{ route('portal.login') }}" class="flex items-center justify-center gap-2 w-full py-3 px-4 rounded-xl border border-slate-300 bg-amber-50/50 text-amber-950 font-semibold text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    Portal Jemaat (Warga Gereja)
                </a>
                <a href="{{ url('/admin/login') }}" class="flex items-center justify-center gap-2 w-full py-3 px-4 rounded-xl bg-slate-900 text-amber-200 font-semibold text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    Area Majelis & Presbiter
                </a>
            </div>
        </div>
    </header>

    <main class="flex-grow">

        <!-- ================================================================= -->
        <!-- 2. HERO & AYAT TEMA TAHUNAN -->
        <!-- ================================================================= -->
        <section id="beranda" class="relative overflow-hidden bg-gradient-to-b from-slate-950 via-slate-900 to-stone-900 text-white pt-16 pb-24 lg:pt-24 lg:pb-32">
            
            @if($setting->hero_banner_url)
                <div class="absolute inset-0 z-0 opacity-25 mix-blend-overlay bg-cover bg-center" style="background-image: url('{{ $setting->hero_banner_url }}');"></div>
            @endif

            <!-- Decorative Light Glows -->
            <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-96 h-96 bg-amber-600/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-0 right-10 w-80 h-80 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                
                <!-- Badge Tema Tahunan -->
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-950/80 border border-amber-600/40 text-amber-300 text-xs sm:text-sm font-semibold tracking-wide mb-6 shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    <span>Tema Pelayanan & Ayat Perenungan</span>
                </div>

                <!-- Scripture Verse (Sacred Display) -->
                <div class="max-w-4xl mx-auto mb-8">
                    <div class="relative inline-block px-4 sm:px-8">
                        <span class="absolute -top-8 -left-2 sm:-left-6 text-6xl sm:text-7xl text-amber-500/30 font-serif select-none">“</span>
                        <blockquote class="font-serif-sacred text-2xl sm:text-3xl md:text-4xl text-amber-50/95 leading-relaxed sm:leading-relaxed font-normal tracking-tight italic">
                            {{ $setting->theme_verse ?? 'Hendaklah kamu saling mengasihi sebagai saudara dan saling mendahului dalam memberi hormat.' }}
                        </blockquote>
                        <span class="absolute -bottom-10 -right-2 sm:-right-6 text-6xl sm:text-7xl text-amber-500/30 font-serif select-none">”</span>
                    </div>

                    @if($setting->theme_verse_ref)
                        <div class="mt-4">
                            <span class="inline-block px-4 py-1 rounded-md bg-amber-900/40 text-amber-400 font-medium text-sm tracking-wider uppercase border border-amber-800/40">
                                {{ $setting->theme_verse_ref }}
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Church Identity & Tagline -->
                <div class="pt-4 border-t border-slate-800/80 max-w-3xl mx-auto">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white mb-2">
                        {{ $setting->hero_title ?? 'GKSBS Filadelfia' }}
                    </h1>
                    <p class="text-sm sm:text-base text-amber-200/90 font-medium mb-3">
                        {{ $setting->hero_subtitle ?? 'Gereja Kristen Sumatera Bagian Selatan — Klasis Tulang Bawang' }}
                    </p>
                    <p class="text-sm sm:text-base text-slate-400 max-w-2xl mx-auto leading-relaxed">
                        {{ $setting->hero_tagline ?? 'Gereja yang Terbuka, Oikumenis, dan Berakar dalam Kasih Kristus' }}
                    </p>
                </div>

                <!-- Hero Action Buttons -->
                <div class="mt-8 sm:mt-10 flex flex-wrap items-center justify-center gap-3 sm:gap-4">
                    <a href="#jadwal" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-semibold text-sm shadow-lg shadow-amber-600/30 transition transform hover:-translate-y-0.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>Jadwal Ibadah Induk</span>
                    </a>

                    <a href="#pos-pelayanan" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-sm backdrop-blur-sm border border-white/20 transition transform hover:-translate-y-0.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        <span>Pos Pelayanan & Warta</span>
                    </a>

                    <a href="{{ route('portal.login') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-700/80 hover:bg-emerald-700 text-emerald-50 font-semibold text-sm border border-emerald-500/30 transition transform hover:-translate-y-0.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>Portal Jemaat</span>
                    </a>
                </div>

            </div>
        </section>

        <!-- ================================================================= -->
        <!-- 3. SAMBUTAN GEMBALA / MAJELIS JEMAAT -->
        <!-- ================================================================= -->
        <section id="sambutan" class="py-20 lg:py-24 bg-white border-b border-amber-900/5 pattern-crosses">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="max-w-3xl mx-auto text-center mb-12">
                    <span class="inline-block text-xs font-bold uppercase tracking-widest text-amber-700 bg-amber-100/70 px-3 py-1 rounded-full mb-3">
                        Warta Gembala
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                        {{ $setting->pastoral_greeting_title ?? 'Salam Kasih & Damai Sejahtera Kristus' }}
                    </h2>
                    <div class="w-20 h-1 bg-amber-600 mx-auto mt-4 rounded-full"></div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-center max-w-6xl mx-auto bg-[#fdfbf7] p-6 sm:p-10 lg:p-12 rounded-3xl border border-amber-900/10 shadow-sm">
                    
                    <!-- Pastoral Photo & Profile Card -->
                    <div class="lg:col-span-5 flex flex-col items-center text-center">
                        <div class="relative group">
                            <!-- Golden Frame Ring -->
                            <div class="absolute -inset-2 rounded-3xl bg-gradient-to-tr from-amber-600 via-amber-400 to-amber-700 opacity-30 blur-sm group-hover:opacity-50 transition duration-300"></div>
                            
                            <div class="relative w-56 h-64 sm:w-64 sm:h-72 rounded-2xl overflow-hidden bg-slate-900 shadow-xl border-4 border-white flex items-center justify-center">
                                @if($setting->pastoral_photo_url)
                                    <img src="{{ $setting->pastoral_photo_url }}" alt="{{ $setting->pastoral_greeting_author }}" class="w-full h-full object-cover">
                                @else
                                    <!-- Fallback Pastoral Illustration / Reverent Icon -->
                                    <div class="w-full h-full bg-gradient-to-br from-slate-900 via-amber-950 to-slate-950 flex flex-col items-center justify-center p-6 text-amber-200">
                                        <div class="w-20 h-20 rounded-full bg-amber-900/50 border border-amber-500/40 flex items-center justify-center mb-3 text-amber-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="12" y1="2" x2="12" y2="22"></line>
                                                <line x1="6" y1="8" x2="18" y2="8"></line>
                                            </svg>
                                        </div>
                                        <span class="text-xs font-semibold text-amber-300/80 uppercase tracking-widest">Pelayanan Pastoral</span>
                                        <span class="text-[11px] text-amber-400/60 mt-1">Majelis Jemaat</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-6">
                            <h3 class="text-xl font-bold text-slate-900">
                                {{ $setting->pastoral_greeting_author ?? 'Pdt. Samuel Kriswanto, M.Th.' }}
                            </h3>
                            <p class="text-sm font-semibold text-amber-800 mt-0.5">
                                {{ $setting->pastoral_greeting_author_role ?? 'Ketua Majelis Jemaat GKSBS Filadelfia' }}
                            </p>
                            <span class="inline-flex items-center gap-1.5 mt-2 px-3 py-1 rounded-full text-xs font-medium bg-amber-100/80 text-amber-900 border border-amber-200/80">
                                <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                                Gereja Induk & Pos Pelayanan
                            </span>
                        </div>
                    </div>

                    <!-- Pastoral Greeting Message -->
                    <div class="lg:col-span-7 space-y-5 text-slate-700 leading-relaxed text-base sm:text-lg">
                        <div class="relative pl-6 border-l-4 border-amber-600">
                            <div class="prose prose-slate max-w-none text-slate-700 leading-relaxed font-normal">
                                {!! nl2br(e($setting->pastoral_greeting_content ?? "Selamat datang di Website Resmi GKSBS Filadelfia. Kami bersyukur atas kasih karunia Tuhan yang terus memelihara persekutuan jemaat induk dan seluruh jemaat kelompok (Candimas, Trimulyo, Margomulyo). Mari bersama kita terus berakar di dalam Kristus, bertumbuh dalam iman, dan berbuah lebat bagi masyarakat sekitar.")) !!}
                            </div>
                        </div>

                        <div class="pt-4 border-t border-amber-900/10 flex items-center justify-between">
                            <p class="text-xs sm:text-sm text-slate-500 italic">
                                "Tuhan memberkati dan melindungi persekutuan kita senantiasa."
                            </p>
                            <div class="font-serif-sacred text-amber-900 font-bold text-lg opacity-80">
                                Soli Deo Gloria
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- ================================================================= -->
        <!-- 4. PROFIL, VISI & MISI GEREJA -->
        <!-- ================================================================= -->
        <section id="profil" class="py-20 lg:py-24 bg-[#faf8f5] border-b border-amber-900/5">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="max-w-3xl mx-auto text-center mb-16">
                    <span class="inline-block text-xs font-bold uppercase tracking-widest text-amber-700 bg-amber-100/70 px-3 py-1 rounded-full mb-3">
                        Identitas & Panggilan
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                        Profil, Visi, dan Misi Gereja
                    </h2>
                    <p class="mt-3 text-slate-600 text-sm sm:text-base">
                        Mengenal perjalanan persekutuan dan arah panggilan pelayanan gereja di tengah jemaat dan masyarakat luas.
                    </p>
                    <div class="w-20 h-1 bg-amber-600 mx-auto mt-4 rounded-full"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    
                    <!-- Kartu 1: Profil & Sejarah Singkat -->
                    <div class="bg-white p-7 sm:p-8 rounded-2xl border border-amber-900/10 shadow-sm hover:shadow-md transition duration-200 flex flex-col">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center mb-6 ring-4 ring-amber-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 21h18"></path>
                                <path d="M5 21V7l7-4 7 4v14"></path>
                                <path d="M9 10a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v11H9V10z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Tentang Gereja</h3>
                        <p class="text-slate-600 text-sm sm:text-base leading-relaxed flex-grow">
                            {{ $setting->about_summary ?? 'GKSBS Filadelfia adalah persekutuan jemaat yang berpusat pada Kristus, melayani warga jemaat di wilayah Lampung Tengah dan sekitarnya melalui jemaat induk dan pos-pos pelayanan kelompok jemaat secara terpadu dan guyub.' }}
                        </p>
                        <div class="mt-6 pt-4 border-t border-slate-100 text-xs font-semibold text-amber-800">
                            Klasis Tulang Bawang — Sinode GKSBS
                        </div>
                    </div>

                    <!-- Kartu 2: Visi Gereja -->
                    <div class="bg-white p-7 sm:p-8 rounded-2xl border border-amber-900/10 shadow-sm hover:shadow-md transition duration-200 flex flex-col relative overflow-hidden">
                        <div class="absolute -right-8 -top-8 w-28 h-28 bg-amber-50 rounded-full blur-xl pointer-events-none"></div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center mb-6 ring-4 ring-emerald-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="m12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                                <path d="M2 12h20"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Visi Pelayanan</h3>
                        <blockquote class="text-slate-700 text-sm sm:text-base leading-relaxed italic font-serif-sacred border-l-2 border-emerald-600 pl-4 py-1 flex-grow">
                            “{{ $setting->vision ?? 'Terwujudnya jemaat yang mandiri, misioner, berwawasan oikumenis, serta menjadi berkat nyata bagi sesama ciptaan Tuhan.' }}”
                        </blockquote>
                        <div class="mt-6 pt-4 border-t border-slate-100 text-xs font-semibold text-emerald-800">
                            Arah Strategis & Panggilan Iman
                        </div>
                    </div>

                    <!-- Kartu 3: Misi Gereja -->
                    <div class="bg-white p-7 sm:p-8 rounded-2xl border border-amber-900/10 shadow-sm hover:shadow-md transition duration-200 flex flex-col">
                        <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-800 flex items-center justify-center mb-6 ring-4 ring-slate-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 11 3 3L22 4"></path>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Misi Pelayanan</h3>
                        
                        <ul class="space-y-3 text-sm text-slate-600 flex-grow">
                            @if(!empty($setting->missions) && is_array($setting->missions))
                                @foreach($setting->missions as $mission)
                                    @php
                                        $missionText = is_array($mission) ? ($mission['item'] ?? '') : $mission;
                                    @endphp
                                    @if(filled($missionText))
                                        <li class="flex items-start gap-2.5">
                                            <span class="mt-1 w-4 h-4 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center flex-shrink-0 text-[10px] font-bold">
                                                ✓
                                            </span>
                                            <span class="leading-normal">{{ $missionText }}</span>
                                        </li>
                                    @endif
                                @endforeach
                            @else
                                <li class="flex items-start gap-2.5">
                                    <span class="mt-1 w-4 h-4 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center flex-shrink-0 text-[10px] font-bold">✓</span>
                                    <span>Menyelenggarakan peribadahan dan pembinaan rohani yang berakar pada Firman Allah.</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="mt-1 w-4 h-4 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center flex-shrink-0 text-[10px] font-bold">✓</span>
                                    <span>Mempererat persaudaraan dan solidaritas antar jemaat kelompok secara guyub dan inklusif.</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="mt-1 w-4 h-4 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center flex-shrink-0 text-[10px] font-bold">✓</span>
                                    <span>Mengembangkan karya pelayanan diakonia holistik bagi jemaat dan masyarakat luas.</span>
                                </li>
                            @endif
                        </ul>

                        <div class="mt-6 pt-4 border-t border-slate-100 text-xs font-semibold text-slate-700">
                            Komitmen Nyata Persekutuan
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- ================================================================= -->
        <!-- 5. POS PELAYANAN & JEMAAT KELOMPOK CABANG -->
        <!-- ================================================================= -->
        <section id="pos-pelayanan" class="py-20 lg:py-24 bg-white border-b border-amber-900/5">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="max-w-3xl mx-auto text-center mb-16">
                    <span class="inline-block text-xs font-bold uppercase tracking-widest text-emerald-800 bg-emerald-100/80 px-3 py-1 rounded-full mb-3">
                        Persekutuan Cabang
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                        Pos Pelayanan & Kelompok Jemaat
                    </h2>
                    <p class="mt-3 text-slate-600 text-sm sm:text-base">
                        GKSBS Filadelfia melayani jemaat induk dan pos-pos pelayanan kelompok: Candimas, Trimulyo, dan Margomulyo. Akses warta mingguan publik untuk setiap cabang di bawah ini.
                    </p>
                    <div class="w-20 h-1 bg-emerald-700 mx-auto mt-4 rounded-full"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @forelse($churches as $church)
                        <div class="bg-[#fdfbf7] rounded-2xl border border-amber-900/10 p-6 sm:p-7 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between group">
                            <div>
                                <!-- Church Header & Code -->
                                <div class="flex items-start justify-between gap-3 mb-4">
                                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center flex-shrink-0 group-hover:bg-amber-600 group-hover:text-white transition-colors duration-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 20V6a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v14"></path>
                                            <path d="M2 20h20"></path>
                                            <path d="M14 12v.01"></path>
                                            <path d="M10 12v.01"></path>
                                        </svg>
                                    </div>
                                    <span class="inline-block px-2.5 py-1 rounded-md text-[11px] font-bold tracking-wider uppercase bg-white border border-slate-200 text-slate-700 shadow-2xs">
                                        {{ $church->code }}
                                    </span>
                                </div>

                                <h3 class="text-lg sm:text-xl font-bold text-slate-900 mb-2 group-hover:text-amber-800 transition-colors">
                                    {{ $church->name }}
                                </h3>

                                <!-- Contact Info -->
                                <div class="space-y-2 text-xs sm:text-sm text-slate-600 mt-4 mb-6">
                                    @if($church->address)
                                        <div class="flex items-start gap-2.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-700 mt-0.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
                                                <circle cx="12" cy="10" r="3"></circle>
                                            </svg>
                                            <span class="line-clamp-2 leading-relaxed">{{ $church->address }}</span>
                                        </div>
                                    @endif

                                    @if($church->phone)
                                        <div class="flex items-center gap-2.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-700 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                            </svg>
                                            <span>{{ $church->phone }}</span>
                                        </div>
                                    @endif

                                    @if($church->email)
                                        <div class="flex items-center gap-2.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-700 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                            </svg>
                                            <span class="truncate">{{ $church->email }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Dedicated Button: Baca Warta Jemaat -->
                            <div class="pt-4 border-t border-amber-900/10">
                                <a href="{{ route('public.warta.index', $church->code) }}" 
                                   class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-semibold text-xs sm:text-sm shadow-xs transition duration-150">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path>
                                        <path d="M6 6h10"></path>
                                        <path d="M6 10h10"></path>
                                        <path d="M6 14h7"></path>
                                    </svg>
                                    <span>Baca Warta Jemaat</span>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-3 text-center py-12 bg-slate-50 rounded-2xl border border-slate-200">
                            <p class="text-slate-500">Belum ada data kelompok cabang yang terdaftar.</p>
                        </div>
                    @endforelse
                </div>

            </div>
        </section>

        <!-- ================================================================= -->
        <!-- 6. JADWAL IBADAH RUTIN INDUK -->
        <!-- ================================================================= -->
        <section id="jadwal" class="py-20 lg:py-24 bg-[#faf8f5] border-b border-amber-900/5">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="max-w-3xl mx-auto text-center mb-16">
                    <span class="inline-block text-xs font-bold uppercase tracking-widest text-amber-700 bg-amber-100/70 px-3 py-1 rounded-full mb-3">
                        Liturgi & Peribadahan
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                        Jadwal Ibadah Rutin Gereja Induk
                    </h2>
                    <p class="mt-3 text-slate-600 text-sm sm:text-base">
                        Mari bersekutu bersama memuji dan memuliakan nama Tuhan dalam ibadah raya minggu dan ibadah kategorial.
                    </p>
                    <div class="w-20 h-1 bg-amber-600 mx-auto mt-4 rounded-full"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @if(!empty($setting->worship_schedules) && is_array($setting->worship_schedules))
                        @foreach($setting->worship_schedules as $schedule)
                            <div class="bg-white rounded-2xl p-6 border border-amber-900/10 shadow-sm hover:shadow-md transition duration-150 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-900 border border-amber-200">
                                            {{ $schedule['day'] ?? 'Minggu' }}
                                        </span>
                                        <span class="text-xs font-semibold text-slate-500">
                                            {{ $schedule['time'] ?? '08:30 WIB' }}
                                        </span>
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-900 mb-2">
                                        {{ $schedule['name'] ?? 'Ibadah Raya' }}
                                    </h3>
                                </div>

                                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center gap-2 text-xs text-slate-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-700 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <span class="truncate">{{ $schedule['location'] ?? 'Gedung Gereja Utama' }}</span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <!-- Default Fallback Schedules -->
                        <div class="bg-white rounded-2xl p-6 border border-amber-900/10 shadow-sm">
                            <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-900 mb-4">Minggu</span>
                            <h3 class="text-lg font-bold text-slate-900 mb-1">Ibadah Raya Minggu Induk</h3>
                            <p class="text-xs text-slate-500 font-semibold mb-3">08:30 WIB</p>
                            <p class="text-xs text-slate-600">Gedung Gereja Utama</p>
                        </div>
                        <div class="bg-white rounded-2xl p-6 border border-amber-900/10 shadow-sm">
                            <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-900 mb-4">Minggu</span>
                            <h3 class="text-lg font-bold text-slate-900 mb-1">Kebaktian Anak / Sekolah Minggu</h3>
                            <p class="text-xs text-slate-500 font-semibold mb-3">08:30 WIB</p>
                            <p class="text-xs text-slate-600">Gedung Serbaguna</p>
                        </div>
                        <div class="bg-white rounded-2xl p-6 border border-amber-900/10 shadow-sm">
                            <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-900 mb-4">Sabtu</span>
                            <h3 class="text-lg font-bold text-slate-900 mb-1">Ibadah Pemuda & Remaja</h3>
                            <p class="text-xs text-slate-500 font-semibold mb-3">17:00 WIB</p>
                            <p class="text-xs text-slate-600">Ruang Pemuda</p>
                        </div>
                        <div class="bg-white rounded-2xl p-6 border border-amber-900/10 shadow-sm">
                            <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-900 mb-4">Rabu</span>
                            <h3 class="text-lg font-bold text-slate-900 mb-1">Persekutuan Doa & PA</h3>
                            <p class="text-xs text-slate-500 font-semibold mb-3">18:30 WIB</p>
                            <p class="text-xs text-slate-600">Gedung Gereja</p>
                        </div>
                    @endif
                </div>

                <!-- Callout Ibadah -->
                <div class="mt-12 bg-amber-900/5 rounded-2xl p-6 border border-amber-900/10 flex flex-col sm:flex-row items-center justify-between gap-4 max-w-4xl mx-auto">
                    <div class="flex items-center gap-3 text-left">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900">Persekutuan Terbuka untuk Seluruh Warga</h4>
                            <p class="text-xs text-slate-600">Ibadah diselenggarakan secara langsung dengan sukacita dan kehangatan persaudaraan sejati.</p>
                        </div>
                    </div>
                    <a href="#kontak" class="text-xs font-semibold text-amber-800 hover:text-amber-900 hover:underline flex items-center gap-1 flex-shrink-0">
                        Hubungi Sekretariat Ibadah &rarr;
                    </a>
                </div>

            </div>
        </section>

        <!-- ================================================================= -->
        <!-- 7. DUA PINTU AKSES PORTAL (CALL TO ACTION) -->
        <!-- ================================================================= -->
        <section class="py-16 bg-slate-900 text-white relative overflow-hidden">
            <div class="absolute inset-0 bg-radial from-amber-950/30 to-slate-950 pointer-events-none"></div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mx-auto text-center mb-12">
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">
                        Layanan Terpadu Warga Jemaat & Tata Kelola Majelis
                    </h2>
                    <p class="mt-2 text-slate-400 text-sm sm:text-base">
                        Pilih pintu akses sesuai peruntukan untuk masuk ke sistem informasi gereja.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                    
                    <!-- Pintu 1: Portal Jemaat -->
                    <div class="bg-slate-800/80 rounded-2xl p-7 sm:p-8 border border-slate-700 shadow-xl flex flex-col justify-between hover:border-amber-500/50 transition">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-amber-500/20 text-amber-300 flex items-center justify-center mb-5 border border-amber-500/30">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Portal Mandiri Jemaat</h3>
                            <p class="text-slate-300 text-sm leading-relaxed mb-6">
                                Dikhususkan bagi seluruh warga jemaat terdaftar untuk memeriksa profil data keluarga, riwayat sakramen (baptis, sidi, perkawinan), jadwal keterlibatan pelayanan, serta membaca warta gereja secara personal.
                            </p>
                        </div>
                        <a href="{{ route('portal.login') }}" class="inline-flex items-center justify-center gap-2 w-full py-3 px-5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-semibold text-sm transition">
                            <span>Masuk ke Portal Jemaat</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                        </a>
                    </div>

                    <!-- Pintu 2: Area Majelis & Pengurus -->
                    <div class="bg-slate-800/80 rounded-2xl p-7 sm:p-8 border border-slate-700 shadow-xl flex flex-col justify-between hover:border-amber-500/50 transition">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-slate-700 text-amber-300 flex items-center justify-center mb-5 border border-slate-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Area Majelis & Administrasi</h3>
                            <p class="text-slate-300 text-sm leading-relaxed mb-6">
                                Pintu masuk resmi bagi Pendeta, Penatua, Diaken, Badan Pengurus Kelompok, dan Staf Sekretariat/Keuangan untuk tata kelola data jemaat, perbendaharaan, penerbitan warta jemaat, dan CMS website.
                            </p>
                        </div>
                        <a href="{{ url('/admin/login') }}" class="inline-flex items-center justify-center gap-2 w-full py-3 px-5 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-semibold text-sm transition">
                            <span>Masuk Area Majelis</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                        </a>
                    </div>

                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- 8. KONTAK SEKRETARIAT & PETA LOKASI -->
        <!-- ================================================================= -->
        <section id="kontak" class="py-20 lg:py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="max-w-3xl mx-auto text-center mb-16">
                    <span class="inline-block text-xs font-bold uppercase tracking-widest text-amber-700 bg-amber-100/70 px-3 py-1 rounded-full mb-3">
                        Layanan Sekretariat
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                        Kontak & Lokasi Gereja
                    </h2>
                    <p class="mt-3 text-slate-600 text-sm sm:text-base">
                        Sekretariat Majelis Jemaat siap melayani kebutuhan informasi peribadahan, pastoral, dan administrasi jemaat.
                    </p>
                    <div class="w-20 h-1 bg-amber-600 mx-auto mt-4 rounded-full"></div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 max-w-6xl mx-auto">
                    
                    <!-- Informasi Kontak Resmi -->
                    <div class="lg:col-span-6 bg-[#fdfbf7] p-8 rounded-2xl border border-amber-900/10 space-y-6">
                        <h3 class="text-xl font-bold text-slate-900 border-b border-amber-900/10 pb-4">
                            Sekretariat Majelis Jemaat Induk
                        </h3>

                        <div class="space-y-4 text-sm sm:text-base text-slate-700">
                            <!-- Alamat -->
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center flex-shrink-0 mt-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Alamat Gereja</span>
                                    <p class="mt-0.5 font-medium leading-relaxed">
                                        {{ $setting->contact_address ?? 'Jl. Gereja Filadelfia No. 01, Kel. Candimas, Kec. Natar, Lampung Selatan' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Telepon -->
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center flex-shrink-0 mt-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Telepon & WhatsApp</span>
                                    <p class="mt-0.5 font-medium">
                                        {{ $setting->contact_phone ?? '0812-7200-1234' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center flex-shrink-0 mt-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                    </svg>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Email Resmi</span>
                                    <p class="mt-0.5 font-medium">
                                        {{ $setting->contact_email ?? 'sekretariat@gksbs-filadelfia.org' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Media Sosial Resmi -->
                        @if(!empty($setting->social_links) && is_array($setting->social_links))
                            <div class="pt-4 border-t border-amber-900/10">
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Saluran Informasi Resmi</span>
                                <div class="flex flex-wrap gap-2.5">
                                    @foreach($setting->social_links as $platform => $link)
                                        @if(filled($link))
                                            <a href="{{ $link }}" target="_blank" rel="noopener noreferrer" 
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-700 hover:text-amber-800 hover:border-amber-400 shadow-2xs transition">
                                                <span class="capitalize">{{ $platform }}</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Peta Lokasi & Panduan Kedatangan -->
                    <div class="lg:col-span-6 bg-[#fdfbf7] p-8 rounded-2xl border border-amber-900/10 flex flex-col justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 border-b border-amber-900/10 pb-4 mb-5">
                                Panduan Kedatangan & Peta Lokasi
                            </h3>
                            <p class="text-slate-600 text-sm leading-relaxed mb-6">
                                Gedung gereja terletak di lokasi strategis yang mudah dijangkau oleh warga jemaat maupun simpatisan. Tersedia area parkir kendaraan serta fasilitas ibadah yang ramah bagi anak dan lansia.
                            </p>
                            
                            <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-2xs mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 14 14"></polyline>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900">Jam Layanan Kantor Gereja</h4>
                                        <p class="text-xs text-slate-500 mt-0.5">Selasa — Sabtu: 08.00 — 15.00 WIB | Minggu: Selesai Ibadah</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            @if($setting->contact_maps_url)
                                <a href="{{ $setting->contact_maps_url }}" target="_blank" rel="noopener noreferrer" 
                                   class="inline-flex items-center justify-center gap-2 w-full py-3.5 px-5 rounded-xl bg-amber-700 hover:bg-amber-800 text-white font-semibold text-sm shadow-sm transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"></polygon>
                                        <line x1="9" y1="3" x2="9" y2="18"></line>
                                        <line x1="15" y1="6" x2="15" y2="21"></line>
                                    </svg>
                                    <span>Buka Petunjuk Arah di Google Maps</span>
                                </a>
                            @else
                                <a href="https://maps.google.com/?q=GKSBS+Filadelfia" target="_blank" rel="noopener noreferrer" 
                                   class="inline-flex items-center justify-center gap-2 w-full py-3.5 px-5 rounded-xl bg-amber-700 hover:bg-amber-800 text-white font-semibold text-sm shadow-sm transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"></polygon>
                                        <line x1="9" y1="3" x2="9" y2="18"></line>
                                        <line x1="15" y1="6" x2="15" y2="21"></line>
                                    </svg>
                                    <span>Buka Petunjuk Arah di Google Maps</span>
                                </a>
                            @endif
                        </div>
                    </div>

                </div>

            </div>
        </section>

    </main>

    <!-- ================================================================= -->
    <!-- 9. FOOTER RESMI -->
    <!-- ================================================================= -->
    <footer class="bg-slate-950 text-slate-400 text-sm border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
                
                <!-- Identitas Sinodal & Gereja -->
                <div class="md:col-span-6 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-amber-700 flex items-center justify-center text-white font-bold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <line x1="12" y1="2" x2="12" y2="22"></line>
                                <line x1="6" y1="8" x2="18" y2="8"></line>
                            </svg>
                        </div>
                        <div>
                            <span class="text-base font-bold text-white block leading-tight">
                                {{ $setting->hero_title ?? 'GKSBS Filadelfia' }}
                            </span>
                            <span class="text-xs text-amber-400 font-medium">
                                {{ $setting->hero_subtitle ?? 'Gereja Kristen Sumatera Bagian Selatan' }}
                            </span>
                        </div>
                    </div>

                    <p class="text-xs sm:text-sm text-slate-400 max-w-md leading-relaxed">
                        Anggota Persekutuan Gereja-gereja di Indonesia (PGI) — Sinode Gereja Kristen Sumatera Bagian Selatan (GKSBS). Terpanggil mewujudkan persekutuan yang mandiri, misioner, dan berakar dalam kasih Kristus.
                    </p>

                    <div class="text-xs text-slate-400 flex items-center gap-2 pt-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Klasis Tulang Bawang — Wilayah Pelayanan Terpadu</span>
                    </div>
                </div>

                <!-- Tautan Pintas Halaman -->
                <div class="md:col-span-3 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300">Navigasi Halaman</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="#beranda" class="hover:text-amber-400 transition">Beranda Utama</a></li>
                        <li><a href="#sambutan" class="hover:text-amber-400 transition">Sambutan Majelis</a></li>
                        <li><a href="#profil" class="hover:text-amber-400 transition">Profil, Visi & Misi</a></li>
                        <li><a href="#pos-pelayanan" class="hover:text-amber-400 transition">Pos Pelayanan Cabang</a></li>
                        <li><a href="#jadwal" class="hover:text-amber-400 transition">Jadwal Ibadah Induk</a></li>
                        <li><a href="#kontak" class="hover:text-amber-400 transition">Kontak Sekretariat</a></li>
                    </ul>
                </div>

                <!-- Akses Sistem -->
                <div class="md:col-span-3 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300">Pintu Masuk Sistem</h4>
                    <ul class="space-y-2.5 text-xs">
                        <li>
                            <a href="{{ route('portal.login') }}" class="inline-flex items-center gap-1.5 text-slate-300 hover:text-amber-400 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                <span>Portal Mandiri Jemaat</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ url('/admin/login') }}" class="inline-flex items-center gap-1.5 text-slate-300 hover:text-amber-400 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                <span>Area Majelis & Presbiter</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('public.warta.index') }}" class="inline-flex items-center gap-1.5 text-emerald-400 hover:text-emerald-300 transition">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                <span>Warta Jemaat Publik</span>
                            </a>
                        </li>
                    </ul>
                </div>

            </div>

            <div class="mt-12 pt-8 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
                <p>
                    &copy; {{ date('Y') }} {{ $setting->hero_title ?? 'GKSBS Filadelfia' }}. Pelayanan sukarela untuk kemuliaan nama Tuhan Yesus Kristus.
                </p>
                <p class="text-slate-400">
                    Soli Deo Gloria
                </p>
            </div>
        </div>
    </footer>

    <!-- JavaScript for Interactive Mobile Navigation -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            const iconOpen = document.getElementById('icon-menu-open');
            const iconClose = document.getElementById('icon-menu-close');
            const navLinks = document.querySelectorAll('.mobile-nav-link');

            if (menuButton && mobileMenu) {
                menuButton.addEventListener('click', function () {
                    const isExpanded = !mobileMenu.classList.contains('hidden');
                    if (isExpanded) {
                        mobileMenu.classList.add('hidden');
                        iconOpen.classList.remove('hidden');
                        iconClose.classList.add('hidden');
                    } else {
                        mobileMenu.classList.remove('hidden');
                        iconOpen.classList.add('hidden');
                        iconClose.classList.remove('hidden');
                    }
                });

                navLinks.forEach(link => {
                    link.addEventListener('click', function () {
                        mobileMenu.classList.add('hidden');
                        iconOpen.classList.remove('hidden');
                        iconClose.classList.add('hidden');
                    });
                });
            }
        });
    </script>
</body>
</html>
