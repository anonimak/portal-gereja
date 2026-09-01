<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Error') · {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f3f5f9; color: #1f2937; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
        }
        .wrap { text-align: center; max-width: 520px; }
        .code { font-size: 88px; font-weight: 800; line-height: 1; color: #4f46e5; letter-spacing: 4px; }
        h1 { font-size: 24px; margin: 16px 0 8px; color: #111827; }
        p { font-size: 15px; color: #4b5563; line-height: 1.6; margin-bottom: 28px; }
        .btn {
            display: inline-block; background: #4f46e5; color: #fff; text-decoration: none; padding: 10px 22px;
            border-radius: 8px; font-size: 14px; font-weight: 600; margin: 0 4px 8px;
        }
        .btn:hover { background: #4338ca; }
        .btn.ghost { background: #fff; color: #4f46e5; border: 1px solid #e5e7eb; }
        .btn.ghost:hover { background: #f9fafb; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a class="btn" href="{{ url('/') }}">&larr; Kembali ke Beranda</a>
        @if (auth()->check())
            <a class="btn ghost" href="{{ url('/admin') }}">Buka Dashboard</a>
        @endif
    </div>
</body>
</html>
