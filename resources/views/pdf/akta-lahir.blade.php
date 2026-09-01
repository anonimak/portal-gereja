{{-- Akta Lahir (Fase 3B T5) — template dompdf/blade print, null-safe (AC-LC-08). --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Akta Lahir</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 12px; margin: 0; }
        .kop { text-align: center; border-bottom: 3px double #1e3a8a; padding-bottom: 10px; margin-bottom: 24px; }
        .kop h1 { margin: 0; font-size: 22px; color: #1e3a8a; letter-spacing: 1px; }
        .kop .nama { font-size: 18px; font-weight: bold; margin-top: 2px; }
        .kop .alamat { font-size: 11px; color: #4b5563; }
        .judul { text-align: center; margin: 28px 0; }
        .judul h2 { font-size: 20px; letter-spacing: 3px; color: #111827; margin: 0; }
        .judul .no { font-size: 13px; color: #374151; margin-top: 6px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table.data td { padding: 7px 6px; vertical-align: top; }
        table.data td.label { width: 38%; font-weight: 600; color: #374151; }
        table.data td.colon { width: 2%; }
        .ttd { margin-top: 56px; text-align: center; float: right; width: 240px; }
        .ttd .role { margin-top: 66px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #9ca3af; }
    </style>
</head>
<body>
    @php
        $member = $record->member ?? null;
        $church = $member?->church ?? null;
        $gender = $member?->gender === 'm' ? 'Laki-laki' : ($member?->gender === 'f' ? 'Perempuan' : '-');
        $issuedAt = $record->issued_at?->format('d F Y') ?? '-';
        $birthDate = $record->birth_date?->format('d F Y') ?? ($member?->birth_date?->format('d F Y') ?? '-');
        $birthPlace = $record->birth_place_full ?? $member?->birth_place ?? '-';
        $birthOrder = $record->birth_order !== null ? "Anak ke-{$record->birth_order}" : '-';
    @endphp

    <div class="kop">
        <h1>{{ $church?->name ?? 'GEREJA' }}</h1>
        <div class="alamat">{{ $church?->address ?? '' }}</div>
    </div>

    <div class="judul">
        <h2>AKTA LAHIR</h2>
        <div class="no">Nomor: {{ $record->certificate_number ?? '-' }}</div>
    </div>

    <table class="data">
        <tr><td class="label">Nama Anak</td><td class="colon">:</td><td>{{ $member?->full_name ?? '-' }}</td></tr>
        <tr><td class="label">Jenis Kelamin</td><td class="colon">:</td><td>{{ $gender }}</td></tr>
        <tr><td class="label">Tempat, Tanggal Lahir</td><td class="colon">:</td><td>{{ $birthPlace }}, {{ $birthDate }}</td></tr>
        <tr><td class="label">Anak Ke-</td><td class="colon">:</td><td>{{ $birthOrder }}</td></tr>
        <tr><td class="label">Nama Ayah</td><td class="colon">:</td><td>{{ $record->father_name ?? '-' }}</td></tr>
        <tr><td class="label">Nama Ibu</td><td class="colon">:</td><td>{{ $record->mother_name ?? '-' }}</td></tr>
        <tr><td class="label">Tanggal Terbit</td><td class="colon">:</td><td>{{ $issuedAt }}</td></tr>
        @if($record->notes)
            <tr><td class="label">Catatan</td><td class="colon">:</td><td>{{ $record->notes }}</td></tr>
        @endif
    </table>

    <div class="ttd">
        <div>{{ $church?->name ?? 'Gereja' }}, {{ $issuedAt }}</div>
        <div class="role">Ketua Majelis / Pendeta</div>
    </div>

    <div class="footer">Dokumen ini diterbitkan oleh {{ $church?->name ?? 'sistem portal-gereja' }}.</div>
</body>
</html>
