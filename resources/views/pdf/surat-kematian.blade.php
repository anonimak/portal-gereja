<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Keterangan Kematian</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; line-height: 1.6; }
        .kop { text-align: center; border-bottom: 3px solid #1e3a5f; padding-bottom: 12px; margin-bottom: 24px; }
        .kop h1 { margin: 0; font-size: 20px; color: #1e3a5f; }
        .kop p { margin: 2px 0; font-size: 12px; }
        .judul { text-align: center; margin-bottom: 24px; }
        .judul h2 { margin: 0; font-size: 16px; text-decoration: underline; }
        .isi { margin-bottom: 24px; }
        .isi p { margin: 8px 0; }
        .ttd { margin-top: 48px; text-align: right; }
        .ttd p { margin: 2px 0; }
        .ttd .nama { font-weight: bold; margin-top: 72px; }
        @media print { body { margin: 20mm; } }
    </style>
</head>
<body>
    <div class="kop">
        <h1>{{ $churchName ?? 'GEREJA KRISTEN' }}</h1>
        <p>{{ $churchAddress ?? '' }}</p>
    </div>

    <div class="judul">
        <h2>SURAT KETERANGAN KEMATIAN</h2>
        <p>Nomor: {{ $certificateNumber ?? '-' }}</p>
    </div>

    <div class="isi">
        <p>Yang bertanda tangan di bawah ini, dengan ini menerangkan bahwa:</p>
        <table style="width:100%; border-collapse:collapse;">
            <tr><td style="width:160px;">Nama</td><td>: {{ $memberName ?? '-' }}</td></tr>
            <tr><td>Jenis Kelamin</td><td>: {{ $memberGender ?? '-' }}</td></tr>
            <tr><td>Tempat, Tgl Lahir</td><td>: {{ $memberBirthPlace ?? '-' }}{{ $memberBirthPlace ? ', ' : '' }}{{ $memberBirthDate ?? '-' }}</td></tr>
            <tr><td>Alamat</td><td>: {{ $memberAddress ?? '-' }}</td></tr>
            <tr><td>Tanggal Meninggal</td><td>: {{ $deathDate ?? '-' }}</td></tr>
            <tr><td>Tanggal Pemakaman</td><td>: {{ $burialDate ?? '-' }}</td></tr>
            <tr><td>Tempat Pemakaman</td><td>: {{ $burialLocation ?? '-' }}</td></tr>
        </table>
        <p style="margin-top:16px;">Telah dipanggil Bapa di Surga dan telah dikuburkan. Keluarga yang ditinggalkan
            agar kiranya dapat menerima dengan penuh penghiburan dan kekuatan dari Tuhan.</p>
        <p>Ibadah pemakaman dilayani oleh {{ $ministerName ?? 'Pendeta' }}.</p>
    </div>

    <div class="ttd">
        <p>{{ $churchName ?? '' }}, {{ $issuedAt ?? '' }}</p>
        <p>Hormat kami,</p>
        <p class="nama">{{ $ministerName ?? '' }}</p>
        <p>Pendeta</p>
    </div>
</body>
</html>
