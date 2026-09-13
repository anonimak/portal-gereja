<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Portal Mandiri Jemaat') — {{ auth()->user()?->church?->name ?? 'Portal Gereja' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">
    <!-- Navbar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-700 flex items-center justify-center text-white font-bold text-xl shadow">
                        <span>⛪</span>
                    </div>
                    <div>
                        <a href="{{ route('portal.profile') }}" class="font-extrabold text-slate-900 tracking-tight hover:text-amber-600 transition">
                            {{ auth()->user()?->church?->name ?? 'Portal Gereja' }}
                        </a>
                        <p class="text-xs text-slate-500 font-medium">Portal Mandiri Jemaat</p>
                    </div>
                </div>

                @auth
                    <!-- Nav links desktop -->
                    <nav class="hidden md:flex items-center space-x-1">
                        <a href="{{ route('portal.profile') }}"
                           class="px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('portal.profile') ? 'bg-amber-50 text-amber-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Data Diri
                        </a>
                        <a href="{{ route('portal.events') }}"
                           class="px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('portal.events*') || request()->routeIs('portal.schedules*') ? 'bg-amber-50 text-amber-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Jadwal Ibadah
                        </a>
                        <a href="{{ route('portal.warta') }}"
                           class="px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('portal.warta*') ? 'bg-amber-50 text-amber-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Warta Jemaat
                        </a>
                    </nav>

                    <div class="flex items-center space-x-3">
                        <div class="hidden sm:block text-right">
                            <span class="text-xs font-bold text-slate-900 block">{{ auth()->user()->name }}</span>
                            <span class="text-[10px] text-slate-400 block uppercase tracking-wider">{{ auth()->user()->member?->family_relation ?? 'Anggota' }}</span>
                        </div>
                        <form action="{{ route('portal.logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition border border-red-200">
                                Keluar
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </div>

        @auth
            <!-- Mobile Navigation Bar -->
            <div class="md:hidden border-t border-slate-100 px-4 py-2 flex justify-around bg-slate-50 text-xs">
                <a href="{{ route('portal.profile') }}" class="py-1 px-3 rounded-lg font-semibold {{ request()->routeIs('portal.profile') ? 'text-amber-600 bg-white shadow-sm' : 'text-slate-600' }}">
                    Data Diri
                </a>
                <a href="{{ route('portal.events') }}" class="py-1 px-3 rounded-lg font-semibold {{ request()->routeIs('portal.events*') || request()->routeIs('portal.schedules*') ? 'text-amber-600 bg-white shadow-sm' : 'text-slate-600' }}">
                    Jadwal Ibadah
                </a>
                <a href="{{ route('portal.warta') }}" class="py-1 px-3 rounded-lg font-semibold {{ request()->routeIs('portal.warta*') ? 'text-amber-600 bg-white shadow-sm' : 'text-slate-600' }}">
                    Warta Jemaat
                </a>
            </div>
        @endauth
    </header>

    <!-- Main Content -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
        @if (session('status'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center shadow-sm">
                <span class="mr-2 text-base">✓</span>
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-medium flex items-center shadow-sm">
                <span class="mr-2 text-base">⚠️</span>
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-400">
        <p>&copy; {{ date('Y') }} {{ auth()->user()?->church?->name ?? 'Portal Gereja' }} — Portal Mandiri Jemaat</p>
    </footer>
</body>
</html>
