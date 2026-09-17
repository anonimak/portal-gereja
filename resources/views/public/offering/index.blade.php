<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Persembahan & Donasi Digital — {{ $selectedChurch?->name ?? 'GKSBS Filadelfia' }}</title>
    <meta name="description" content="Kanal persembahan digital resmi (QRIS & Transfer Bank) jemaat {{ $selectedChurch?->name ?? 'GKSBS Filadelfia' }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caudex:ital,wght@0,400;0,700;1,400&family=Raleway:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        heading: ['"Caudex"', 'Georgia', 'serif'],
                        sans: ['"Raleway"', 'Arial', 'sans-serif'],
                    },
                    colors: {
                        gksbs: {
                            forest: '#0f3d2e',
                            'forest-deep': '#09251c',
                            'forest-dark': '#061a13',
                            leaf: '#16a34a',
                            'leaf-light': '#22c55e',
                            ocean: '#0369a1',
                            sky: '#0284c7',
                            'sky-light': '#38bdf8',
                            teal: '#0d5d54',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Raleway', Arial, sans-serif;
            background-color: #f8faf9;
            color: #1e293b;
        }
        .font-heading {
            font-family: 'Caudex', Georgia, serif;
        }
    </style>
</head>
<body class="selection:bg-gksbs-ocean selection:text-white min-h-screen flex flex-col justify-between">

    <!-- Header -->
    <header class="bg-gksbs-forest-deep text-white border-b border-white/10 sticky top-0 z-40 shadow-sm">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <div class="h-9 w-9 p-1 rounded-full bg-black/25 border border-white/20 flex items-center justify-center">
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
                    <span class="font-heading text-lg font-bold block leading-none">{{ $selectedChurch?->name ?? 'GKSBS Filadelfia' }}</span>
                    <span class="text-[10px] text-white/75 uppercase tracking-widest">Kanal Persembahan Digital</span>
                </div>
            </a>

            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}" class="text-xs uppercase tracking-widest text-white/80 hover:text-white transition">
                    &larr; Kembali ke Beranda
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex-grow w-full">

        <!-- Title Banner -->
        <div class="text-center max-w-2xl mx-auto mb-10">
            <span class="text-xs uppercase tracking-[0.2em] font-bold text-gksbs-forest block mb-2">
                Pelayanan Penatalayanan & Diakonia
            </span>
            <h1 class="font-heading text-3xl sm:text-4xl font-light text-slate-900 mb-3">
                Persembahan & Donasi Jemaat
            </h1>
            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                "Hendaklah masing-masing memberikan menurut kerelaan hatinya, jangan dengan sedih hati atau karena paksaan, sebab Allah mengasihi orang yang memberi dengan sukacita." (2 Korintus 9:7)
            </p>
        </div>

        @if(session('offering_success'))
            @php $res = session('offering_success'); @endphp
            <div class="mb-10 bg-emerald-50 border border-emerald-200 rounded-2xl p-6 sm:p-8 max-w-xl mx-auto text-center shadow-sm">
                <div class="w-12 h-12 rounded-full bg-emerald-600 text-white flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h2 class="font-heading text-2xl font-bold text-emerald-950 mb-1">Persembahan Berhasil Dicatat</h2>
                <p class="text-xs text-emerald-800 mb-4">Terima kasih atas persembahan kasih dan dukungan bagi pelayanan gereja.</p>
                <div class="bg-white rounded-xl p-4 border border-emerald-100 text-left text-xs space-y-1.5 font-medium text-slate-700">
                    <p><span class="text-slate-400">Nomor Referensi:</span> <strong class="text-emerald-800 font-mono">{{ $res['reference_code'] }}</strong></p>
                    <p><span class="text-slate-400">Atas Nama:</span> {{ $res['donor_name'] }}</p>
                    <p><span class="text-slate-400">Jumlah:</span> <strong class="text-emerald-700 font-bold">Rp {{ number_format((int) $res['amount'], 0, ',', '.') }}</strong></p>
                    <p><span class="text-slate-400">Status:</span> <span class="inline-block px-2 py-0.5 rounded bg-amber-100 text-amber-900 font-bold text-[10px]">Menunggu Verifikasi Majelis</span></p>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            
            <!-- Left Column: QRIS & Rekening Resmi -->
            <div class="lg:col-span-5 space-y-6">
                
                <!-- QRIS Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm text-center">
                    <span class="text-xs uppercase tracking-widest font-bold text-gksbs-ocean block mb-2">QRIS Standar Nasional</span>
                    <h2 class="font-heading text-xl font-bold text-slate-900 mb-4">Pindai QRIS Resmi Gereja</h2>
                    
                    @if($selectedChurch?->qris_url)
                        <div class="max-w-xs mx-auto bg-white p-3 rounded-xl border border-slate-200 shadow-inner mb-4">
                            <img src="{{ $selectedChurch->qris_url }}" alt="QRIS {{ $selectedChurch->name }}" class="w-full h-auto object-contain mx-auto rounded-lg">
                        </div>
                    @else
                        <div class="max-w-xs mx-auto bg-slate-50 border-2 border-dashed border-slate-200 rounded-xl p-8 mb-4">
                            <svg class="w-12 h-12 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M4 8h4m-4 4h.01M4 16h4m-4 4h4m12-12h.01M4 4h4v4H4V4zm12 0h4v4h-4V4zM4 16h4v4H4v-4z"></path></svg>
                            <p class="text-xs text-slate-400 font-medium">QRIS statis belum diunggah oleh majelis gereja.</p>
                        </div>
                    @endif

                    <p class="text-[11px] text-slate-500 leading-normal">
                        Mendukung seluruh aplikasi e-wallet & mobile banking (BCA, Mandiri, BRI, BNI, GoPay, OVO, Dana, LinkAja).
                    </p>
                </div>

                <!-- Bank Accounts Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <span class="text-xs uppercase tracking-widest font-bold text-gksbs-forest block mb-2">Rekening Bank Resmi</span>
                    <h2 class="font-heading text-xl font-bold text-slate-900 mb-4">Transfer Rekening Jemaat</h2>

                    <div class="space-y-3">
                        @if(!empty($selectedChurch?->bank_accounts) && is_array($selectedChurch->bank_accounts))
                            @foreach($selectedChurch->bank_accounts as $acc)
                                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                                    <span class="text-xs font-bold text-gksbs-forest block">{{ $acc['bank_name'] ?? 'Bank' }}</span>
                                    <p class="font-mono text-base font-extrabold text-slate-900 tracking-wider my-0.5">{{ $acc['account_number'] ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">a.n. {{ $acc['account_holder'] ?? $selectedChurch->name }}</p>
                                </div>
                            @endforeach
                        @else
                            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                                <span class="text-xs font-bold text-gksbs-forest block">Bank Lampung / Bank Mandiri</span>
                                <p class="font-mono text-base font-extrabold text-slate-900 tracking-wider my-0.5">380-00-1234567-8</p>
                                <p class="text-xs text-slate-500">a.n. Majelis Jemaat {{ $selectedChurch?->name ?? 'GKSBS Filadelfia' }}</p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            <!-- Right Column: Confirmation Form -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
                    <span class="text-xs uppercase tracking-widest font-bold text-gksbs-forest block mb-1">Formulir Persembahan</span>
                    <h2 class="font-heading text-2xl font-bold text-slate-900 mb-6">Konfirmasi Persembahan Digital</h2>

                    <form action="{{ route('public.offering.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                        @csrf
                        
                        <input type="hidden" name="church_id" value="{{ $selectedChurch?->id }}">

                        <!-- Church selection if multiple -->
                        <div>
                            <label class="block text-xs uppercase tracking-wider font-bold text-slate-600 mb-1.5">Gereja / Kelompok Jemaat Tujuan</label>
                            <select onchange="window.location.href='/persembahan/' + this.value" class="w-full text-xs sm:text-sm rounded-xl border-slate-300 bg-slate-50 p-2.5 font-medium text-slate-800 focus:border-gksbs-forest focus:ring-gksbs-forest">
                                @foreach($churches as $c)
                                    <option value="{{ $c->code }}" {{ $selectedChurch?->id === $c->id ? 'selected' : '' }}>
                                        {{ $c->name }} ({{ $c->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Pos Kantong Kas & Kategori -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs uppercase tracking-wider font-bold text-slate-600 mb-1.5">Kantong Kas Tujuan *</label>
                                <select name="fund_id" required class="w-full text-xs sm:text-sm rounded-xl border-slate-300 p-2.5 font-medium text-slate-800 focus:border-gksbs-forest focus:ring-gksbs-forest">
                                    @foreach($funds as $f)
                                        <option value="{{ $f->id }}">{{ $f->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs uppercase tracking-wider font-bold text-slate-600 mb-1.5">Kategori Persembahan *</label>
                                <select name="financial_category_id" required class="w-full text-xs sm:text-sm rounded-xl border-slate-300 p-2.5 font-medium text-slate-800 focus:border-gksbs-forest focus:ring-gksbs-forest">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Nominal & Metode -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs uppercase tracking-wider font-bold text-slate-600 mb-1.5">Jumlah Persembahan (Rp) *</label>
                                <input type="number" name="amount" min="10000" step="1000" required placeholder="Contoh: 50000" class="w-full text-xs sm:text-sm rounded-xl border-slate-300 p-2.5 font-medium text-slate-800 focus:border-gksbs-forest focus:ring-gksbs-forest">
                                <span class="text-[10px] text-slate-400 mt-1 block">Minimal Rp 10.000</span>
                            </div>

                            <div>
                                <label class="block text-xs uppercase tracking-wider font-bold text-slate-600 mb-1.5">Metode Pembayaran *</label>
                                <select name="payment_method" required class="w-full text-xs sm:text-sm rounded-xl border-slate-300 p-2.5 font-medium text-slate-800 focus:border-gksbs-forest focus:ring-gksbs-forest">
                                    <option value="qris">QRIS (Scan Barcode)</option>
                                    <option value="bank_transfer">Transfer Rekening Bank</option>
                                    <option value="va">Virtual Account</option>
                                </select>
                            </div>
                        </div>

                        <!-- Donor Name & Contacts -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs uppercase tracking-wider font-bold text-slate-600 mb-1.5">Nama Jemaat / Donatur</label>
                                <input type="text" name="donor_name" placeholder="Hamba Allah (Boleh Kosong)" class="w-full text-xs sm:text-sm rounded-xl border-slate-300 p-2.5 font-medium text-slate-800 focus:border-gksbs-forest focus:ring-gksbs-forest">
                            </div>
                            <div>
                                <label class="block text-xs uppercase tracking-wider font-bold text-slate-600 mb-1.5">No. Telepon / WA</label>
                                <input type="text" name="donor_phone" placeholder="08xxxxxxxxxx" class="w-full text-xs sm:text-sm rounded-xl border-slate-300 p-2.5 font-medium text-slate-800 focus:border-gksbs-forest focus:ring-gksbs-forest">
                            </div>
                            <div>
                                <label class="block text-xs uppercase tracking-wider font-bold text-slate-600 mb-1.5">Email</label>
                                <input type="email" name="donor_email" placeholder="nama@email.com" class="w-full text-xs sm:text-sm rounded-xl border-slate-300 p-2.5 font-medium text-slate-800 focus:border-gksbs-forest focus:ring-gksbs-forest">
                            </div>
                        </div>

                        <!-- Bukti Transfer -->
                        <div>
                            <label class="block text-xs uppercase tracking-wider font-bold text-slate-600 mb-1.5">Unggah Bukti Transfer / Struk QRIS</label>
                            <input type="file" name="proof" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-gksbs-forest/10 file:text-gksbs-forest hover:file:bg-gksbs-forest/20">
                            <span class="text-[10px] text-slate-400 mt-1 block">Format gambar PNG/JPG/WEBP, maksimal 5MB.</span>
                        </div>

                        <!-- Pokok Doa / Catatan -->
                        <div>
                            <label class="block text-xs uppercase tracking-wider font-bold text-slate-600 mb-1.5">Pokok Doa / Ucapan Syukur (Opsional)</label>
                            <textarea name="prayer_notes" rows="3" placeholder="Tuliskan permohonan doa atau ucapan syukur yang ingin didoakan oleh majelis jemaat..." class="w-full text-xs sm:text-sm rounded-xl border-slate-300 p-2.5 font-medium text-slate-800 focus:border-gksbs-forest focus:ring-gksbs-forest"></textarea>
                        </div>

                        <div class="pt-3">
                            <button type="submit" class="w-full py-3.5 px-6 rounded-full bg-gksbs-forest hover:bg-gksbs-leaf text-white font-bold text-xs uppercase tracking-widest transition shadow-sm">
                                Kirim Konfirmasi Persembahan
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-gksbs-forest-dark text-white/60 text-xs py-8 border-t border-white/10 mt-16 text-center">
        <div class="max-w-6xl mx-auto px-4">
            <p>&copy; {{ date('Y') }} {{ $selectedChurch?->name ?? 'GKSBS Filadelfia' }}. Tata Kelola Perbendaharaan Presbiterial Sinodal.</p>
            <p class="font-heading text-white/40 italic text-sm mt-1">Soli Deo Gloria</p>
        </div>
    </footer>

</body>
</html>
