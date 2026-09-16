<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Gereja — Sistem Informasi & Administrasi Jemaat</title>
    <meta name="description" content="Sistem Informasi & Administrasi Jemaat terpadu. Akses portal mandiri jemaat, baca warta mingguan, dan panel kelola majelis gereja.">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN with configuration -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
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
        .bg-grid-pattern {
            background-image: radial-gradient(rgba(217, 119, 6, 0.08) 1px, transparent 1px);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-brand-500 selection:text-white min-h-screen flex flex-col">

    <!-- Navigation Bar -->
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-200/80 transition duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Brand & Logo -->
                <a href="{{ url('/') }}" class="flex items-center gap-3.5 group">
                    <div class="h-11 w-11 rounded-2xl bg-gradient-to-tr from-brand-600 via-brand-500 to-amber-400 flex items-center justify-center text-white shadow-md shadow-brand-500/20 group-hover:scale-105 transition-transform duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 20V6a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v14"></path>
                            <path d="M2 20h20"></path>
                            <path d="M14 12v.01"></path>
                            <path d="M10 12v.01"></path>
                            <path d="M10 16v.01"></path>
                            <path d="M14 16v.01"></path>
                            <path d="M12 2v4"></path>
                            <path d="M10 4h4"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-extrabold text-slate-900 tracking-tight">Portal Gereja</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-100 text-brand-800 border border-brand-200/60">
                                Sistem Resmi
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium tracking-normal hidden sm:block">Sistem Informasi & Administrasi Jemaat</p>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center gap-1">
                    <a href="#layanan" class="px-3.5 py-2 text-sm font-medium text-slate-600 hover:text-brand-700 hover:bg-slate-100/70 rounded-xl transition">
                        Pintu Layanan
                    </a>
                    <a href="#gereja" class="px-3.5 py-2 text-sm font-medium text-slate-600 hover:text-brand-700 hover:bg-slate-100/70 rounded-xl transition">
                        Daftar Gereja
                    </a>
                    <a href="#keunggulan" class="px-3.5 py-2 text-sm font-medium text-slate-600 hover:text-brand-700 hover:bg-slate-100/70 rounded-xl transition">
                        Fitur & Modul
                    </a>
                    <a href="{{ route('public.warta.index') }}" class="px-3.5 py-2 text-sm font-medium text-emerald-700 hover:text-emerald-800 hover:bg-emerald-50 rounded-xl transition flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Warta Publik
                    </a>
                </nav>

                <!-- Quick Action Buttons -->
                <div class="flex items-center gap-2 sm:gap-3">
                    @auth
                        <div class="hidden sm:flex flex-col items-end mr-1">
                            <span class="text-xs font-bold text-slate-900 leading-tight">{{ auth()->user()->name }}</span>
                            <span class="text-[11px] text-slate-500 leading-tight">{{ auth()->user()->email }}</span>
                        </div>
                        <a href="{{ auth()->user()->role ? url('/admin') : route('portal.profile') }}" 
                           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs sm:text-sm shadow-sm hover:shadow transition duration-150">
                            <span>Buka Dashboard</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                        </a>
                    @else
                        <a href="{{ route('portal.login') }}" 
                           class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-slate-700 hover:text-slate-950 font-semibold text-xs sm:text-sm hover:bg-slate-100 transition">
                            Masuk Jemaat
                        </a>
                        <a href="{{ url('/admin/login') }}" 
                           class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs sm:text-sm shadow-sm transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            <span>Panel Admin</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Body Container -->
    <main class="flex-1">

        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-b from-brand-50/70 via-slate-50 to-slate-50 pt-12 pb-20 sm:pt-20 sm:pb-28 border-b border-slate-200/60 bg-grid-pattern">
            <!-- Decorative atmospheric glows -->
            <div class="pointer-events-none absolute -top-24 left-1/2 -translate-x-1/2 w-96 sm:w-[680px] h-96 sm:h-[480px] bg-gradient-to-tr from-brand-300/30 via-amber-200/20 to-emerald-200/20 blur-3xl rounded-full opacity-70"></div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <!-- Warm Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white border border-brand-200 shadow-sm text-brand-800 text-xs sm:text-sm font-semibold mb-6 animate-fade-in">
                    <span class="flex h-2 w-2 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-600"></span>
                    </span>
                    <span>Pelayanan Digital Terintegrasi Berbasis Kasih & Ketertiban</span>
                </div>

                <!-- Headline -->
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.18] max-w-4xl mx-auto">
                    Portal Informasi & Administrasi <span class="bg-gradient-to-r from-brand-700 via-brand-600 to-amber-600 bg-clip-text text-transparent">Jemaat Gereja</span>
                </h1>

                <!-- Subtitle / Tagline -->
                <p class="mt-6 text-base sm:text-xl text-slate-600 max-w-2xl mx-auto font-normal leading-relaxed">
                    Mewujudkan persekutuan yang transparan, tertata, dan terintegrasi. Menghubungkan seluruh warga jemaat, keluarga, dan majelis dalam satu pelayanan yang hangat dan berkeadaban.
                </p>

                <!-- Quick Action Buttons in Hero -->
                <div class="mt-9 flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 max-w-md mx-auto">
                    <a href="{{ auth()->check() ? route('portal.profile') : route('portal.login') }}" 
                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm sm:text-base shadow-lg shadow-brand-600/25 hover:shadow-brand-600/35 transition-all duration-200 group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-brand-200 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <span>Masuk Portal Jemaat</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                    </a>

                    <a href="{{ route('public.warta.index') }}" 
                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-2xl bg-white hover:bg-slate-50 text-slate-800 border border-slate-300/80 font-bold text-sm sm:text-base shadow-sm hover:border-slate-400 transition-all duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path><path d="M6 6h10"></path><path d="M6 10h10"></path><path d="M6 14h7"></path></svg>
                        <span>Warta Jemaat Publik</span>
                    </a>
                </div>

                <!-- Trust / Statistics mini ticker -->
                <div class="mt-14 pt-10 border-t border-slate-200/70 max-w-3xl mx-auto grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ $churches->count() }}</div>
                        <div class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">Jemaat Terdaftar</div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-brand-700">100%</div>
                        <div class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">Mandiri & Terpadu</div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-emerald-700">Publik</div>
                        <div class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">Warta Mingguan Bebas Akses</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 3 Pintu Akses Utama (CTA Cards) -->
        <section id="layanan" class="py-16 sm:py-24 bg-white relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-14">
                    <span class="text-xs font-bold uppercase tracking-wider text-brand-700 bg-brand-50 px-3 py-1 rounded-lg border border-brand-200/60">
                        Gerbang Layanan
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mt-3">
                        Tiga Pintu Akses Utama
                    </h2>
                    <p class="mt-3 text-slate-600 text-sm sm:text-base">
                        Pilih portal sesuai peran dan kebutuhan Anda dalam persekutuan dan administrasi gereja.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
                    
                    <!-- Card 1: Portal Mandiri Jemaat -->
                    <div class="rounded-3xl border-2 border-brand-200/80 bg-gradient-to-b from-white via-amber-50/20 to-brand-50/40 p-8 shadow-sm hover:shadow-xl hover:border-brand-400 transition-all duration-300 flex flex-col justify-between relative group">
                        <div class="absolute -top-3.5 left-8">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-brand-500 text-white shadow-sm">
                                Khusus Jemaat
                            </span>
                        </div>

                        <div>
                            <div class="h-14 w-14 rounded-2xl bg-brand-100 text-brand-700 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </div>

                            <h3 class="text-xl font-bold text-slate-900 mb-2">Portal Mandiri Jemaat</h3>
                            <p class="text-slate-600 text-sm leading-relaxed mb-6">
                                Akses mandiri untuk setiap warga jemaat guna memantau data diri, keluarga, riwayat sakramen suci, serta jadwal pelayanan ibadah.
                            </p>

                            <ul class="space-y-2.5 text-xs sm:text-sm text-slate-600 mb-8 border-t border-brand-200/60 pt-5">
                                <li class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-brand-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                    <span>Profil jemaat & data kartu keluarga</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-brand-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                    <span>Catatan Baptis, Sidi, dan Pernikahan</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-brand-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                    <span>Jadwal tugas pelayan ibadah & warta</span>
                                </li>
                            </ul>
                        </div>

                        <div>
                            @auth
                                <a href="{{ route('portal.profile') }}" 
                                   class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm shadow transition duration-150">
                                    <span>Buka Profil Jemaat</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                                </a>
                            @else
                                <a href="{{ route('portal.login') }}" 
                                   class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm shadow transition duration-150">
                                    <span>Masuk ke Portal Jemaat</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                                </a>
                            @endauth
                        </div>
                    </div>

                    <!-- Card 2: Warta Jemaat Publik -->
                    <div class="rounded-3xl border-2 border-emerald-200/80 bg-gradient-to-b from-white via-emerald-50/20 to-emerald-50/40 p-8 shadow-sm hover:shadow-xl hover:border-emerald-400 transition-all duration-300 flex flex-col justify-between relative group">
                        <div class="absolute -top-3.5 left-8">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-600 text-white shadow-sm">
                                Terbuka untuk Umum
                            </span>
                        </div>

                        <div>
                            <div class="h-14 w-14 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path>
                                    <path d="M6 6h10"></path>
                                    <path d="M6 10h10"></path>
                                    <path d="M6 14h8"></path>
                                </svg>
                            </div>

                            <h3 class="text-xl font-bold text-slate-900 mb-2">Warta Jemaat Publik</h3>
                            <p class="text-slate-600 text-sm leading-relaxed mb-6">
                                Terbitan warta mingguan resmi seluruh gereja terdaftar. Dapat diakses bebas oleh jemaat, keluarga, dan simpatisan tanpa perlu login.
                            </p>

                            <ul class="space-y-2.5 text-xs sm:text-sm text-slate-600 mb-8 border-t border-emerald-200/60 pt-5">
                                <li class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                    <span>Liturgi, bacaan Alkitab, dan renungan</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                    <span>Jadwal ibadah raya, kategorial & doa</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                    <span>Pokok doa syafaat & pengumuman resmi</span>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <a href="{{ route('public.warta.index') }}" 
                               class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow transition duration-150">
                                <span>Baca Warta Jemaat</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Card 3: Panel Administrasi Majelis / Staff -->
                    <div class="rounded-3xl border-2 border-slate-300/80 bg-gradient-to-b from-white via-slate-50/50 to-slate-100/50 p-8 shadow-sm hover:shadow-xl hover:border-slate-500 transition-all duration-300 flex flex-col justify-between relative group">
                        <div class="absolute -top-3.5 left-8">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-900 text-white shadow-sm">
                                Majelis & Administrator
                            </span>
                        </div>

                        <div>
                            <div class="h-14 w-14 rounded-2xl bg-slate-100 text-slate-800 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                                    <path d="M3 9h18"></path>
                                    <path d="M9 21V9"></path>
                                </svg>
                            </div>

                            <h3 class="text-xl font-bold text-slate-900 mb-2">Panel Administrasi Majelis</h3>
                            <p class="text-slate-600 text-sm leading-relaxed mb-6">
                                Pusat tata kelola jemaat bagi Majelis, Penatua, Diaken, Bendahara, dan Sekretariat untuk administrasi harian dan laporan berkala.
                            </p>

                            <ul class="space-y-2.5 text-xs sm:text-sm text-slate-600 mb-8 border-t border-slate-200 pt-5">
                                <li class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-slate-700 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                    <span>Registrasi, sensus & mutasi jemaat</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-slate-700 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                    <span>Penerbitan akta & sertifikat sakramen</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-slate-700 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                    <span>Buku kas, pos keuangan & ekspor laporan</span>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <a href="{{ url('/admin/login') }}" 
                               class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm shadow transition duration-150">
                                <span>Masuk Panel Majelis</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- Section Daftar Gereja Terdaftar -->
        <section id="gereja" class="py-16 sm:py-24 bg-slate-50 border-t border-slate-200/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row md:items-end justify-between mb-12">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-brand-700 bg-brand-100/60 px-3 py-1 rounded-lg border border-brand-200/50">
                            Direktori Terdaftar
                        </span>
                        <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mt-3">
                            Jemaat & Pos Kebaktian
                        </h2>
                        <p class="mt-2 text-slate-600 text-sm sm:text-base">
                            Daftar gereja lokal yang telah terhubung dan mengelola pelayanan secara digital.
                        </p>
                    </div>

                    <div class="mt-4 md:mt-0 flex items-center gap-2 text-xs sm:text-sm font-semibold text-slate-500">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span>{{ $churches->count() }} Gereja Aktif Terintegrasi</span>
                    </div>
                </div>

                @if($churches->isEmpty())
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center max-w-lg mx-auto">
                        <div class="w-16 h-16 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-4 text-2xl">
                            ⛪
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">Belum Ada Gereja Terdaftar</h3>
                        <p class="text-sm text-slate-500 mt-1">Data gereja akan segera ditampilkan setelah didaftarkan oleh administrator.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($churches as $church)
                            <div class="rounded-3xl bg-white border border-slate-200 p-6 sm:p-7 shadow-sm hover:shadow-md hover:border-brand-300 transition duration-200 flex flex-col justify-between group">
                                <div>
                                    <!-- Header Card: Logo / Icon & Synod -->
                                    <div class="flex items-start gap-4 mb-4">
                                        @if($church->logo_url)
                                            <img src="{{ $church->logo_url }}" alt="{{ $church->name }}" class="w-12 h-12 rounded-2xl object-cover border border-slate-200 shadow-sm flex-shrink-0">
                                        @else
                                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-brand-500 to-amber-600 text-white flex items-center justify-center font-bold text-xl shadow-sm flex-shrink-0">
                                                ⛪
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <h3 class="text-base sm:text-lg font-extrabold text-slate-900 group-hover:text-brand-700 transition leading-snug">
                                                {{ $church->name }}
                                            </h3>
                                            @if($church->synod)
                                                <p class="text-[11px] sm:text-xs text-brand-800 font-medium mt-0.5 line-clamp-1">
                                                    {{ $church->synod }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Address & Contacts -->
                                    <div class="space-y-2.5 text-xs sm:text-sm text-slate-600 my-5 pt-4 border-t border-slate-100">
                                        @if($church->address)
                                            <div class="flex items-start gap-2.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                                <span class="leading-relaxed">{{ $church->address }}</span>
                                            </div>
                                        @endif

                                        @if($church->phone)
                                            <div class="flex items-center gap-2.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                                <a href="tel:{{ $church->phone }}" class="hover:text-brand-700 font-medium underline-offset-2 hover:underline">
                                                    {{ $church->phone }}
                                                </a>
                                            </div>
                                        @endif

                                        @if($church->email)
                                            <div class="flex items-center gap-2.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                                                <span class="truncate">{{ $church->email }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                                    <a href="{{ route('public.warta.index', ['church' => $church->code]) }}" 
                                       class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-brand-50 hover:bg-brand-100 text-brand-800 font-bold text-xs sm:text-sm border border-brand-200/70 transition duration-150">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path><path d="M6 6h10"></path><path d="M6 10h10"></path><path d="M6 14h7"></path></svg>
                                        <span>Buka Warta</span>
                                    </a>

                                    @if($church->website)
                                        <a href="{{ $church->website }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center justify-center p-2.5 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 border border-slate-200 transition"
                                           title="Kunjungi Website Resmi">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" x2="22" y1="12" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <!-- Section Fitur & Keunggulan Modul -->
        <section id="keunggulan" class="py-16 sm:py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-3 py-1 rounded-lg border border-emerald-200/60">
                        Ekosistem Lengkap
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mt-3">
                        Dirancang Khusus untuk Kebutuhan Gerejawi
                    </h2>
                    <p class="mt-3 text-slate-600 text-sm sm:text-base">
                        Menggabungkan ketertiban administrasi gerejawi dengan kemudahan akses digital yang aman dan transparan.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Feature 1 -->
                    <div class="rounded-2xl p-6 bg-slate-50/80 border border-slate-200/80 hover:bg-white hover:border-brand-300 hover:shadow-md transition duration-200">
                        <div class="w-12 h-12 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center mb-5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                        <h4 class="font-bold text-base text-slate-900 mb-2">Basis Data Jemaat</h4>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            Pencatatan kepala keluarga, relasi anggota, data sensus, tanggal lahir, dan riwayat mutasi warga secara terstruktur.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="rounded-2xl p-6 bg-slate-50/80 border border-slate-200/80 hover:bg-white hover:border-brand-300 hover:shadow-md transition duration-200">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mb-5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        </div>
                        <h4 class="font-bold text-base text-slate-900 mb-2">Administrasi Sakramen</h4>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            Pencatatan dan penerbitan dokumen resmi: Akta Lahir, Dokumen Baptis Anak & Dewasa, Peneguhan Sidi, Akta Nikah, dan Surat Kematian.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="rounded-2xl p-6 bg-slate-50/80 border border-slate-200/80 hover:bg-white hover:border-brand-300 hover:shadow-md transition duration-200">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </div>
                        <h4 class="font-bold text-base text-slate-900 mb-2">Akuntabilitas Kas</h4>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            Buku kas gereja, persembahan mingguan/perpuluhan, pos anggaran komisi, dan ekspor laporan keuangan rapat majelis ke PDF & Excel.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="rounded-2xl p-6 bg-slate-50/80 border border-slate-200/80 hover:bg-white hover:border-brand-300 hover:shadow-md transition duration-200">
                        <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center mb-5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                        <h4 class="font-bold text-base text-slate-900 mb-2">Kalender & Penugasan</h4>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            Jadwal ibadah raya, ibadah sektor, koordinasi liturgis, penugasan majelis bertugas, dan pencatatan presensi kehadiran jemaat.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Ayat Alkitab / Nilai Kebersamaan Banner -->
        <section class="py-14 bg-gradient-to-r from-brand-900 via-slate-900 to-brand-950 text-white relative overflow-hidden">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <div class="inline-block mb-3 text-brand-300 text-2xl font-serif">“</div>
                <blockquote class="text-lg sm:text-2xl font-light italic leading-relaxed text-slate-100">
                    Lakukanlah segala pekerjaanmu dalam kasih, dan biarlah segala sesuatu berlangsung dengan tertib dan teratur.
                </blockquote>
                <p class="mt-4 text-xs sm:text-sm font-semibold text-brand-300 tracking-wide uppercase">
                    1 Korintus 16:14 & 14:40
                </p>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-slate-950 text-slate-400 text-xs sm:text-sm border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-10">
                <!-- Col 1: Brand info -->
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="h-9 w-9 rounded-xl bg-brand-600 flex items-center justify-center text-white font-bold text-lg">
                            <span>⛪</span>
                        </div>
                        <span class="text-base font-bold text-white tracking-tight">Portal Gereja</span>
                    </div>
                    <p class="text-slate-400 text-xs sm:text-sm leading-relaxed max-w-md">
                        Sistem Informasi & Administrasi Jemaat terpadu yang membantu gereja-gereja lokal melayani warga jemaat dengan tertib, transparan, dan berkeadaban dalam kasih Kristus.
                    </p>
                    <p class="mt-4 text-[11px] text-slate-500">
                        Didukung oleh Sinode & Klasis terkait.
                    </p>
                </div>

                <!-- Col 2: Navigasi Cepat -->
                <div>
                    <h5 class="text-white font-bold text-xs uppercase tracking-wider mb-3">Tautan Cepat</h5>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ url('/') }}" class="hover:text-brand-400 transition">Beranda Utama</a></li>
                        <li><a href="{{ route('public.warta.index') }}" class="hover:text-brand-400 transition">Warta Jemaat Publik</a></li>
                        <li><a href="{{ route('portal.login') }}" class="hover:text-brand-400 transition">Portal Mandiri Jemaat</a></li>
                        <li><a href="{{ url('/admin/login') }}" class="hover:text-brand-400 transition">Panel Administrasi Majelis</a></li>
                    </ul>
                </div>

                <!-- Col 3: Layanan & Informasi -->
                <div>
                    <h5 class="text-white font-bold text-xs uppercase tracking-wider mb-3">Administrasi</h5>
                    <ul class="space-y-2 text-xs">
                        <li><span class="text-slate-400">Data Sensus Warga & Keluarga</span></li>
                        <li><span class="text-slate-400">Penerbitan Dokumen Sakramen</span></li>
                        <li><span class="text-slate-400">Laporan Keuangan Rapat Majelis</span></li>
                        <li><span class="text-slate-400">Jadwal Ibadah & Pelayanan</span></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom bar -->
            <div class="pt-8 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <p>&copy; {{ date('Y') }} Portal Gereja — Sistem Informasi & Administrasi Jemaat. Hak Cipta Dilindungi.</p>
                <div class="flex items-center gap-4">
                    <span>Versi Produksi</span>
                    <span>•</span>
                    <a href="#layanan" class="hover:text-slate-300 transition">Kembali ke Atas ↑</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
