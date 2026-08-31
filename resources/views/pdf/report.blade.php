<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; line-height: 1.5; }
        .header { text-align: center; border-bottom: 3px double #111827; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { font-size: 20px; margin: 0 0 4px; color: #111827; }
        .header .sub { font-size: 12px; color: #374151; }
        h2 { font-size: 13px; margin: 18px 0 6px; color: #111827; border-bottom: 1px solid #d1d5db; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; font-size: 10px; }
        th { background: #f3f4f6; font-weight: 600; }
        .footer { margin-top: 28px; font-size: 9px; color: #6b7280; text-align: center; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $churchName ?? 'Portal Gereja' }}</h1>
        <div class="sub">{{ $title ?? '' }}</div>
        @isset($periodLabel)<div class="sub">{{ $periodLabel }}</div>@endisset
    </div>

    @forelse ($blocks ?? [] as $block)
        <h2>{{ $block['title'] }}</h2>
        <table>
            @if (!empty($block['headers']))
                <thead>
                    <tr>
                        @foreach ($block['headers'] as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody>
                @forelse ($block['rows'] ?? [] as $row)
                    <tr>
                        @foreach ((array) $row as $cell)
                            <td>{{ is_scalar($cell) || $cell === null ? $cell : json_encode($cell) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($block['headers'] ?? [1]) }}">Tidak ada data</td></tr>
                @endforelse
            </tbody>
        </table>
    @empty
        <p>Tidak ada data.</p>
    @endforelse

    <div class="footer">Diterbitkan oleh Portal Gereja</div>
</body>
</html>
