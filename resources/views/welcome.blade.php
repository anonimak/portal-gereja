@php
    $setting = $setting ?? \App\Models\LandingSetting::current();
    $churches = $churches ?? \App\Models\Church::all();
    $churchName = $setting->hero_title ?? 'GKSBS Filadelfia';
    $heroBg = $setting->hero_banner_url ?: 'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?q=80&w=1920&auto=format&fit=crop';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $churchName }} — Gereja Kristen Sumatera Bagian Selatan</title>
    <meta name="description" content="Website Resmi {{ $churchName }}. {{ $setting->hero_tagline ?? 'Gereja yang Terbuka, Oikumenis, dan Berakar dalam Kasih Kristus' }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Fonts: Caudex (Headings) & Raleway (Body/UI) ala sonshipbayridge.church -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caudex:ital,wght@0,400;0,700;1,400&family=Raleway:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        heading: ['"Caudex"', 'Georgia', 'serif'],
                        sans: ['"Raleway"', 'system-ui', 'sans-serif'],
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
                            cream: '#f4f6f5',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Raleway', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            background-color: #fbfcfd;
            margin: 0;
            padding: 0;
        }
        .font-heading {
            font-family: 'Caudex', Georgia, 'Times New Roman', serif;
        }
        
        /* Sonship Bay Ridge Parallax Sections */
        .parallax-window {
            position: relative;
            background-attachment: fixed;
            background-position: center center;
            background-repeat: no-repeat;
            background-size: cover;
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .parallax-window.viewport-height {
            min-height: 100vh;
        }
        
        /* Mobile fallback for background-attachment */
        @media (max-width: 1024px) {
            .parallax-window {
                background-attachment: scroll !important;
            }
        }

        /* Fixed Header scroll transitions */
        #church-navbar {
            transition: background-color 0.35s ease, padding 0.35s ease, box-shadow 0.35s ease, backdrop-filter 0.35s ease;
        }
        #church-navbar.scrolled {
            background-color: rgba(9, 37, 28, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        #church-navbar.not-scrolled {
            background-color: transparent;
            padding-top: 1.75rem;
            padding-bottom: 1.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        /* Classic Sonship Outline Button */
        .sonship-btn-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.85rem 1.85rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            border-radius: 9999px;
            border: 1.5px solid rgba(255, 255, 255, 0.7);
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(4px);
            transition: all 0.25s ease;
        }
        .sonship-btn-outline:hover {
            border-color: #22c55e;
            background-color: #16a34a;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(22, 163, 74, 0.3);
        }

        .sonship-btn-solid-blue {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.85rem 1.85rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            border-radius: 9999px;
            border: 1.5px solid #0284c7;
            background-color: #0369a1;
            color: #ffffff;
            transition: all 0.25s ease;
        }
        .sonship-btn-solid-blue:hover {
            background-color: #0284c7;
            border-color: #38bdf8;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.35);
        }

        .sonship-btn-dark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.85rem 1.85rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            border-radius: 9999px;
            border: 1.5px solid #0f3d2e;
            background-color: #0f3d2e;
            color: #ffffff;
            transition: all 0.25s ease;
        }
        .sonship-btn-dark:hover {
            background-color: #16a34a;
            border-color: #16a34a;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(22, 163, 74, 0.25);
        }

        /* Subtle scroll pulse */
        @keyframes scrollBounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(8px); }
            60% { transform: translateY(4px); }
        }
        .scroll-indicator {
            animation: scrollBounce 2.5s infinite;
        }
    </style>
</head>
<body class="antialiased selection:bg-gksbs-ocean selection:text-white">

    <!-- ================================================================= -->
    <!-- 1. FIXED HEADER & NAVIGATION (Maranatha Theme Style) -->
    <!-- ================================================================= -->
    <header id="church-navbar" class="fixed top-0 left-0 right-0 z-50 not-scrolled">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                
                <!-- GKSBS Authentic Logo & Church Identity -->
                <a href="#beranda" class="flex items-center gap-3.5 group text-white">
                    <!-- Vector Logo Sinode GKSBS: 7 Daun Cengkeh Hijau + 4 Garis Biru Sumbagsel -->
                    <div class="h-12 w-12 flex-shrink-0 flex items-center justify-center p-1 rounded-xl bg-black/25 backdrop-blur-xs border border-white/20 group-hover:border-gksbs-leaf-light transition">
                        <svg viewBox="0 0 100 100" class="w-10 h-10" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- 7 Daun Cengkeh (Warna Hijau Tua & Hijau Muda) -->
                            <path d="M50 8 C46 22 42 34 50 48 C58 34 54 22 50 8Z" fill="#16a34a"/>
                            <path d="M38 16 C30 27 30 38 43 47 C43 33 42 24 38 16Z" fill="#22c55e"/>
                            <path d="M62 16 C70 27 70 38 57 47 C57 33 58 24 62 16Z" fill="#22c55e"/>
                            <path d="M26 28 C16 38 20 50 36 52 C34 38 31 31 26 28Z" fill="#15803d"/>
                            <path d="M74 28 C84 38 80 50 64 52 C66 38 69 31 74 28Z" fill="#15803d"/>
                            <path d="M18 44 C8 54 14 65 31 60 C26 48 22 44 18 44Z" fill="#166534"/>
                            <path d="M82 44 C92 54 86 65 69 60 C74 48 78 44 82 44Z" fill="#166534"/>
                            <circle cx="50" cy="50" r="3.5" fill="#ffffff"/>
                            <!-- 4 Garis Biru Identitas 4 Propinsi Sumbagsel (Lampung, Sumsel, Bengkulu, Jambi) -->
                            <path d="M20 68 Q50 64 80 68" stroke="#38bdf8" stroke-width="2.5" stroke-linecap="round"/>
                            <path d="M16 75 Q50 71 84 75" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round"/>
                            <path d="M20 82 Q50 78 80 82" stroke="#0369a1" stroke-width="2.5" stroke-linecap="round"/>
                            <path d="M24 89 Q50 85 76 89" stroke="#0c4a6e" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <span class="block font-heading text-xl sm:text-2xl font-bold tracking-tight text-white leading-none">
                            {{ $churchName }}
                        </span>
                        <span class="block text-[10px] sm:text-[11px] text-white/80 uppercase tracking-[0.2em] font-semibold mt-1">
                            {{ $setting->hero_subtitle ?? 'Sinode GKSBS — Klasis Tulang Bawang' }}
                        </span>
                    </div>
                </a>

                <!-- Desktop Navigation Links (Uppercase, letter-spaced ala sonshipbayridge.church) -->
                <nav class="hidden lg:flex items-center space-x-7 text-xs font-semibold uppercase tracking-[0.18em]">
                    <a href="#beranda" class="text-white/90 hover:text-white hover:underline underline-offset-8 transition">
                        Beranda
                    </a>
                    <a href="#sambutan" class="text-white/90 hover:text-white hover:underline underline-offset-8 transition">
                        Sambutan
                    </a>
                    <a href="#pos-pelayanan" class="text-white/90 hover:text-white hover:underline underline-offset-8 transition">
                        Pos Pelayanan
                    </a>
                    <a href="#jadwal" class="text-white/90 hover:text-white hover:underline underline-offset-8 transition">
                        Jadwal Ibadah
                    </a>
                    <a href="#profil" class="text-white/90 hover:text-white hover:underline underline-offset-8 transition">
                        Profil & Visi
                    </a>
                    <a href="#kontak" class="text-white/90 hover:text-white hover:underline underline-offset-8 transition">
                        Kontak
                    </a>
                </nav>

                <!-- Access Door Buttons -->
                <div class="hidden sm:flex items-center gap-3">
                    <!-- Portal Jemaat -->
                    <a href="{{ route('portal.login') }}" 
                       class="inline-flex items-center justify-center px-4 py-2 rounded-full border border-white/70 hover:border-gksbs-leaf-light bg-white/10 hover:bg-white/20 text-white text-[11px] font-bold uppercase tracking-[0.15em] backdrop-blur-xs transition">
                        <span>Portal Jemaat</span>
                    </a>

                    <!-- Area Majelis -->
                    <a href="{{ url('/admin/login') }}" 
                       class="inline-flex items-center justify-center px-4 py-2 rounded-full border border-gksbs-sky bg-gksbs-ocean hover:bg-gksbs-sky text-white text-[11px] font-bold uppercase tracking-[0.15em] transition shadow-sm">
                        <span>Area Majelis</span>
                    </a>
                </div>

                <!-- Mobile Hamburger Button -->
                <div class="flex items-center gap-2 lg:hidden">
                    <button type="button" id="mobile-toggle" aria-label="Buka Menu" class="p-2 text-white hover:text-gksbs-sky focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>

            </div>
        </div>

        <!-- Mobile Navigation Drawer -->
        <div id="mobile-menu" class="hidden lg:hidden bg-gksbs-forest-deep/98 border-t border-white/10 px-6 py-6 space-y-4 text-xs uppercase tracking-[0.18em] font-semibold text-white">
            <a href="#beranda" class="block py-2 hover:text-gksbs-leaf-light">Beranda</a>
            <a href="#sambutan" class="block py-2 hover:text-gksbs-leaf-light">Sambutan Gembala</a>
            <a href="#pos-pelayanan" class="block py-2 hover:text-gksbs-leaf-light">Pos Pelayanan</a>
            <a href="#jadwal" class="block py-2 hover:text-gksbs-leaf-light">Jadwal Ibadah</a>
            <a href="#profil" class="block py-2 hover:text-gksbs-leaf-light">Profil & Visi Misi</a>
            <a href="#kontak" class="block py-2 hover:text-gksbs-leaf-light">Kontak & Lokasi</a>
            <div class="pt-4 border-t border-white/10 flex flex-col gap-2.5">
                <a href="{{ route('portal.login') }}" class="w-full text-center py-2.5 rounded-full border border-white/60 text-white font-bold">
                    Portal Jemaat
                </a>
                <a href="{{ url('/admin/login') }}" class="w-full text-center py-2.5 rounded-full bg-gksbs-ocean text-white font-bold">
                    Area Majelis
                </a>
            </div>
        </div>
    </header>

    <main>

        <!-- ================================================================= -->
        <!-- 2. HERO PARALLAX SECTION (Viewport Height with Dark Overlay) -->
        <!-- ================================================================= -->
        <section id="beranda" class="parallax-window viewport-height" style="background-image: url('{{ $heroBg }}');">
            <!-- Atmospheric GKSBS Forest-Blue Vignette Overlay -->
            <div class="absolute inset-0 bg-gradient-to-b from-[#061812]/80 via-[#09251c]/70 to-[#071d2b]/85 pointer-events-none"></div>

            <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white pt-24 pb-16">
                
                <!-- Sinodal Badge -->
                <div class="inline-block mb-6">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-[0.25em] text-gksbs-leaf-light/95 border-b border-gksbs-leaf-light/40 pb-1">
                        Gereja Kristen Sumatera Bagian Selatan
                    </span>
                </div>

                <!-- Sacred Scripture Quote (Caudex Serif) -->
                <div class="max-w-3xl mx-auto mb-8 sm:mb-10">
                    <blockquote class="font-heading text-2xl sm:text-4xl md:text-5xl font-light leading-snug tracking-tight text-white/95 italic">
                        “{{ $setting->theme_verse ?? 'Hendaklah kamu saling mengasihi sebagai saudara dan saling mendahului dalam memberi hormat.' }}”
                    </blockquote>
                    @if($setting->theme_verse_ref)
                        <div class="mt-4">
                            <span class="text-xs uppercase tracking-[0.25em] font-semibold text-gksbs-sky-light/90">
                                — {{ $setting->theme_verse_ref }} —
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Church Title -->
                <div class="mb-10 max-w-2xl mx-auto">
                    <h1 class="font-heading text-3xl sm:text-5xl md:text-6xl font-normal tracking-wide text-white mb-3">
                        {{ $churchName }}
                    </h1>
                    <p class="text-sm sm:text-base text-white/80 font-normal tracking-wide max-w-xl mx-auto">
                        {{ $setting->hero_tagline ?? 'Gereja yang Terbuka, Oikumenis, dan Berakar dalam Kasih Kristus' }}
                    </p>
                </div>

                <!-- Action Buttons (Sonship Bay Ridge circular/pill style) -->
                <div class="flex flex-wrap items-center justify-center gap-4">
                    <a href="#jadwal" class="sonship-btn-outline">
                        <span>Jadwal Ibadah</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </a>
                    <a href="#pos-pelayanan" class="sonship-btn-solid-blue">
                        <span>Pos Pelayanan & Warta</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>

                <!-- Scroll Down Arrow -->
                <div class="mt-14 sm:mt-16 flex justify-center">
                    <a href="#lokasi-ringkas" aria-label="Gulir ke bawah" class="scroll-indicator inline-flex items-center justify-center w-10 h-10 rounded-full border border-white/40 text-white/70 hover:text-white hover:border-white transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7-7-7"></path>
                        </svg>
                    </a>
                </div>

            </div>
        </section>

        <!-- ================================================================= -->
        <!-- 3. LOCATION & SERVICE HOURS DETAILS BAR (Sonship Map/Location Bar) -->
        <!-- ================================================================= -->
        <section id="lokasi-ringkas" class="bg-gksbs-forest-deep text-white border-y border-white/10 relative z-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-12">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                    
                    <!-- Location Column -->
                    <div class="md:col-span-5 space-y-2 border-b md:border-b-0 md:border-r border-white/10 pb-6 md:pb-0 md:pr-8">
                        <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-gksbs-leaf-light block">
                            Lokasi & Alamat Gedung Gereja
                        </span>
                        <h2 class="font-heading text-2xl font-light text-white leading-snug">
                            {{ $churchName }}
                        </h2>
                        <p class="text-xs sm:text-sm text-white/70 leading-relaxed">
                            {{ $setting->contact_address ?? 'Jl. Gereja Filadelfia No. 01, Kel. Candimas, Kec. Natar, Lampung Selatan' }}
                        </p>
                        <div class="pt-2">
                            <a href="{{ $setting->contact_maps_url ?: 'https://maps.google.com/?q=' . urlencode($churchName) }}" 
                               target="_blank" rel="noopener noreferrer" 
                               class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gksbs-sky-light hover:text-white transition">
                                <span>Petunjuk Arah (Google Maps)</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Worship Times Column -->
                    <div class="md:col-span-4 space-y-2 border-b md:border-b-0 md:border-r border-white/10 pb-6 md:pb-0 md:pr-8">
                        <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-gksbs-sky-light block">
                            Waktu Ibadah Minggu
                        </span>
                        <div class="space-y-1 text-sm text-white/90">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">Ibadah Raya Minggu</span>
                                <span class="font-bold text-white">08:30 WIB</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-white/70">
                                <span>Sekolah Minggu & Tunas</span>
                                <span>08:30 WIB</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-white/70">
                                <span>Ibadah Pemuda / Remaja</span>
                                <span>Sabtu 17:00 WIB</span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact & Bulletins Column -->
                    <div class="md:col-span-3 space-y-3">
                        <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-white/60 block">
                            Sekretariat & Hubungan
                        </span>
                        <p class="text-xs text-white/80">
                            Telepon/WA: <span class="font-semibold text-white">{{ $setting->contact_phone ?? '0812-7200-1234' }}</span>
                        </p>
                        <p class="text-xs text-white/80 truncate">
                            Email: <span class="text-white">{{ $setting->contact_email ?? 'sekretariat@gksbs-filadelfia.org' }}</span>
                        </p>
                        <div class="pt-1">
                            <a href="#pos-pelayanan" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 text-[11px] uppercase tracking-wider font-semibold text-white transition">
                                <span>Warta Jemaat Terbaru &rarr;</span>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- 4. SAMBUTAN GEMBALA (Dark Parallax Section 2 - Bible & Pulpit) -->
        <!-- ================================================================= -->
        <section id="sambutan" class="parallax-window" style="background-image: url('https://images.unsplash.com/photo-1504052434569-70ad5836ab65?q=80&w=1920&auto=format&fit=crop');">
            <!-- Dark Forest-Blue Overlay -->
            <div class="absolute inset-0 bg-[#061a13]/85 pointer-events-none"></div>

            <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32 text-center text-white">
                
                <span class="text-xs uppercase tracking-[0.25em] font-semibold text-gksbs-leaf-light block mb-3">
                    Warta & Salam Penggembalaan
                </span>

                <h2 class="font-heading text-3xl sm:text-5xl font-light text-white mb-6">
                    {{ $setting->pastoral_greeting_title ?? 'Salam Kasih & Damai Sejahtera Kristus' }}
                </h2>

                <div class="w-16 h-0.5 bg-gksbs-leaf mx-auto mb-10"></div>

                <!-- Pastoral Message Box -->
                <div class="max-w-3xl mx-auto text-left bg-black/40 backdrop-blur-md p-8 sm:p-12 rounded-3xl border border-white/15 shadow-2xl">
                    <div class="font-heading text-lg sm:text-xl text-white/90 leading-relaxed italic font-light space-y-4">
                        {!! nl2br(e($setting->pastoral_greeting_content ?? "Selamat datang di Website Resmi GKSBS Filadelfia. Kami bersyukur atas kasih karunia Tuhan yang terus memelihara persekutuan jemaat induk dan seluruh jemaat kelompok (Candimas, Trimulyo, Margomulyo). Mari bersama kita terus berakar di dalam Kristus, bertumbuh dalam iman, dan berbuah lebat bagi masyarakat sekitar.")) !!}
                    </div>

                    <div class="mt-8 pt-6 border-t border-white/15 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="font-heading text-lg font-bold text-white">
                                {{ $setting->pastoral_greeting_author ?? 'Pdt. Samuel Kriswanto, M.Th.' }}
                            </h3>
                            <p class="text-xs uppercase tracking-widest text-gksbs-leaf-light font-semibold mt-0.5">
                                {{ $setting->pastoral_greeting_author_role ?? 'Ketua Majelis Jemaat GKSBS Filadelfia' }}
                            </p>
                        </div>
                        <div class="font-heading text-lg font-normal tracking-wider text-white/50 italic">
                            Soli Deo Gloria
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- ================================================================= -->
        <!-- 5. POS PELAYANAN & WARTA JEMAAT (Light Parallax Section 3) -->
        <!-- ================================================================= -->
        <section id="pos-pelayanan" class="parallax-window" style="background-image: url('https://images.unsplash.com/photo-1519817650390-64a93db51149?q=80&w=1920&auto=format&fit=crop');">
            <!-- Frosted Light Overlay -->
            <div class="absolute inset-0 bg-[#f8faf9]/94 backdrop-blur-xs pointer-events-none"></div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32">
                
                <div class="max-w-3xl mx-auto text-center mb-16">
                    <span class="text-xs uppercase tracking-[0.25em] font-semibold text-gksbs-teal block mb-3">
                        Persekutuan Cabang & Wilayah
                    </span>
                    <h2 class="font-heading text-3xl sm:text-5xl font-light text-slate-900 tracking-tight">
                        Pos Pelayanan & Kelompok Jemaat
                    </h2>
                    <div class="w-16 h-0.5 bg-gksbs-teal mx-auto my-5"></div>
                    <p class="text-sm sm:text-base text-slate-600 font-normal leading-relaxed">
                        GKSBS Filadelfia menaungi jemaat induk serta pos-pos pelayanan kelompok yang tersebar. Warga jemaat dapat langsung mengakses edisi warta mingguan terpublikasi di bawah ini.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @forelse($churches as $church)
                        <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-slate-200/80 p-7 sm:p-8 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between group">
                            <div>
                                <div class="flex items-center justify-between gap-3 mb-5">
                                    <span class="inline-block px-3 py-1 rounded-full text-[10px] font-extrabold tracking-widest uppercase bg-gksbs-forest/10 text-gksbs-forest border border-gksbs-forest/20">
                                        {{ $church->code }}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                                        Wilayah Pelayanan
                                    </span>
                                </div>

                                <h3 class="font-heading text-xl sm:text-2xl font-bold text-slate-900 mb-3 group-hover:text-gksbs-ocean transition-colors">
                                    {{ $church->name }}
                                </h3>

                                <div class="space-y-2 text-xs sm:text-sm text-slate-600 mt-4 mb-6 leading-relaxed">
                                    @if($church->address)
                                        <p class="line-clamp-2">
                                            <strong class="text-slate-700">Alamat:</strong> {{ $church->address }}
                                        </p>
                                    @endif
                                    @if($church->phone)
                                        <p>
                                            <strong class="text-slate-700">Telepon:</strong> {{ $church->phone }}
                                        </p>
                                    @endif
                                    @if($church->email)
                                        <p class="truncate">
                                            <strong class="text-slate-700">Email:</strong> {{ $church->email }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="pt-5 border-t border-slate-100">
                                <a href="{{ route('public.warta.index', $church->code) }}" 
                                   class="inline-flex items-center justify-center gap-2 w-full py-3 px-5 rounded-full bg-gksbs-forest hover:bg-gksbs-leaf text-white font-bold text-xs uppercase tracking-[0.16em] shadow-sm transition">
                                    <span>Baca Warta Jemaat</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-3 text-center py-12 bg-white/70 rounded-2xl border border-slate-200">
                            <p class="text-slate-500">Belum ada data kelompok cabang yang terdaftar.</p>
                        </div>
                    @endforelse
                </div>

            </div>
        </section>

        <!-- ================================================================= -->
        <!-- 6. JADWAL IBADAH (Dark Parallax Section 4 - Altar & Candles) -->
        <!-- ================================================================= -->
        <section id="jadwal" class="parallax-window" style="background-image: url('https://images.unsplash.com/photo-1478147427282-58a87a120781?q=80&w=1920&auto=format&fit=crop');">
            <!-- Deep Ocean-Forest Overlay -->
            <div class="absolute inset-0 bg-[#071922]/88 pointer-events-none"></div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32 text-white">
                
                <div class="max-w-3xl mx-auto text-center mb-16">
                    <span class="text-xs uppercase tracking-[0.25em] font-semibold text-gksbs-sky-light block mb-3">
                        Liturgi & Peribadahan
                    </span>
                    <h2 class="font-heading text-3xl sm:text-5xl font-light text-white tracking-tight">
                        Jadwal Ibadah Rutin Gereja
                    </h2>
                    <div class="w-16 h-0.5 bg-gksbs-sky mx-auto my-5"></div>
                    <p class="text-sm sm:text-base text-white/80 font-normal leading-relaxed">
                        Kami menyambut hangat kehadiran setiap saudara seiman dan tamu untuk bersekutu bersama dalam kehangatan kasih Kristus.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @if(!empty($setting->worship_schedules) && is_array($setting->worship_schedules))
                        @foreach($setting->worship_schedules as $schedule)
                            <div class="bg-black/45 backdrop-blur-md rounded-2xl p-7 border border-white/15 hover:border-gksbs-sky/50 transition duration-200 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-4">
                                        <span class="text-xs font-bold uppercase tracking-widest text-gksbs-sky-light">
                                            {{ $schedule['day'] ?? 'Minggu' }}
                                        </span>
                                        <span class="text-xs text-white/60 font-semibold">
                                            {{ $schedule['time'] ?? '08:30 WIB' }}
                                        </span>
                                    </div>
                                    <h3 class="font-heading text-xl font-bold text-white mb-2">
                                        {{ $schedule['name'] ?? 'Ibadah Raya' }}
                                    </h3>
                                </div>
                                <div class="mt-6 pt-4 border-t border-white/10 text-xs text-white/70">
                                    {{ $schedule['location'] ?? 'Gedung Gereja Utama' }}
                                </div>
                            </div>
                        @endforeach
                    @else
                        <!-- Default Fallback Schedules -->
                        <div class="bg-black/45 backdrop-blur-md rounded-2xl p-7 border border-white/15">
                            <span class="text-xs font-bold uppercase tracking-widest text-gksbs-sky-light block mb-1">Minggu · 08:30 WIB</span>
                            <h3 class="font-heading text-xl font-bold text-white mb-2">Ibadah Raya Minggu Induk</h3>
                            <p class="text-xs text-white/70">Gedung Gereja Utama</p>
                        </div>
                        <div class="bg-black/45 backdrop-blur-md rounded-2xl p-7 border border-white/15">
                            <span class="text-xs font-bold uppercase tracking-widest text-gksbs-sky-light block mb-1">Minggu · 08:30 WIB</span>
                            <h3 class="font-heading text-xl font-bold text-white mb-2">Sekolah Minggu Anak</h3>
                            <p class="text-xs text-white/70">Gedung Serbaguna</p>
                        </div>
                        <div class="bg-black/45 backdrop-blur-md rounded-2xl p-7 border border-white/15">
                            <span class="text-xs font-bold uppercase tracking-widest text-gksbs-sky-light block mb-1">Sabtu · 17:00 WIB</span>
                            <h3 class="font-heading text-xl font-bold text-white mb-2">Ibadah Pemuda & Remaja</h3>
                            <p class="text-xs text-white/70">Ruang Persekutuan Pemuda</p>
                        </div>
                        <div class="bg-black/45 backdrop-blur-md rounded-2xl p-7 border border-white/15">
                            <span class="text-xs font-bold uppercase tracking-widest text-gksbs-sky-light block mb-1">Rabu · 18:30 WIB</span>
                            <h3 class="font-heading text-xl font-bold text-white mb-2">Persekutuan Doa & PA</h3>
                            <p class="text-xs text-white/70">Ruang Konsistori</p>
                        </div>
                    @endif
                </div>

            </div>
        </section>

        <!-- ================================================================= -->
        <!-- 7. PROFIL, VISI & MISI GEREJA (Light Parallax Section 5) -->
        <!-- ================================================================= -->
        <section id="profil" class="parallax-window" style="background-image: url('https://images.unsplash.com/photo-1519491050282-cf00c82424b4?q=80&w=1920&auto=format&fit=crop');">
            <!-- Light Altar Linen Overlay -->
            <div class="absolute inset-0 bg-[#fbfcfd]/95 backdrop-blur-xs pointer-events-none"></div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32">
                
                <div class="max-w-3xl mx-auto text-center mb-16">
                    <span class="text-xs uppercase tracking-[0.25em] font-semibold text-gksbs-forest block mb-3">
                        Identitas & Panggilan Pelayanan
                    </span>
                    <h2 class="font-heading text-3xl sm:text-5xl font-light text-slate-900 tracking-tight">
                        Profil, Visi, dan Misi Gereja
                    </h2>
                    <div class="w-16 h-0.5 bg-gksbs-forest mx-auto my-5"></div>
                    <p class="text-sm sm:text-base text-slate-600 font-normal leading-relaxed">
                        Mengenal perjalanan persekutuan dan arah panggilan iman GKSBS di tengah masyarakat.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    
                    <!-- Tentang Gereja -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-8 shadow-sm flex flex-col justify-between">
                        <div>
                            <span class="text-xs uppercase tracking-widest font-bold text-gksbs-forest block mb-2">Sejarah & Profil</span>
                            <h3 class="font-heading text-2xl font-bold text-slate-900 mb-4">Tentang Gereja</h3>
                            <p class="text-slate-600 text-sm leading-relaxed">
                                {{ $setting->about_summary ?? 'GKSBS Filadelfia adalah persekutuan jemaat yang berpusat pada Kristus, melayani warga jemaat di wilayah Lampung Tengah dan sekitarnya melalui jemaat induk dan pos-pos pelayanan kelompok jemaat secara terpadu dan guyub.' }}
                            </p>
                        </div>
                        <div class="mt-8 pt-4 border-t border-slate-100 text-xs font-semibold text-gksbs-forest">
                            Sinode GKSBS — Klasis Tulang Bawang
                        </div>
                    </div>

                    <!-- Visi Pelayanan -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-8 shadow-sm flex flex-col justify-between">
                        <div>
                            <span class="text-xs uppercase tracking-widest font-bold text-gksbs-ocean block mb-2">Arah Panggilan</span>
                            <h3 class="font-heading text-2xl font-bold text-slate-900 mb-4">Visi Pelayanan</h3>
                            <blockquote class="font-heading text-lg sm:text-xl text-slate-800 leading-relaxed italic border-l-2 border-gksbs-ocean pl-4 py-1">
                                “{{ $setting->vision ?? 'Terwujudnya jemaat yang mandiri, misioner, berwawasan oikumenis, serta menjadi berkat nyata bagi sesama ciptaan Tuhan.' }}”
                            </blockquote>
                        </div>
                        <div class="mt-8 pt-4 border-t border-slate-100 text-xs font-semibold text-gksbs-ocean">
                            Persekutuan Rumah Bersama
                        </div>
                    </div>

                    <!-- Misi Pelayanan -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-8 shadow-sm flex flex-col justify-between">
                        <div>
                            <span class="text-xs uppercase tracking-widest font-bold text-gksbs-leaf block mb-2">Tindakan Nyata</span>
                            <h3 class="font-heading text-2xl font-bold text-slate-900 mb-4">Misi Pelayanan</h3>
                            <ul class="space-y-3 text-xs sm:text-sm text-slate-600">
                                @if(!empty($setting->missions) && is_array($setting->missions))
                                    @foreach($setting->missions as $mission)
                                        @php
                                            $missionText = is_array($mission) ? ($mission['item'] ?? '') : $mission;
                                        @endphp
                                        @if(filled($missionText))
                                            <li class="flex items-start gap-2.5">
                                                <span class="text-gksbs-leaf font-bold">•</span>
                                                <span>{{ $missionText }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                @else
                                    <li class="flex items-start gap-2.5"><span class="text-gksbs-leaf font-bold">•</span><span>Menyelenggarakan peribadahan dan pembinaan rohani yang berakar pada Firman Allah.</span></li>
                                    <li class="flex items-start gap-2.5"><span class="text-gksbs-leaf font-bold">•</span><span>Mempererat persaudaraan dan solidaritas antar jemaat kelompok secara guyub dan inklusif.</span></li>
                                    <li class="flex items-start gap-2.5"><span class="text-gksbs-leaf font-bold">•</span><span>Mengembangkan karya pelayanan diakonia holistik bagi jemaat dan masyarakat luas.</span></li>
                                @endif
                            </ul>
                        </div>
                        <div class="mt-8 pt-4 border-t border-slate-100 text-xs font-semibold text-gksbs-leaf">
                            Kemandirian & Keterbukaan
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- ================================================================= -->
        <!-- 8. DUA PINTU AKSES LAYANAN MANDIRI (Sonship Callout Section) -->
        <!-- ================================================================= -->
        <section class="bg-gksbs-forest-dark text-white border-t border-white/10 py-20 sm:py-24">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center max-w-2xl mx-auto mb-14">
                    <span class="text-xs uppercase tracking-[0.25em] font-semibold text-gksbs-leaf-light block mb-2">
                        Sistem Informasi Gereja
                    </span>
                    <h2 class="font-heading text-3xl sm:text-4xl font-light text-white">
                        Layanan Jemaat & Tata Kelola
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <!-- Pintu Portal Jemaat -->
                    <div class="bg-black/30 backdrop-blur-sm rounded-3xl p-8 sm:p-10 border border-white/10 flex flex-col justify-between hover:border-gksbs-leaf/50 transition">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-gksbs-leaf-light block mb-2">
                                Warga Jemaat Terdaftar
                            </span>
                            <h3 class="font-heading text-2xl sm:text-3xl font-normal text-white mb-3">
                                Portal Jemaat
                            </h3>
                            <p class="text-sm text-white/70 leading-relaxed mb-8">
                                Akses mandiri untuk memeriksa data keluarga jemaat, riwayat sakramen (baptis, sidi, perkawinan), jadwal keterlibatan pelayanan, serta edisi warta jemaat personal.
                            </p>
                        </div>
                        <a href="{{ route('portal.login') }}" class="sonship-btn-outline w-full text-center">
                            <span>Masuk ke Portal Jemaat</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                    </div>

                    <!-- Pintu Area Majelis -->
                    <div class="bg-black/30 backdrop-blur-sm rounded-3xl p-8 sm:p-10 border border-white/10 flex flex-col justify-between hover:border-gksbs-sky/50 transition">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-gksbs-sky-light block mb-2">
                                Presbiter & Administrasi
                            </span>
                            <h3 class="font-heading text-2xl sm:text-3xl font-normal text-white mb-3">
                                Area Majelis
                            </h3>
                            <p class="text-sm text-white/70 leading-relaxed mb-8">
                                Pintu masuk resmi bagi Pendeta, Penatua, Diaken, Badan Pengurus Kelompok, dan Staf Keuangan untuk tata kelola data jemaat, perbendaharaan, dan publikasi warta.
                            </p>
                        </div>
                        <a href="{{ url('/admin/login') }}" class="sonship-btn-solid-blue w-full text-center">
                            <span>Masuk Area Majelis</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                    </div>

                </div>

            </div>
        </section>

        <!-- ================================================================= -->
        <!-- 9. KONTAK, LOKASI & INFO SINODAL GKSBS -->
        <!-- ================================================================= -->
        <section id="kontak" class="bg-white py-20 sm:py-24 border-t border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
                    
                    <div class="lg:col-span-6 space-y-6">
                        <span class="text-xs uppercase tracking-[0.25em] font-semibold text-gksbs-ocean block">
                            Layanan Sekretariat
                        </span>
                        <h2 class="font-heading text-3xl sm:text-4xl font-light text-slate-900 leading-snug">
                            Kontak & Informasi Gereja
                        </h2>
                        <div class="w-12 h-0.5 bg-gksbs-ocean"></div>

                        <div class="space-y-4 text-sm text-slate-700 pt-2">
                            <div>
                                <strong class="block text-xs uppercase tracking-wider text-slate-400 font-bold mb-1">Alamat Resmi</strong>
                                <p class="leading-relaxed">{{ $setting->contact_address ?? 'Jl. Gereja Filadelfia No. 01, Kel. Candimas, Kec. Natar, Lampung Selatan' }}</p>
                            </div>

                            <div>
                                <strong class="block text-xs uppercase tracking-wider text-slate-400 font-bold mb-1">Telepon & WhatsApp</strong>
                                <p>{{ $setting->contact_phone ?? '0812-7200-1234' }}</p>
                            </div>

                            <div>
                                <strong class="block text-xs uppercase tracking-wider text-slate-400 font-bold mb-1">Email Resmi</strong>
                                <p>{{ $setting->contact_email ?? 'sekretariat@gksbs-filadelfia.org' }}</p>
                            </div>
                        </div>

                        <div class="pt-4">
                            <a href="{{ $setting->contact_maps_url ?: 'https://maps.google.com/?q=' . urlencode($churchName) }}" 
                               target="_blank" rel="noopener noreferrer" 
                               class="sonship-btn-dark">
                                <span>Buka Peta Petunjuk Arah</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Sinode GKSBS Identity Box -->
                    <div class="lg:col-span-6 bg-[#f4f7f6] p-8 sm:p-10 rounded-3xl border border-slate-200">
                        <div class="flex items-center gap-3.5 mb-5">
                            <!-- Logo GKSBS Mini -->
                            <div class="h-10 w-10 p-1 rounded-lg bg-gksbs-forest flex items-center justify-center flex-shrink-0">
                                <svg viewBox="0 0 100 100" class="w-8 h-8" fill="none">
                                    <path d="M50 8 C46 22 42 34 50 48 C58 34 54 22 50 8Z" fill="#22c55e"/>
                                    <path d="M38 16 C30 27 30 38 43 47 C43 33 42 24 38 16Z" fill="#4ade80"/>
                                    <path d="M62 16 C70 27 70 38 57 47 C57 33 58 24 62 16Z" fill="#4ade80"/>
                                    <path d="M26 28 C16 38 20 50 36 52 C34 38 31 31 26 28Z" fill="#15803d"/>
                                    <path d="M74 28 C84 38 80 50 64 52 C66 38 69 31 74 28Z" fill="#15803d"/>
                                    <circle cx="50" cy="50" r="3.5" fill="#ffffff"/>
                                    <path d="M20 68 Q50 64 80 68" stroke="#38bdf8" stroke-width="3" stroke-linecap="round"/>
                                    <path d="M16 75 Q50 71 84 75" stroke="#0284c7" stroke-width="3" stroke-linecap="round"/>
                                    <path d="M20 82 Q50 78 80 82" stroke="#0369a1" stroke-width="3" stroke-linecap="round"/>
                                    <path d="M24 89 Q50 85 76 89" stroke="#0c4a6e" stroke-width="3" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-heading text-lg font-bold text-slate-900">
                                    Sinode Gereja Kristen Sumatera Bagian Selatan
                                </h3>
                                <p class="text-xs uppercase tracking-wider text-gksbs-forest font-semibold">
                                    Anggota Persekutuan Gereja-gereja di Indonesia (PGI)
                                </p>
                            </div>
                        </div>

                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-6">
                            Lambang Sinode GKSBS tersusun atas <strong>tujuh lembar daun cengkeh warna hijau</strong> yang melambangkan tujuh klasis pendiri pada tahun 1987, serta <strong>empat garis warna biru</strong> yang melambangkan keberadaan pelayanan di empat provinsi Sumatera Bagian Selatan (Lampung, Sumatera Selatan, Bengkulu, dan Jambi).
                        </p>

                        <div class="pt-4 border-t border-slate-200 text-xs text-slate-500 flex items-center justify-between">
                            <span>Tata Gereja GKSBS — Eklesiologi Rumah Bersama</span>
                            <span class="font-semibold text-gksbs-forest">Sumbagsel</span>
                        </div>
                    </div>

                </div>

            </div>
        </section>

    </main>

    <!-- ================================================================= -->
    <!-- 10. FOOTER (Maranatha Dark Footer) -->
    <!-- ================================================================= -->
    <footer class="bg-gksbs-forest-dark text-white/70 text-xs border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-left">
                
                <div>
                    <span class="font-heading text-lg text-white font-bold block mb-1">
                        {{ $churchName }}
                    </span>
                    <p class="text-white/50">
                        &copy; {{ date('Y') }} {{ $churchName }}. Pelayanan untuk kemuliaan nama Tuhan Yesus Kristus.
                    </p>
                </div>

                <div class="flex items-center space-x-6 font-semibold uppercase tracking-widest text-[11px]">
                    <a href="#beranda" class="text-white/70 hover:text-white transition">Beranda</a>
                    <a href="#pos-pelayanan" class="text-white/70 hover:text-white transition">Pos Pelayanan</a>
                    <a href="{{ route('portal.login') }}" class="text-gksbs-sky-light hover:text-white transition">Portal Jemaat</a>
                    <a href="{{ url('/admin/login') }}" class="text-gksbs-leaf-light hover:text-white transition">Area Majelis</a>
                </div>

                <div class="font-heading text-sm text-white/40 italic">
                    Soli Deo Gloria
                </div>

            </div>
        </div>
    </footer>

    <!-- JavaScript for Navbar Transition on Scroll & Mobile Menu Toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const navbar = document.getElementById('church-navbar');
            const toggle = document.getElementById('mobile-toggle');
            const mobileMenu = document.getElementById('mobile-menu');

            function handleScroll() {
                if (window.pageYOffset > 50) {
                    navbar.classList.add('scrolled');
                    navbar.classList.remove('not-scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                    navbar.classList.add('not-scrolled');
                }
            }

            window.addEventListener('scroll', handleScroll, { passive: true });
            handleScroll();

            if (toggle && mobileMenu) {
                toggle.addEventListener('click', function () {
                    mobileMenu.classList.toggle('hidden');
                });
                mobileMenu.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', () => mobileMenu.classList.add('hidden'));
                });
            }
        });
    </script>
</body>
</html>
