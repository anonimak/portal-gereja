@extends('portal.layout')

@section('title', 'Data Diri Anggota')

@section('content')
<div class="space-y-6 sm:space-y-8 w-full">

    <!-- Header Profil Anggota -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center space-x-4 sm:space-x-5">
            <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl bg-gksbs-forest/10 border border-gksbs-forest/20 flex items-center justify-center text-gksbs-forest font-heading font-bold text-2xl sm:text-3xl flex-shrink-0 shadow-inner">
                {{ strtoupper(substr($member?->full_name ?? $user->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="font-heading text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                        {{ $member?->full_name ?? $user->name }}
                    </h1>
                    @if ($member)
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider {{ $member->status === 'aktif' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                            {{ $member->status }}
                        </span>
                    @endif
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1 flex items-center gap-1.5 flex-wrap">
                    <span class="font-medium text-slate-700">{{ $user->church?->name ?? 'GKSBS' }}</span>
                    <span class="text-slate-300">&bull;</span>
                    <span>{{ $user->email }}</span>
                </p>
                @if ($member?->id_card_number)
                    <p class="text-xs font-mono text-slate-400 mt-0.5">NIK: {{ $member->id_card_number }}</p>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
            <a href="{{ route('portal.events') }}" class="px-4 py-2 rounded-full bg-slate-50 hover:bg-gksbs-forest hover:text-white text-slate-700 font-bold text-xs uppercase tracking-wider transition border border-slate-200">
                Jadwal Ibadah →
            </a>
            <a href="{{ route('portal.warta') }}" class="px-4 py-2 rounded-full bg-slate-50 hover:bg-gksbs-forest hover:text-white text-slate-700 font-bold text-xs uppercase tracking-wider transition border border-slate-200">
                Warta Jemaat →
            </a>
            <a href="{{ route('portal.offerings') }}" class="px-4 py-2 rounded-full bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-800 font-bold text-xs uppercase tracking-wider transition border border-emerald-200">
                Persembahan →
            </a>
        </div>
    </div>

    @if (! $member)
        <div class="bg-amber-50 border border-amber-200/80 rounded-3xl p-6 sm:p-8 text-amber-900 text-xs sm:text-sm shadow-xs">
            <div class="flex items-center gap-2 font-heading font-bold text-base sm:text-lg mb-2 text-amber-950">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>Akun Pengguna Belum Ditautkan ke Data Jemaat</span>
            </div>
            <p class="leading-relaxed text-amber-800/90">
                Akun portal Anda belum terhubung dengan nomor induk kependudukan atau data induk jemaat di sistem gereja. Silakan hubungi Majelis Jemaat atau Sekretariat Gereja untuk memverifikasi dan menautkan data keanggotaan Anda.
            </p>
        </div>
    @else
        <!-- Grid 2 Kolom: Data Pribadi & Data Keluarga -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
            
            <!-- Card Data Pribadi -->
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs">
                <div class="flex items-center space-x-2.5 pb-4 mb-4 border-b border-slate-100">
                    <div class="h-8 w-8 rounded-xl bg-gksbs-forest/10 flex items-center justify-center text-gksbs-forest">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <h2 class="font-heading text-lg font-bold text-slate-900">Data Pribadi</h2>
                </div>
                
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3.5 text-xs">
                    <div>
                        <dt class="font-semibold text-slate-400 uppercase tracking-wider text-[10px]">Nama Lengkap</dt>
                        <dd class="font-bold text-slate-900 mt-0.5 text-sm">{{ $member->full_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-400 uppercase tracking-wider text-[10px]">No. Identitas (NIK)</dt>
                        <dd class="font-bold font-mono text-slate-900 mt-0.5 text-sm">{{ $member->id_card_number ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-400 uppercase tracking-wider text-[10px]">Jenis Kelamin</dt>
                        <dd class="font-bold text-slate-800 mt-0.5">
                            {{ $member->gender === 'm' ? 'Laki-laki' : ($member->gender === 'f' ? 'Perempuan' : '-') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-400 uppercase tracking-wider text-[10px]">Hubungan Keluarga</dt>
                        <dd class="font-bold text-slate-800 mt-0.5 capitalize">
                            {{ str_replace('_', ' ', $member->family_relation) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-400 uppercase tracking-wider text-[10px]">Tempat Lahir</dt>
                        <dd class="font-bold text-slate-800 mt-0.5">{{ $member->birth_place ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-400 uppercase tracking-wider text-[10px]">Tanggal Lahir</dt>
                        <dd class="font-bold text-slate-800 mt-0.5">
                            @if ($member->birth_date)
                                {{ $member->birth_date->locale('id')->translatedFormat('d F Y') }}
                                <span class="text-slate-400 font-normal">({{ $member->birth_date->age }} tahun)</span>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-400 uppercase tracking-wider text-[10px]">Status Keanggotaan</dt>
                        <dd class="font-bold text-slate-800 mt-0.5 capitalize">{{ $member->status }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-400 uppercase tracking-wider text-[10px]">Gereja Lokal</dt>
                        <dd class="font-bold text-slate-800 mt-0.5">{{ $member->church?->name ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Card Data Keluarga -->
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs">
                <div class="flex items-center space-x-2.5 pb-4 mb-4 border-b border-slate-100">
                    <div class="h-8 w-8 rounded-xl bg-gksbs-forest/10 flex items-center justify-center text-gksbs-forest">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </div>
                    <h2 class="font-heading text-lg font-bold text-slate-900">Keluarga & Domisili</h2>
                </div>

                @if ($member->family)
                    <div class="space-y-4">
                        <div class="text-xs space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-slate-400 uppercase tracking-wider text-[10px]">No. Kartu Keluarga:</span>
                                <span class="font-mono font-bold text-slate-900 bg-slate-100 px-2.5 py-0.5 rounded-lg">{{ $member->family->family_number }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-slate-400 uppercase tracking-wider text-[10px]">Nama Keluarga:</span>
                                <span class="font-bold text-slate-800">{{ $member->family->name }}</span>
                            </div>
                            <div>
                                <span class="font-semibold text-slate-400 uppercase tracking-wider text-[10px] block mb-1">Alamat Domisili:</span>
                                <p class="text-slate-700 bg-[#fbfcfd] p-3 rounded-xl border border-slate-200/60 leading-relaxed text-xs">
                                    {{ $member->family->address }}
                                </p>
                            </div>
                        </div>

                        <!-- Daftar Anggota Keluarga -->
                        <div class="pt-2">
                            <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Anggota Keluarga Terdaftar</h3>
                            <div class="divide-y divide-slate-100 border border-slate-100 rounded-2xl overflow-hidden text-xs">
                                @foreach ($member->family->members as $fMember)
                                    <div class="p-3 flex items-center justify-between {{ $fMember->id === $member->id ? 'bg-gksbs-forest/5 font-bold text-gksbs-forest-deep' : 'bg-white hover:bg-slate-50' }}">
                                        <div>
                                            <p class="font-semibold text-slate-900">
                                                {{ $fMember->full_name }}
                                                @if ($fMember->id === $member->id)
                                                    <span class="ml-1 text-[10px] text-gksbs-forest font-bold bg-gksbs-forest/10 px-2 py-0.5 rounded-full">(Anda)</span>
                                                @endif
                                            </p>
                                            <p class="text-[11px] text-slate-400 mt-0.5">
                                                {{ $fMember->gender === 'm' ? 'L' : 'P' }} &bull; {{ $fMember->birth_date?->locale('id')->translatedFormat('d M Y') ?? '-' }}
                                            </p>
                                        </div>
                                        <span class="text-[11px] font-medium text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-full capitalize">
                                            {{ str_replace('_', ' ', $fMember->family_relation) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-xs text-slate-400 italic py-3">Data keluarga belum terdaftar di sistem.</p>
                @endif
            </div>
        </div>

        <!-- Card Riwayat Sakramen -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs">
            <div class="flex items-center space-x-2.5 pb-4 mb-4 border-b border-slate-100">
                <div class="h-8 w-8 rounded-xl bg-gksbs-forest/10 flex items-center justify-center text-gksbs-forest">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <h2 class="font-heading text-lg font-bold text-slate-900">Catatan Sakramen & Rohani</h2>
            </div>

            @if ($member->sacraments->isEmpty())
                <p class="text-xs text-slate-400 italic py-2">Belum ada riwayat sakramen yang tercatat.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($member->sacraments as $sacrament)
                        <div class="p-4 rounded-2xl border border-slate-200/70 bg-[#fbfcfd] space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 capitalize text-sm">
                                    {{ match($sacrament->type) {
                                        'baptis_anak' => 'Baptis Anak',
                                        'baptis_dewasa' => 'Baptis Dewasa',
                                        'sidi' => 'Peneguhan Sidi',
                                        'pernikahan' => 'Pernikahan Kudus',
                                        default => ucfirst(str_replace('_', ' ', $sacrament->type))
                                    } }}
                                </span>
                                <span class="text-[10px] text-gksbs-forest bg-gksbs-forest/10 border border-gksbs-forest/20 font-bold px-2.5 py-0.5 rounded-full">
                                    {{ $sacrament->sacrament_date?->format('d/m/Y') ?? 'Tercatat' }}
                                </span>
                            </div>

                            <div class="space-y-1 text-slate-600 pt-1">
                                @if ($sacrament->certificate_number)
                                    <p class="font-mono text-[11px] text-slate-500">No. Akta: <strong class="text-slate-700">{{ $sacrament->certificate_number }}</strong></p>
                                @endif
                                @if ($sacrament->official)
                                    <p class="text-[11px]">Dilayani: <span class="font-medium text-slate-800">{{ $sacrament->official->name }}</span></p>
                                @endif
                                @if ($sacrament->notes)
                                    <p class="text-[11px] text-slate-500 italic mt-1 leading-relaxed">{{ $sacrament->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Card Riwayat Kehadiran Ibadah -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                <div class="flex items-center space-x-2.5">
                    <div class="h-8 w-8 rounded-xl bg-gksbs-forest/10 flex items-center justify-center text-gksbs-forest">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    </div>
                    <h2 class="font-heading text-lg font-bold text-slate-900">Kehadiran Ibadah Terakhir</h2>
                </div>
                <span class="text-xs font-bold text-gksbs-forest bg-gksbs-forest/10 px-3 py-1 rounded-full border border-gksbs-forest/20">
                    Total: {{ $member->attendances->count() }} Kehadiran
                </span>
            </div>

            @if ($member->attendances->isEmpty())
                <p class="text-xs text-slate-400 italic py-2">Belum ada catatan kehadiran ibadah.</p>
            @else
                <div class="divide-y divide-slate-100 text-xs">
                    @foreach ($member->attendances->take(5) as $att)
                        <div class="py-3 flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">{{ $att->event?->title ?? 'Ibadah Jemaat' }}</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $att->event?->start_datetime?->locale('id')->translatedFormat('l, d F Y') }}</p>
                            </div>
                            <span class="px-3 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $att->status === 'checked_in' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-700' }}">
                                {{ $att->status === 'checked_in' ? 'Hadir' : ($att->status ?? 'Hadir') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
