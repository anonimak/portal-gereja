<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle ?? 'Dokumen Sidi' }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1f2937; line-height: 1.6; }
        .header { text-align: center; border-bottom: 3px double #111827; padding-bottom: 12px; margin-bottom: 24px; }
        .header h1 { font-size: 20px; margin: 0 0 4px; color: #111827; }
        .header .sub { font-size: 12px; color: #374151; }
        .title-doc { text-align: center; margin: 24px 0; }
        .title-doc h2 { font-size: 18px; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
        .title-doc p { font-size: 11px; color: #6b7280; margin: 4px 0 0; }
        .content { max-width: 620px; margin: 0 auto; }
        .content p { text-align: justify; margin: 12px 0; }
        .data-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .data-table td { padding: 6px 8px; border-bottom: 1px dotted #9ca3af; font-size: 12px; }
        .data-table td.label { width: 200px; color: #6b7280; }
        .data-table td.value { font-weight: 600; }
        .signature { margin-top: 48px; text-align: right; }
        .signature .line { width: 220px; border-top: 1px solid #111827; margin-top: 60px; padding-top: 6px; text-align: center; font-size: 11px; }
        .footer { margin-top: 32px; font-size: 9px; color: #6b7280; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $churchName ?? 'Portal Gereja' }}</h1>
        @if (!empty($churchAddress))<div class="sub">{{ $churchAddress }}</div>@endif
    </div>

    <div class="title-doc">
        <h2>{{ $documentTitle ?? 'Dokumen Sidi' }}</h2>
        <p>Nomor Sertifikat: {{ $certificateNumber ?? '-' }}</p>
    </div>

    <div class="content">
        <p>Yang bertanda tangan di bawah ini, menerangkan bahwa:</p>
        <table class="data-table">
            <tr><td class="label">Nama</td><td class="value">{{ $memberName ?? '-' }}</td></tr>
            <tr><td class="label">Jenis Kelamin</td><td class="value">{{ $gender ?? '-' }}</td></tr>
            <tr><td class="label">Tempat, Tanggal Lahir</td><td class="value">{{ $birthPlace ?? '-' }}, {{ $birthDate ?? '-' }}</td></tr>
            <tr><td class="label">Nama Ayah</td><td class="value">{{ $fatherName ?? '-' }}</td></tr>
            <tr><td class="label">Nama Ibu</td><td class="value">{{ $motherName ?? '-' }}</td></tr>
            <tr><td class="label">Tanggal {{ $recordTypeLabel ?? 'Sidi' }}</td><td class="value">{{ $sacramentDate ?? '-' }}</td></tr>
            @if (!empty($programName))<tr><td class="label">Program Bimbingan</td><td class="value">{{ $programName }}</td></tr>@endif
            <tr><td class="label">Tanggal Diterbitkan</td><td class="value">{{ $issuedAt ?? '-' }}</td></tr>
        </table>
        <p>Telah menerima {{ $documentTitle ?? 'Sidi' }} sesuai dengan tata ibadah Gereja {{ $churchName ?? '' }}.</p>
    </div>

    <div class="signature">
        <div>{{ $churchLocation ?? '' }}, {{ $issuedAt ?? '' }}</div>
        <div class="line">{{ $ministerName ?? 'Pendeta' }}</div>
    </div>

    <div class="footer">Dokumen ini diterbitkan oleh {{ $churchName ?? '' }}. {{ ! empty($certificateNumber) ? 'No. '.$certificateNumber : '' }}</div>
</body>
</html>
