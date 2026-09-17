@php
    $setting = $setting ?? \App\Models\LandingSetting::current();
    $churches = $churches ?? \App\Models\Church::all();
    $churchName = $setting->hero_title ?? 'GKSBS Filadelfia';

    // Backgrounds dari CMS (dengan fallback foto gerejawi otentik resolusi tinggi)
    $heroBg = $setting->hero_banner_url ?: 'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?q=80&w=1920&auto=format&fit=crop';
    $wartaBg = $setting->warta_bg_url ?: 'https://images.unsplash.com/photo-1504052434569-70ad5836ab65?q=80&w=1920&auto=format&fit=crop';
    $branchesBg = $setting->branches_bg_url ?: 'https://images.unsplash.com/photo-1543807535-eceef0bc6599?q=80&w=1920&auto=format&fit=crop';
    $worshipBg = $setting->worship_bg_url ?: 'https://images.unsplash.com/photo-1478147427282-58a87a120781?q=80&w=1920&auto=format&fit=crop';
    $profileBg = $setting->profile_bg_url ?: 'https://images.unsplash.com/photo-1438032005730-c779502df39b?q=80&w=1920&auto=format&fit=crop';
    $portalBg = $setting->portal_bg_url ?: 'https://images.unsplash.com/photo-1510590337019-5ef8d3d32116?q=80&w=1920&auto=format&fit=crop';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $churchName }} — Gereja Kristen Sumatera Bagian Selatan</title>
    <meta name="description" content="Website Resmi {{ $churchName }}. {{ $setting->hero_tagline ?? 'Gereja yang Terbuka, Oikumenis, dan Berakar dalam Kasih Kristus' }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Fonts: Playfair Display (Serif Elegan & Luwes) & Plus Jakarta Sans (Modern & Human) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
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
        html, body {
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            background-color: #ffffff;
            -webkit-font-smoothing: antialiased;
        }

        .font-heading {
            font-family: 'Playfair Display', Georgia, 'Times New Roman', serif;
        }

        /* Fixed Top Header with Smooth Scroll Blur */
        #maranatha-header-top {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 99998;
            transition: padding 0.3s ease, background-color 0.3s ease, box-shadow 0.3s ease;
        }
        #maranatha-header-top.not-scrolled {
            padding: 1.4rem 0;
            background-color: transparent;
        }
        #maranatha-header-top.scrolled {
            padding: 0.75rem 0;
            background-color: rgba(9, 37, 28, 0.96);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        /* Vertical Parallax Viewport Height Section */
        .maranatha-home-section {
            display: table;
            width: 100%;
            position: relative;
            background-repeat: no-repeat;
            background-position: center center;
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
        }

        /* Mobile fallback where fixed attachment can be sluggish */
        @media (max-width: 1024px) {
            .maranatha-home-section {
                background-attachment: scroll !important;
            }
        }

        .maranatha-home-section-inner {
            display: table-cell;
            vertical-align: middle;
            position: relative;
            text-align: center;
            padding: 110px 24px 80px;
        }

        .maranatha-home-section-content {
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
        }

        .maranatha-home-section-content h1,
        .maranatha-home-section-content h2 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: clamp(2.2rem, 5vw, 3.6rem);
            line-height: 1.25;
            margin: 0 0 20px;
            font-weight: 400;
            letter-spacing: -0.01em;
        }

        .maranatha-home-section-content p {
            font-size: clamp(1.05rem, 2vw, 1.45rem);
            font-weight: 300;
            line-height: 1.65;
            margin: 20px auto 32px;
            max-width: 760px;
        }

        /* Pill Buttons with High Legibility */
        .maranatha-circle-buttons-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .maranatha-circle-buttons-list li {
            display: inline-block;
        }
        .maranatha-circle-buttons-list a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 28px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .btn-maranatha-dark-sec {
            border: 1.5px solid rgba(255, 255, 255, 0.75);
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(4px);
        }
        .btn-maranatha-dark-sec:hover {
            border-color: #22c55e;
            background-color: #16a34a;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(22, 163, 74, 0.35);
        }

        .btn-maranatha-accent {
            border: 1.5px solid #0284c7;
            background-color: #0369a1;
            color: #ffffff;
        }
        .btn-maranatha-accent:hover {
            background-color: #0284c7;
            border-color: #38bdf8;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(2, 132, 199, 0.35);
        }

        @keyframes scrollBounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(7px); }
            60% { transform: translateY(3px); }
        }
        .scroll-indicator {
            animation: scrollBounce 2.5s infinite;
        }
    </style>
</head>
<body class="selection:bg-gksbs-ocean selection:text-white">

    <!-- ================================================================= -->
    <!-- 1. STREAMLINED HEADER (Minimalist, No Subtitle, Rapi & Elegan) -->
    <!-- ================================================================= -->
    <header id="maranatha-header-top" class="not-scrolled">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-4">
                
                <!-- Left: GKSBS Logo & Simple Clean Title (Single Line) -->
                <a href="#beranda" class="flex items-center gap-3 group text-white flex-shrink-0">
                    <div class="h-10 w-10 flex-shrink-0 flex items-center justify-center p-1 rounded-full bg-black/30 backdrop-blur-xs border border-white/20 group-hover:border-gksbs-leaf-light transition">
                        <svg viewBox="0 0 100 100" class="w-8 h-8" fill="none" xmlns="http://www.w3.org/2000/svg">
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
                    <span class="font-heading text-xl sm:text-2xl font-semibold tracking-tight text-white leading-none whitespace-nowrap">
                        {{ $churchName }}
                    </span>
                </a>

                <!-- Center: Concise Desktop Nav -->
                <nav class="hidden lg:flex items-center space-x-6 text-[13px] font-medium uppercase tracking-[0.14em]">
                    <a href="#beranda" class="text-white/80 hover:text-white transition">Beranda</a>
                    <a href="#sambutan" class="text-white/80 hover:text-white transition">Warta</a>
                    <a href="#pos-pelayanan" class="text-white/80 hover:text-white transition">Pos Pelayanan</a>
                    <a href="#jadwal" class="text-white/80 hover:text-white transition">Jadwal</a>
                    <a href="{{ route('public.offering.index') }}" class="text-white/80 hover:text-white transition">Persembahan</a>
                    <a href="#kontak" class="text-white/80 hover:text-white transition">Kontak</a>
                </nav>

                <!-- Right: Solid Neat Portal Action Buttons (No Breaking / Clean Layout) -->
                <div class="hidden sm:flex items-center gap-2.5 flex-shrink-0">
                    <a href="{{ route('portal.login') }}" 
                       class="px-4 py-2 rounded-full border border-white/50 hover:border-white text-white hover:bg-white/10 text-xs font-semibold uppercase tracking-wider transition whitespace-nowrap">
                        Portal Jemaat
                    </a>
                    <a href="{{ url('/admin/login') }}" 
                       class="px-4 py-2 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold uppercase tracking-wider transition shadow-sm whitespace-nowrap">
                        Area Majelis
                    </a>
                </div>

                <!-- Mobile Hamburger Button -->
                <div class="flex items-center gap-2 lg:hidden">
                    <button type="button" id="mobile-toggle" aria-label="Menu" class="p-2 text-white hover:text-gksbs-sky">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                </div>

            </div>
        </div>

        <!-- Mobile Drawer -->
        <div id="mobile-menu" class="hidden lg:hidden bg-gksbs-forest-deep/98 border-t border-white/10 px-6 py-5 space-y-3 text-xs uppercase tracking-[0.14em] font-semibold text-white">
            <a href="#beranda" class="block py-2 hover:text-gksbs-leaf-light">Beranda</a>
            <a href="#sambutan" class="block py-2 hover:text-gksbs-leaf-light">Warta & Sambutan</a>
            <a href="#pos-pelayanan" class="block py-2 hover:text-gksbs-leaf-light">Pos Pelayanan Cabang</a>
            <a href="#jadwal" class="block py-2 hover:text-gksbs-leaf-light">Jadwal Ibadah</a>
            <a href="{{ route('public.offering.index') }}" class="block py-2 hover:text-gksbs-leaf-light">Persembahan Online</a>
            <a href="#kontak" class="block py-2 hover:text-gksbs-leaf-light">Kontak & Lokasi</a>
            <div class="pt-3 border-t border-white/10 flex flex-col gap-2">
                <a href="{{ route('portal.login') }}" class="w-full text-center py-2.5 rounded-full border border-white/60 text-white font-bold">Portal Jemaat</a>
                <a href="{{ url('/admin/login') }}" class="w-full text-center py-2.5 rounded-full bg-emerald-600 text-white font-bold">Area Majelis</a>
            </div>
        </div>
    </header>

    <main>

        <!-- ================================================================= -->
        <!-- SEKSI 1: BERANDA / HERO (Parallax 100vh) -->
        <!-- ================================================================= -->
        <section id="beranda" class="maranatha-home-section" style="background-image: url('{{ $heroBg }}');">
            <!-- Atmospheric Dark Forest-Ocean Vignette -->
            <div class="absolute inset-0 bg-gradient-to-b from-[#061812]/85 via-[#09251c]/75 to-[#071d2b]/88 pointer-events-none"></div>

            <div class="maranatha-home-section-inner">
                <div class="maranatha-home-section-content text-white">
                    
                    <span class="text-xs uppercase tracking-[0.22em] font-semibold text-gksbs-leaf-light block mb-3">
                        Gereja Kristen Sumatera Bagian Selatan
                    </span>

                    <h1 class="text-white">
                        {{ $churchName }}
                    </h1>

                    <blockquote class="font-heading text-2xl sm:text-3xl md:text-4xl leading-snug italic text-white/95 my-5 max-w-3xl mx-auto font-normal">
                        “{{ $setting->theme_verse ?? 'Hendaklah kamu saling mengasihi sebagai saudara dan saling mendahului dalam memberi hormat.' }}”
                    </blockquote>

                    @if($setting->theme_verse_ref)
                        <div class="text-xs uppercase tracking-[0.22em] font-medium text-gksbs-sky-light mb-6">
                            — {{ $setting->theme_verse_ref }} —
                        </div>
                    @endif

                    <p class="text-white/85 text-sm sm:text-base font-light">
                        {{ $setting->hero_tagline ?? 'Gereja yang Terbuka, Oikumenis, dan Berakar dalam Kasih Kristus' }}
                    </p>

                    <ul class="maranatha-circle-buttons-list">
                        <li><a href="#jadwal" class="btn-maranatha-dark-sec">Jadwal Ibadah</a></li>
                        <li><a href="#pos-pelayanan" class="btn-maranatha-dark-sec">Pos Pelayanan & Warta</a></li>
                        <li><a href="{{ route('public.offering.index') }}" class="btn-maranatha-accent">Persembahan Digital</a></li>
                    </ul>

                    <div class="mt-12 flex justify-center">
                        <a href="#lokasi-ringkas" aria-label="Gulir ke bawah" class="scroll-indicator inline-flex items-center justify-center w-10 h-10 rounded-full border border-white/30 text-white/70 hover:text-white transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7-7-7"></path></svg>
                        </a>
                    </div>

                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- SEKSI 2: LOCATION DETAILS BAR (Rapi & Jelas) -->
        <!-- ================================================================= -->
        <section id="lokasi-ringkas" class="bg-white border-b border-slate-200 relative z-20 py-14 sm:py-16">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center mb-8">
                    <h2 class="font-heading text-2xl sm:text-3xl text-slate-900 font-semibold mb-1.5">Informasi Lokasi & Waktu</h2>
                    <div class="w-10 h-0.5 bg-gksbs-forest mx-auto"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center items-start">
                    <!-- Alamat -->
                    <div class="space-y-2">
                        <span class="text-xs uppercase tracking-wider font-bold text-gksbs-forest block">Alamat Gereja</span>
                        <p class="text-sm text-slate-600 leading-relaxed max-w-xs mx-auto">
                            {{ $setting->contact_address ?? 'Jl. Gereja Filadelfia No. 01, Kel. Candimas, Kec. Natar, Lampung Selatan' }}
                        </p>
                        <div class="pt-1">
                            <a href="{{ $setting->contact_maps_url ?: 'https://maps.google.com/?q=' . urlencode($churchName) }}" 
                               target="_blank" rel="noopener noreferrer" 
                               class="inline-flex items-center justify-center px-4 py-1.5 rounded-full border border-slate-300 hover:border-gksbs-forest text-slate-700 hover:text-gksbs-forest text-xs font-semibold transition">
                                Buka Google Maps &rarr;
                            </a>
                        </div>
                    </div>

                    <!-- Waktu Ibadah -->
                    <div class="space-y-2">
                        <span class="text-xs uppercase tracking-wider font-bold text-gksbs-ocean block">Ibadah Raya Minggu</span>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Setiap Minggu pukul <strong>08:30 WIB</strong><br>
                            Gedung Gereja Utama Candimas
                        </p>
                        <div class="pt-1">
                            <a href="#jadwal" class="inline-flex items-center justify-center px-4 py-1.5 rounded-full border border-slate-300 hover:border-gksbs-ocean text-slate-700 hover:text-gksbs-ocean text-xs font-semibold transition">
                                Jadwal Selengkapnya &rarr;
                            </a>
                        </div>
                    </div>

                    <!-- Kontak -->
                    <div class="space-y-2">
                        <span class="text-xs uppercase tracking-wider font-bold text-gksbs-leaf block">Sekretariat Jemaat</span>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            {{ $setting->contact_phone ?? '0812-7200-1234' }}<br>
                            {{ $setting->contact_email ?? 'sekretariat@gksbs-filadelfia.org' }}
                        </p>
                        <div class="pt-1">
                            <a href="#kontak" class="inline-flex items-center justify-center px-4 py-1.5 rounded-full border border-slate-300 hover:border-gksbs-leaf text-slate-700 hover:text-gksbs-leaf text-xs font-semibold transition">
                                Hubungi Sekretariat &rarr;
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- ================================================================= -->
        <!-- SEKSI 3: WARTA & SAMBUTAN (Parallax 100vh - Dark Overlay Jernih) -->
        <!-- ================================================================= -->
        <section id="sambutan" class="maranatha-home-section" style="background-image: url('{{ $wartaBg }}');">
            <div class="absolute inset-0 bg-[#061a13]/85 pointer-events-none"></div>

            <div class="maranatha-home-section-inner">
                <div class="maranatha-home-section-content text-white">
                    
                    <span class="text-xs uppercase tracking-[0.2em] font-semibold text-gksbs-leaf-light block mb-2">
                        Pesan Penggembalaan
                    </span>

                    <h2 class="text-white">
                        {{ $setting->pastoral_greeting_title ?? 'Warta Jemaat & Sabda Firman' }}
                    </h2>

                    <p class="text-white/90">
                        {!! nl2br(e($setting->pastoral_greeting_content ?? "Selamat datang di Website Resmi GKSBS Filadelfia. Kami bersyukur atas kasih karunia Tuhan yang terus memelihara persekutuan jemaat induk dan seluruh jemaat kelompok (Candimas, Trimulyo, Margomulyo). Mari bersama kita terus berakar di dalam Kristus, bertumbuh dalam iman, dan berbuah lebat bagi masyarakat sekitar.")) !!}
                    </p>

                    <div class="font-heading text-lg text-gksbs-leaf-light font-normal mb-8">
                        {{ $setting->pastoral_greeting_author ?? 'Pdt. Samuel Kriswanto, M.Th.' }} · <span class="text-xs uppercase tracking-widest text-white/60">{{ $setting->pastoral_greeting_author_role ?? 'Ketua Majelis Jemaat' }}</span>
                    </div>

                    <ul class="maranatha-circle-buttons-list">
                        <li><a href="#pos-pelayanan" class="btn-maranatha-dark-sec">Baca Warta Jemaat</a></li>
                        <li><a href="{{ route('portal.login') }}" class="btn-maranatha-dark-sec">Portal Jemaat</a></li>
                    </ul>

                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- SEKSI 4: POS PELAYANAN CABANG (Parallax 100vh - Foto Gereja Kristen & Dark Contrast Overlay) -->
        <!-- ================================================================= -->
        <section id="pos-pelayanan" class="maranatha-home-section" style="background-image: url('{{ $branchesBg }}');">
            <!-- Overlay Gelap Elegan: Memastikan Teks & Tombol 100% Kontras & Terbaca Jelas -->
            <div class="absolute inset-0 bg-[#071e28]/85 pointer-events-none"></div>

            <div class="maranatha-home-section-inner">
                <div class="maranatha-home-section-content text-white">
                    
                    <span class="text-xs uppercase tracking-[0.2em] font-semibold text-gksbs-sky-light block mb-2">
                        Persekutuan Cabang & Wilayah
                    </span>

                    <h2 class="text-white">
                        Pos Pelayanan & Kelompok Jemaat
                    </h2>

                    <p class="text-white/90">
                        Pelayanan GKSBS Filadelfia menjangkau jemaat induk dan pos pelayanan kelompok Candimas, Trimulyo, dan Margomulyo. Akses edisi warta publik per jemaat kelompok di bawah ini.
                    </p>

                    <ul class="maranatha-circle-buttons-list">
                        @forelse($churches as $church)
                            <li>
                                <a href="{{ route('public.warta.index', $church->code) }}" class="btn-maranatha-dark-sec">
                                    {{ $church->name }}
                                </a>
                            </li>
                        @empty
                            <li><span class="text-sm text-white/70">Belum ada kelompok jemaat terdaftar.</span></li>
                        @endforelse
                    </ul>

                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- SEKSI 5: JADWAL IBADAH RAYA (Parallax 100vh - Dark Overlay) -->
        <!-- ================================================================= -->
        <section id="jadwal" class="maranatha-home-section" style="background-image: url('{{ $worshipBg }}');">
            <div class="absolute inset-0 bg-[#09221a]/85 pointer-events-none"></div>

            <div class="maranatha-home-section-inner">
                <div class="maranatha-home-section-content text-white">
                    
                    <span class="text-xs uppercase tracking-[0.2em] font-semibold text-gksbs-leaf-light block mb-2">
                        Liturgi Peribadahan
                    </span>

                    <h2 class="text-white">
                        Ibadah Raya & Persekutuan Doa
                    </h2>

                    <p class="text-white/90">
                        Mari bersekutu bersama dalam kehangatan persaudaraan seiman: Ibadah Raya Minggu pukul 08:30 WIB, Kebaktian Sekolah Minggu Anak, Ibadah Pemuda & Remaja, serta Persekutuan Doa.
                    </p>

                    <ul class="maranatha-circle-buttons-list">
                        <li><a href="#kegiatan-terkini" class="btn-maranatha-dark-sec">Jadwal Ibadah Lengkap</a></li>
                        <li><a href="{{ route('public.offering.index') }}" class="btn-maranatha-accent">Persembahan Online</a></li>
                    </ul>

                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- SEKSI 6: PERSEKUTUAN RUMAH BERSAMA (Parallax 100vh - Dark Contrast Overlay) -->
        <!-- ================================================================= -->
        <section id="profil" class="maranatha-home-section" style="background-image: url('{{ $profileBg }}');">
            <!-- Overlay Gelap Elegan: Memastikan Teks & Visi 100% Kontras & Terbaca Jelas -->
            <div class="absolute inset-0 bg-[#081e18]/85 pointer-events-none"></div>

            <div class="maranatha-home-section-inner">
                <div class="maranatha-home-section-content text-white">
                    
                    <span class="text-xs uppercase tracking-[0.2em] font-semibold text-gksbs-leaf-light block mb-2">
                        Eklesiologi Sinode GKSBS
                    </span>

                    <h2 class="text-white">
                        Persekutuan Rumah Bersama
                    </h2>

                    <blockquote class="font-heading text-xl sm:text-2xl leading-relaxed italic text-white/95 my-5 max-w-2xl mx-auto">
                        “{{ $setting->vision ?? 'Terwujudnya jemaat yang mandiri, misioner, berwawasan oikumenis, serta menjadi berkat nyata bagi sesama ciptaan Tuhan.' }}”
                    </blockquote>

                    <p class="text-white/80 text-sm sm:text-base font-light">
                        {{ $setting->about_summary ?? 'GKSBS Filadelfia adalah persekutuan jemaat yang berpusat pada Kristus, melayani warga jemaat di wilayah Lampung Tengah dan sekitarnya melalui jemaat induk dan pos-pos pelayanan kelompok jemaat secara terpadu dan guyub.' }}
                    </p>

                    <ul class="maranatha-circle-buttons-list">
                        <li><a href="#kegiatan-terkini" class="btn-maranatha-dark-sec">Rincian Misi Pelayanan</a></li>
                        <li><a href="#kontak" class="btn-maranatha-dark-sec">Sejarah & Kontak</a></li>
                    </ul>

                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- SEKSI 7: LAYANAN JEMAAT & MAJELIS (Parallax 100vh) -->
        <!-- ================================================================= -->
        <section class="maranatha-home-section" style="background-image: url('{{ $portalBg }}');">
            <div class="absolute inset-0 bg-[#071922]/88 pointer-events-none"></div>

            <div class="maranatha-home-section-inner">
                <div class="maranatha-home-section-content text-white">
                    
                    <span class="text-xs uppercase tracking-[0.2em] font-semibold text-gksbs-sky-light block mb-2">
                        Sistem Informasi Pelayanan
                    </span>

                    <h2 class="text-white">
                        Portal Jemaat & Area Majelis
                    </h2>

                    <p class="text-white/90">
                        Akses mandiri data keanggotaan dan sakramen bagi warga jemaat terdaftar — serta area tata kelola administrasi dan perbendaharaan bagi majelis jemaat.
                    </p>

                    <ul class="maranatha-circle-buttons-list">
                        <li><a href="{{ route('portal.login') }}" class="btn-maranatha-dark-sec">Portal Jemaat</a></li>
                        <li><a href="{{ url('/admin/login') }}" class="btn-maranatha-dark-sec">Area Majelis</a></li>
                    </ul>

                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- SEKSI 8: RINCIAN JADWAL & WARTA (Layout 2 Kolom Bersih) -->
        <!-- ================================================================= -->
        <section id="kegiatan-terkini" class="bg-white py-20 border-t border-slate-200">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-14">
                    
                    <!-- Jadwal Liturgi -->
                    <div>
                        <div class="border-b border-slate-200 pb-3 mb-6 flex items-center justify-between">
                            <h2 class="font-heading text-2xl font-bold text-slate-900">Jadwal Persekutuan</h2>
                            <span class="text-xs uppercase tracking-widest text-gksbs-forest font-semibold">Liturgi</span>
                        </div>

                        <div class="space-y-5">
                            @if(!empty($setting->worship_schedules) && is_array($setting->worship_schedules))
                                @foreach($setting->worship_schedules as $sched)
                                    <div class="border-b border-slate-100 pb-4">
                                        <span class="text-xs font-semibold uppercase tracking-wider text-gksbs-ocean">
                                            {{ $sched['day'] ?? 'Minggu' }} · {{ $sched['time'] ?? '08:30 WIB' }}
                                        </span>
                                        <h3 class="font-heading text-lg font-bold text-slate-900 mt-1">
                                            {{ $sched['name'] ?? 'Ibadah Raya' }}
                                        </h3>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            {{ $sched['location'] ?? 'Gedung Gereja Utama' }}
                                        </p>
                                    </div>
                                @endforeach
                            @else
                                <div class="border-b border-slate-100 pb-4">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-gksbs-ocean">Setiap Minggu · 08:30 WIB</span>
                                    <h3 class="font-heading text-lg font-bold text-slate-900 mt-1">Ibadah Raya Minggu Induk</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Gedung Gereja Utama Candimas</p>
                                </div>
                                <div class="border-b border-slate-100 pb-4">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-gksbs-ocean">Setiap Minggu · 08:30 WIB</span>
                                    <h3 class="font-heading text-lg font-bold text-slate-900 mt-1">Kebaktian Sekolah Minggu Anak</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Gedung Serbaguna</p>
                                </div>
                                <div class="border-b border-slate-100 pb-4">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-gksbs-ocean">Setiap Sabtu · 17:00 WIB</span>
                                    <h3 class="font-heading text-lg font-bold text-slate-900 mt-1">Ibadah Pemuda & Remaja</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Ruang Persekutuan Pemuda</p>
                                </div>
                                <div class="border-b border-slate-100 pb-4">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-gksbs-ocean">Setiap Rabu · 18:30 WIB</span>
                                    <h3 class="font-heading text-lg font-bold text-slate-900 mt-1">Persekutuan Doa & PA</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Ruang Konsistori</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Warta Mingguan -->
                    <div>
                        <div class="border-b border-slate-200 pb-3 mb-6 flex items-center justify-between">
                            <h2 class="font-heading text-2xl font-bold text-slate-900">Warta Jemaat Publik</h2>
                            <span class="text-xs uppercase tracking-widest text-gksbs-leaf font-semibold">Publikasi</span>
                        </div>

                        <div class="space-y-5">
                            @foreach($churches as $c)
                                <div class="border-b border-slate-100 pb-4">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-gksbs-forest">
                                        Edisi Mingguan · {{ $c->code }}
                                    </span>
                                    <h3 class="font-heading text-lg font-bold text-slate-900 mt-1">
                                        <a href="{{ route('public.warta.index', $c->code) }}" class="hover:text-gksbs-ocean transition">
                                            Warta Jemaat {{ $c->name }}
                                        </a>
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        {{ $c->address ?? 'Wilayah Pelayanan GKSBS Filadelfia' }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- ================================================================= -->
        <!-- SEKSI 9: KONTAK & IDENTITAS SINODAL GKSBS -->
        <!-- ================================================================= -->
        <section id="kontak" class="bg-[#f8faf9] py-16 border-t border-slate-200">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-12 gap-10 items-center">
                    
                    <div class="md:col-span-6 space-y-3">
                        <span class="text-xs uppercase tracking-wider font-bold text-gksbs-forest block">
                            Informasi & Sekretariat
                        </span>
                        <h2 class="font-heading text-2xl sm:text-3xl text-slate-900 font-bold leading-snug">
                            Hubungi Sekretariat Gereja
                        </h2>
                        <div class="w-10 h-0.5 bg-gksbs-forest"></div>
                        <div class="space-y-1.5 text-sm text-slate-600 pt-1">
                            <p><strong>Alamat:</strong> {{ $setting->contact_address ?? 'Jl. Gereja Filadelfia No. 01, Kel. Candimas, Kec. Natar, Lampung Selatan' }}</p>
                            <p><strong>Telepon / WA:</strong> {{ $setting->contact_phone ?? '0812-7200-1234' }}</p>
                            <p><strong>Email:</strong> {{ $setting->contact_email ?? 'sekretariat@gksbs-filadelfia.org' }}</p>
                        </div>
                        <div class="pt-2">
                            <a href="{{ $setting->contact_maps_url ?: 'https://maps.google.com/?q=' . urlencode($churchName) }}" 
                               target="_blank" rel="noopener noreferrer" 
                               class="inline-flex items-center justify-center px-5 py-2.5 rounded-full bg-gksbs-forest hover:bg-gksbs-ocean text-white text-xs font-bold uppercase tracking-wider transition">
                                Petunjuk Arah di Google Maps &rarr;
                            </a>
                        </div>
                    </div>

                    <div class="md:col-span-6 bg-white p-7 rounded-2xl border border-slate-200 space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 p-1 rounded-full bg-gksbs-forest flex items-center justify-center flex-shrink-0">
                                <svg viewBox="0 0 100 100" class="w-7 h-7" fill="none">
                                    <path d="M50 8 C46 22 42 34 50 48 C58 34 54 22 50 8Z" fill="#22c55e"/>
                                    <path d="M38 16 C30 27 30 38 43 47 C43 33 42 24 38 16Z" fill="#4ade80"/>
                                    <path d="M62 16 C70 27 70 38 57 47 C57 33 58 24 62 16Z" fill="#4ade80"/>
                                    <path d="M26 28 C16 38 20 50 36 52 C34 38 31 31 26 28Z" fill="#15803d"/>
                                    <path d="M74 28 C84 38 80 50 64 52 C66 38 69 31 74 28Z" fill="#15803d"/>
                                    <circle cx="50" cy="50" r="3.5" fill="#ffffff"/>
                                    <path d="M20 68 Q50 64 80 68" stroke="#38bdf8" stroke-width="3" stroke-linecap="round"/>
                                    <path d="M16 75 Q50 71 84 75" stroke="#0284c7" stroke-width="3" stroke-linecap="round"/>
                                    <path d="M20 82 Q50 78 80 82" stroke="#0369a1" stroke-width="3" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-heading text-base font-bold text-slate-900 leading-tight">Sinode Gereja Kristen Sumatera Bagian Selatan</h3>
                                <p class="text-[11px] uppercase tracking-wider text-gksbs-forest font-semibold">Anggota PGI (No. 58)</p>
                            </div>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Simbol <strong>tujuh helai daun cengkeh warna hijau</strong> melambangkan 7 klasis pendiri pada tahun 1987, dan <strong>empat garis biru</strong> melambangkan keberadaan pelayanan di empat provinsi Sumbagsel (Lampung, Sumatera Selatan, Bengkulu, dan Jambi).
                        </p>
                    </div>

                </div>

            </div>
        </section>

    </main>

    <!-- ================================================================= -->
    <!-- 10. FOOTER (Soli Deo Gloria) -->
    <!-- ================================================================= -->
    <footer class="bg-gksbs-forest-dark text-white/70 text-xs border-t border-white/10 py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
                
                <div>
                    <span class="font-heading text-base text-white font-normal block mb-0.5">
                        {{ $churchName }}
                    </span>
                    <p class="text-white/50">
                        &copy; {{ date('Y') }} {{ $churchName }}. Pelayanan untuk kemuliaan nama Tuhan Yesus Kristus.
                    </p>
                </div>

                <div class="flex items-center space-x-5 font-medium uppercase tracking-wider text-[11px]">
                    <a href="#beranda" class="text-white/70 hover:text-white transition">Beranda</a>
                    <a href="#pos-pelayanan" class="text-white/70 hover:text-white transition">Pos Pelayanan</a>
                    <a href="{{ route('public.offering.index') }}" class="text-gksbs-leaf-light hover:text-white transition">Persembahan</a>
                    <a href="{{ route('portal.login') }}" class="text-gksbs-sky-light hover:text-white transition">Portal Jemaat</a>
                    <a href="{{ url('/admin/login') }}" class="text-white hover:text-white transition">Area Majelis</a>
                </div>

                <div class="font-heading text-sm text-white/40 italic">
                    Soli Deo Gloria
                </div>

            </div>
        </div>
    </footer>

    <!-- JavaScript Header scroll listener & Mobile Drawer -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const header = document.getElementById('maranatha-header-top');
            const toggle = document.getElementById('mobile-toggle');
            const menu = document.getElementById('mobile-menu');

            function updateHeader() {
                const scrollPos = window.pageYOffset || document.documentElement.scrollTop || window.scrollY || 0;
                if (scrollPos > 40) {
                    header.classList.add('scrolled');
                    header.classList.remove('not-scrolled');
                } else {
                    header.classList.remove('scrolled');
                    header.classList.add('not-scrolled');
                }
            }

            window.addEventListener('scroll', updateHeader, { passive: true });
            updateHeader();

            if (toggle && menu) {
                toggle.addEventListener('click', function () {
                    menu.classList.toggle('hidden');
                });
                menu.querySelectorAll('a').forEach(a => {
                    a.addEventListener('click', () => menu.classList.add('hidden'));
                });
            }
        });
    </script>
</body>
</html>
