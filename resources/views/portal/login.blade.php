<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Portal Mandiri Jemaat</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        <div class="bg-gradient-to-r from-amber-600 to-amber-700 p-8 text-white text-center">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm text-3xl mb-3 shadow-inner">
                <span>⛪</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight">Portal Mandiri Jemaat</h1>
            <p class="text-amber-100 text-sm mt-1">Akses data diri, jadwal ibadah, dan warta jemaat</p>
        </div>

        <div class="p-8">
            @if (session('status'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('portal.login.submit') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="login" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Email atau Nomor KTP / NIK
                    </label>
                    <input type="text"
                           id="login"
                           name="login"
                           value="{{ old('login') }}"
                           required
                           placeholder="contoh@email.com atau 3171..."
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm transition">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Kata Sandi (Password)
                        </label>
                    </div>
                    <input type="password"
                           id="password"
                           name="password"
                           required
                           placeholder="••••••••"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm transition">
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                        <span class="text-xs text-slate-600">Ingat saya di perangkat ini</span>
                    </label>
                </div>

                <button type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm shadow-md hover:shadow-lg transition">
                    Masuk ke Portal
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-100 text-center text-xs text-slate-500 space-y-2">
                <p>Belum memiliki akun atau butuh bantuan aktivasi? Hubungi Sekretariat Majelis Jemaat.</p>
                <div class="pt-2">
                    <a href="{{ route('public.warta.index') }}" class="text-amber-600 hover:underline font-semibold">
                        Lihat Warta Publik →
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
