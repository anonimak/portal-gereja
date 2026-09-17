@extends('portal.layout')

@section('title', 'Persembahan Jemaat')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900">Persembahan & Donasi Digital</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Kanal persembahan syukur, perpuluhan, dan diakonia jemaat {{ $user->church?->name ?? 'Gereja' }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                100% Tercatat Otomatis
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- QRIS & Rekening Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Rekening & QRIS info -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-2">
                Rekening & QRIS Resmi Jemaat
            </h2>

            @if($user->church?->qris_url)
                <div class="text-center p-3 bg-slate-50 rounded-xl border border-slate-200 max-w-xs mx-auto">
                    <p class="text-[11px] font-bold text-slate-600 mb-2">QRIS Gereja</p>
                    <img src="{{ $user->church->qris_url }}" alt="QRIS" class="w-48 h-auto mx-auto rounded-lg shadow-xs">
                </div>
            @endif

            <div class="space-y-2 text-xs">
                @if(!empty($user->church?->bank_accounts) && is_array($user->church->bank_accounts))
                    @foreach($user->church->bank_accounts as $acc)
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="font-bold text-slate-800 block">{{ $acc['bank_name'] ?? 'Bank' }}</span>
                            <p class="font-mono text-sm font-black text-slate-900">{{ $acc['account_number'] ?? '-' }}</p>
                            <p class="text-slate-500">a.n. {{ $acc['account_holder'] ?? $user->church->name }}</p>
                        </div>
                    @endforeach
                @else
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="font-bold text-slate-800 block">Bank Mandiri / Bank Lampung</span>
                        <p class="font-mono text-sm font-black text-slate-900">380-00-1234567-8</p>
                        <p class="text-slate-500">a.n. Majelis Jemaat {{ $user->church?->name }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Form Persembahan Mandiri -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-2 mb-4">
                Formulir Persembahan Baru
            </h2>

            <form action="{{ route('portal.offerings.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kantong Kas Tujuan *</label>
                        <select name="fund_id" required class="w-full rounded-xl border-slate-200 p-2 text-xs">
                            @foreach($funds as $f)
                                <option value="{{ $f->id }}">{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kategori Persembahan *</label>
                        <select name="financial_category_id" required class="w-full rounded-xl border-slate-200 p-2 text-xs">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nominal (Rp) *</label>
                        <input type="number" name="amount" min="10000" step="1000" required placeholder="50000" class="w-full rounded-xl border-slate-200 p-2 text-xs font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Metode Pembayaran *</label>
                        <select name="payment_method" required class="w-full rounded-xl border-slate-200 p-2 text-xs">
                            <option value="qris">QRIS (Scan Barcode)</option>
                            <option value="bank_transfer">Transfer Bank</option>
                            <option value="va">Virtual Account</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Unggah Bukti Transfer (Opsional)</label>
                    <input type="file" name="proof" accept="image/*" class="w-full text-slate-500 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-slate-100">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Pokok Doa / Ucapan Syukur (Opsional)</label>
                    <textarea name="prayer_notes" rows="2" placeholder="Catatan permohonan doa..." class="w-full rounded-xl border-slate-200 p-2 text-xs"></textarea>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition shadow-xs">
                    Kirim Konfirmasi Persembahan
                </button>
            </form>
        </div>
    </div>

    <!-- Riwayat Persembahan Anggota -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">Riwayat Persembahan Anda</h2>
            <span class="text-xs text-slate-400">Total {{ $offerings->total() }} transaksi</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider font-bold text-slate-400 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3">Kode Ref</th>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Kantong Kas</th>
                        <th class="px-6 py-3">Kategori</th>
                        <th class="px-6 py-3">Jumlah</th>
                        <th class="px-6 py-3">Metode</th>
                        <th class="px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($offerings as $off)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-3.5 font-mono font-bold text-slate-900">{{ $off->reference_code }}</td>
                            <td class="px-6 py-3.5">{{ $off->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-3.5 font-semibold">{{ $off->fund?->name }}</td>
                            <td class="px-6 py-3.5">{{ $off->financialCategory?->name }}</td>
                            <td class="px-6 py-3.5 font-black text-emerald-700">Rp {{ number_format((int) $off->amount, 0, ',', '.') }}</td>
                            <td class="px-6 py-3.5 uppercase font-bold text-[10px]">{{ $off->payment_method }}</td>
                            <td class="px-6 py-3.5">
                                @if($off->status === 'confirmed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        Dikonfirmasi
                                    </span>
                                @elseif($off->status === 'rejected')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800" title="{{ $off->rejection_reason }}">
                                        Ditolak
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                        Menunggu
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400 italic">
                                Belum ada riwayat persembahan online yang tercatat.
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
