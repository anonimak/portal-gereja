@extends('portal.layout')

@section('title', 'Persembahan Jemaat')

@section('content')
<div class="space-y-6 sm:space-y-8">
    <!-- Header Page -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-gksbs-forest block">
                Penatalayanan & Diakonia
            </span>
            <h1 class="font-heading text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                Persembahan & Donasi Jemaat
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-xl">
                Kanal persembahan syukur, persepuluhan, pembangunan, dan aksi kasih diakonia jemaat {{ $user->church?->name ?? 'GKSBS' }}
            </p>
            <p class="text-xs text-gksbs-forest/80 italic font-heading mt-2">
                &ldquo;Hendaklah masing-masing memberikan menurut kerelaan hatinya, jangan dengan sedih hati atau karena paksaan, sebab Allah mengasihi orang yang memberi dengan sukacita.&rdquo; (2 Korintus 9:7)
            </p>
        </div>
        <div class="flex items-center gap-2 self-start md:self-auto">
            <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                100% Tercatat Otomatis
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs sm:text-sm font-medium flex items-center gap-3 shadow-xs">
            <div class="h-7 w-7 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0 text-emerald-700">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- QRIS & Form Persembahan -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8">
        
        <!-- Left: Rekening & QRIS info (5 cols) -->
        <div class="lg:col-span-5 bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs space-y-5">
            <div class="flex items-center space-x-2.5 pb-3 border-b border-slate-100">
                <div class="h-8 w-8 rounded-xl bg-gksbs-forest/10 flex items-center justify-center text-gksbs-forest">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                </div>
                <h2 class="font-heading text-base font-bold text-slate-900">
                    Rekening & QRIS Resmi
                </h2>
            </div>

            @if($user->church?->qris_url)
                <div class="text-center p-4 bg-[#fbfcfd] rounded-2xl border border-slate-200 max-w-xs mx-auto">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block mb-2">QRIS Standar Indonesia</span>
                    <img src="{{ $user->church->qris_url }}" alt="QRIS Gereja" class="w-48 h-auto mx-auto rounded-xl shadow-xs">
                    <p class="text-[10px] text-slate-400 mt-2">Dapat dipindai melalui BCA, Mandiri, BRI, GoPay, OVO, Dana, dll.</p>
                </div>
            @endif

            <div class="space-y-3 text-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Transfer Rekening Gereja:</span>
                @if(!empty($user->church?->bank_accounts) && is_array($user->church->bank_accounts))
                    @foreach($user->church->bank_accounts as $acc)
                        <div class="p-3.5 rounded-2xl bg-[#fbfcfd] border border-slate-200/80 space-y-1">
                            <span class="font-bold text-slate-800 text-xs block">{{ $acc['bank_name'] ?? 'Bank' }}</span>
                            <p class="font-mono text-sm font-black text-gksbs-forest tracking-wide">{{ $acc['account_number'] ?? '-' }}</p>
                            <p class="text-slate-500 text-[11px]">a.n. {{ $acc['account_holder'] ?? $user->church->name }}</p>
                        </div>
                    @endforeach
                @else
                    <div class="p-3.5 rounded-2xl bg-[#fbfcfd] border border-slate-200/80 space-y-1">
                        <span class="font-bold text-slate-800 text-xs block">Bank Mandiri / Bank Lampung</span>
                        <p class="font-mono text-sm font-black text-gksbs-forest tracking-wide">380-00-1234567-8</p>
                        <p class="text-slate-500 text-[11px]">a.n. Majelis Jemaat {{ $user->church?->name ?? 'GKSBS' }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Form Persembahan Mandiri (7 cols) -->
        <div class="lg:col-span-7 bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs">
            <div class="flex items-center space-x-2.5 pb-3 border-b border-slate-100 mb-5">
                <div class="h-8 w-8 rounded-xl bg-gksbs-forest/10 flex items-center justify-center text-gksbs-forest">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <h2 class="font-heading text-base font-bold text-slate-900">
                    Formulir Konfirmasi Persembahan
                </h2>
            </div>

            <form action="{{ route('portal.offerings.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-bold uppercase tracking-wider text-[10px] text-slate-700 mb-1.5">Kantong Kas Tujuan *</label>
                        <select name="fund_id" required class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:ring-2 focus:ring-gksbs-forest focus:border-gksbs-forest transition bg-[#fbfcfd]">
                            @foreach($funds as $f)
                                <option value="{{ $f->id }}">{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold uppercase tracking-wider text-[10px] text-slate-700 mb-1.5">Kategori Persembahan *</label>
                        <select name="financial_category_id" required class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:ring-2 focus:ring-gksbs-forest focus:border-gksbs-forest transition bg-[#fbfcfd]">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-bold uppercase tracking-wider text-[10px] text-slate-700 mb-1.5">Nominal Persembahan (Rp) *</label>
                        <input type="number" name="amount" min="10000" step="1000" required placeholder="50000" class="w-full rounded-xl border border-slate-300 p-2.5 text-xs font-bold focus:ring-2 focus:ring-gksbs-forest focus:border-gksbs-forest transition bg-[#fbfcfd]">
                    </div>
                    <div>
                        <label class="block font-bold uppercase tracking-wider text-[10px] text-slate-700 mb-1.5">Metode Pembayaran *</label>
                        <select name="payment_method" required class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:ring-2 focus:ring-gksbs-forest focus:border-gksbs-forest transition bg-[#fbfcfd]">
                            <option value="qris">QRIS (Scan Barcode)</option>
                            <option value="bank_transfer">Transfer Rekening Bank</option>
                            <option value="va">Virtual Account (VA)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold uppercase tracking-wider text-[10px] text-slate-700 mb-1.5">Unggah Bukti Transfer / Resi (Opsional)</label>
                    <input type="file" name="proof" accept="image/*" class="w-full text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-gksbs-forest/10 file:text-gksbs-forest hover:file:bg-gksbs-forest/20 text-xs">
                </div>

                <div>
                    <label class="block font-bold uppercase tracking-wider text-[10px] text-slate-700 mb-1.5">Pokok Doa / Ucapan Syukur (Opsional)</label>
                    <textarea name="prayer_notes" rows="2" placeholder="Catatan permohonan doa atau wujud syukur..." class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:ring-2 focus:ring-gksbs-forest focus:border-gksbs-forest transition bg-[#fbfcfd]"></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3 px-4 rounded-full bg-gksbs-forest hover:bg-gksbs-forest-deep text-white font-bold text-xs uppercase tracking-wider transition shadow-sm hover:shadow-md flex items-center justify-center gap-2">
                        <span>Kirim Konfirmasi Persembahan</span>
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Riwayat Persembahan Anggota -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="font-heading text-base sm:text-lg font-bold text-slate-900">
                    Riwayat Persembahan Anda
                </h2>
                <span class="text-xs text-slate-400">Total {{ $offerings->total() }} transaksi tercatat</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-[#fbfcfd] text-[10px] uppercase tracking-wider font-bold text-slate-400 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Kode Ref</th>
                        <th class="px-6 py-3.5">Tanggal</th>
                        <th class="px-6 py-3.5">Kantong Kas</th>
                        <th class="px-6 py-3.5">Kategori</th>
                        <th class="px-6 py-3.5">Jumlah</th>
                        <th class="px-6 py-3.5">Metode</th>
                        <th class="px-6 py-3.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($offerings as $off)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-3.5 font-mono font-bold text-slate-900">{{ $off->reference_code }}</td>
                            <td class="px-6 py-3.5">{{ $off->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-3.5 font-semibold text-slate-800">{{ $off->fund?->name }}</td>
                            <td class="px-6 py-3.5">{{ $off->financialCategory?->name }}</td>
                            <td class="px-6 py-3.5 font-black text-emerald-700">Rp {{ number_format((int) $off->amount, 0, ',', '.') }}</td>
                            <td class="px-6 py-3.5 uppercase font-bold text-[10px] text-slate-600">{{ $off->payment_method }}</td>
                            <td class="px-6 py-3.5">
                                @if($off->status === 'confirmed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        Dikonfirmasi
                                    </span>
                                @elseif($off->status === 'rejected')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200" title="{{ $off->rejection_reason }}">
                                        Ditolak
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        Menunggu
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-400 italic">
                                Belum ada riwayat persembahan digital yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($offerings->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $offerings->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
