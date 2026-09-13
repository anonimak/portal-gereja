@extends('portal.layout')

@section('title', 'Data Diri Anggota')

@section('content')
<div class="space-y-6">
    <!-- Header Profil -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="h-16 w-16 rounded-2xl bg-amber-100 border border-amber-200 flex items-center justify-center text-amber-700 font-extrabold text-2xl shadow-inner">
                {{ strtoupper(substr($member?->full_name ?? $user->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900">{{ $member?->full_name ?? $user->name }}</h1>
                    @if ($member)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $member->status === 'aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                            {{ $member->status }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $user->church?->name ?? 'Gereja' }} &bull; {{ $user->email }}
                </p>
                @if ($member?->id_card_number)
                    <p class="text-xs font-mono text-slate-400 mt-0.5">NIK: {{ $member->id_card_number }}</p>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('portal.events') }}" class="px-4 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold text-xs transition border border-amber-200">
                Lihat Jadwal Ibadah →
            </a>
            <a href="{{ route('portal.warta') }}" class="px-4 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold text-xs transition border border-slate-200">
                Warta Jemaat →
            </a>
        </div>
    </div>

    @if (! $member)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-amber-800 text-sm">
            <h2 class="font-bold text-base mb-1">Akun belum ditautkan dengan data jemaat</h2>
            <p>Akun pengguna Anda saat ini belum dihubungkan dengan data keanggotaan jemaat di sistem. Silakan hubungi pengurus atau sekretariat gereja untuk melakukan penautan data diri.</p>
        </div>
    @else
        <!-- Grid 2 Kolom: Data Pribadi & Data Keluarga -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Card Data Pribadi -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <div class="flex items-center space-x-2 pb-4 mb-4 border-b border-slate-100">
                    <span class="text-lg">👤</span>
                    <h2 class="text-base font-bold text-slate-900">Data Pribadi</h2>
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 text-xs">
                    <div>
                        <dt class="font-medium text-slate-400 uppercase tracking-wider">Nama Lengkap</dt>
                        <dd class="font-bold text-slate-900 mt-0.5">{{ $member->full_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-400 uppercase tracking-wider">No. Identitas (NIK)</dt>
                        <dd class="font-bold font-mono text-slate-900 mt-0.5">{{ $member->id_card_number ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-400 uppercase tracking-wider">Jenis Kelamin</dt>
                        <dd class="font-bold text-slate-900 mt-0.5">
                            {{ $member->gender === 'm' ? 'Laki-laki' : ($member->gender === 'f' ? 'Perempuan' : '-') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-400 uppercase tracking-wider">Hubungan Keluarga</dt>
                        <dd class="font-bold text-slate-900 mt-0.5 capitalize">
                            {{ str_replace('_', ' ', $member->family_relation) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-400 uppercase tracking-wider">Tempat Lahir</dt>
                        <dd class="font-bold text-slate-900 mt-0.5">{{ $member->birth_place ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-400 uppercase tracking-wider">Tanggal Lahir</dt>
                        <dd class="font-bold text-slate-900 mt-0.5">
                            @if ($member->birth_date)
                                {{ $member->birth_date->locale('id')->translatedFormat('d F Y') }}
                                <span class="text-slate-400 font-normal">({{ $member->birth_date->age }} th)</span>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-400 uppercase tracking-wider">Status Keanggotaan</dt>
                        <dd class="font-bold text-slate-900 mt-0.5 capitalize">{{ $member->status }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-400 uppercase tracking-wider">Gereja Lokal</dt>
                        <dd class="font-bold text-slate-900 mt-0.5">{{ $member->church?->name ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Card Data Keluarga -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <div class="flex items-center space-x-2 pb-4 mb-4 border-b border-slate-100">
                    <span class="text-lg">🏡</span>
                    <h2 class="text-base font-bold text-slate-900">Keluarga & Tempat Tinggal</h2>
                </div>

                @if ($member->family)
                    <div class="space-y-4">
                        <div class="text-xs space-y-1">
                            <div class="flex justify-between">
                                <span class="text-slate-400">No. Kartu Keluarga (KK):</span>
                                <span class="font-mono font-bold text-slate-800">{{ $member->family->family_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Nama Keluarga:</span>
                                <span class="font-bold text-slate-800">{{ $member->family->name }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block mb-0.5">Alamat Domisili:</span>
                                <p class="text-slate-700 bg-slate-50 p-2.5 rounded-xl border border-slate-100 leading-relaxed">
                                    {{ $member->family->address }}
                                </p>
                            </div>
                        </div>

                        <!-- Daftar Anggota Keluarga -->
                        <div class="pt-2">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Anggota Keluarga Terdaftar</h3>
                            <div class="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden text-xs">
                                @foreach ($member->family->members as $fMember)
                                    <div class="p-2.5 flex items-center justify-between {{ $fMember->id === $member->id ? 'bg-amber-50/60 font-bold text-amber-900' : 'bg-white hover:bg-slate-50' }}">
                                        <div>
                                            <p class="font-medium text-slate-900">
                                                {{ $fMember->full_name }}
                                                @if ($fMember->id === $member->id)
                                                    <span class="ml-1 text-[10px] text-amber-700 font-bold">(Anda)</span>
                                                @endif
                                            </p>
                                            <p class="text-[11px] text-slate-400">
                                                {{ $fMember->gender === 'm' ? 'L' : 'P' }} &bull; {{ $fMember->birth_date?->locale('id')->translatedFormat('d M Y') ?? '-' }}
                                            </p>
                                        </div>
                                        <span class="text-[11px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full capitalize">
                                            {{ str_replace('_', ' ', $fMember->family_relation) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-xs text-slate-400 italic">Data keluarga belum terdaftar.</p>
                @endif
            </div>
        </div>

        <!-- Card Riwayat Sakramen -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-center space-x-2 pb-4 mb-4 border-b border-slate-100">
                <span class="text-lg">📜</span>
                <h2 class="text-base font-bold text-slate-900">Catatan Sakramen & Rohani</h2>
            </div>

            @if ($member->sacraments->isEmpty())
                <p class="text-xs text-slate-400 italic py-2">Belum ada riwayat sakramen yang tercatat.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($member->sacraments as $sacrament)
                        <div class="p-4 rounded-xl border border-slate-100 bg-slate-50/50 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 capitalize">
                                    {{ match($sacrament->type) {
                                        'baptis_anak' => 'Baptis Anak',
                                        'baptis_dewasa' => 'Baptis Dewasa',
                                        'sidi' => 'Peneguhan Sidi',
                                        'pernikahan' => 'Pernikahan Kudus',
                                        default => ucfirst(str_replace('_', ' ', $sacrament->type))
                                    } }}
                                </span>
                                <span class="text-[10px] text-amber-700 bg-amber-100 font-bold px-2 py-0.5 rounded-full">
                                    {{ $sacrament->sacrament_date?->format('d/m/Y') ?? 'Tercatat' }}
                                </span>
                            </div>

                            <div class="space-y-1 text-slate-600">
                                @if ($sacrament->certificate_number)
                                    <p class="font-mono text-[11px] text-slate-500">No: {{ $sacrament->certificate_number }}</p>
                                @endif
                                @if ($sacrament->official)
                                    <p class="text-[11px]">Dilayani: <span class="font-medium text-slate-800">{{ $sacrament->official->name }}</span></p>
                                @endif
                                @if ($sacrament->notes)
                                    <p class="text-[11px] text-slate-500 italic mt-1">{{ $sacrament->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Card Riwayat Kehadiran Ibadah -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <span class="text-lg">🗓️</span>
                    <h2 class="text-base font-bold text-slate-900">Kehadiran Ibadah</h2>
                </div>
                <span class="text-xs font-bold text-amber-700 bg-amber-50 px-3 py-1 rounded-full border border-amber-200">
                    Total: {{ $member->attendances->count() }} Kehadiran
                </span>
            </div>

            @if ($member->attendances->isEmpty())
                <p class="text-xs text-slate-400 italic py-2">Belum ada catatan kehadiran ibadah.</p>
            @else
                <div class="divide-y divide-slate-100 text-xs">
                    @foreach ($member->attendances->take(5) as $att)
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $att->event?->title ?? 'Ibadah Gereja' }}</p>
                                <p class="text-[11px] text-slate-400">{{ $att->event?->start_datetime?->locale('id')->translatedFormat('l, d F Y') }}</p>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $att->status === 'checked_in' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                {{ $att->status ?? 'Hadir' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
