<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Portal Akun CAMABA') - Universitas Catur Insan Cendekia</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-ucic.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-full bg-slate-50 text-slate-800 antialiased selection:bg-blue-600 selection:text-white flex flex-col justify-between"
      x-data="{ mobileNavOpen: false }">

    <!-- Top Navigation Header -->
    <header class="w-full bg-white border-b border-slate-200/90 sticky top-0 z-30 shadow-2xs">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <!-- Left Branding -->
            <div class="flex items-center gap-3">
                <a href="{{ route('pmb.akun.dashboard') }}" class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-white border border-slate-200/90 flex items-center justify-center p-1.5 shadow-2xs">
                        <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <span class="text-sm font-bold text-slate-900 leading-tight font-display block">Portal Akun CAMABA</span>
                        <span class="text-[11px] text-blue-600 font-semibold block">Universitas CIC Cirebon</span>
                    </div>
                </a>
            </div>

            <!-- Desktop Nav Menu -->
            <nav class="hidden md:flex items-center gap-1">
                <a href="{{ route('pmb.akun.dashboard') }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('pmb.akun.dashboard') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Dashboard
                </a>
                <a href="{{ route('pmb.akun.status') }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('pmb.akun.status') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Status Pendaftaran
                </a>
                <a href="{{ route('pmb.akun.bayar') }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('pmb.akun.bayar') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Pembayaran
                </a>
                <a href="{{ route('pmb.akun.profil') }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('pmb.akun.profil') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Profil Saya
                </a>
                <a href="{{ route('pmb.akun.bantuan') }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('pmb.akun.bantuan') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Bantuan
                </a>
            </nav>

            <!-- Right User Info & Logout -->
            <div class="hidden md:flex items-center gap-3">
                <div class="text-right">
                    <span class="text-xs font-bold text-slate-800 block leading-tight">{{ auth()->user()->name }}</span>
                    <span class="text-[10px] text-slate-400 block">{{ auth()->user()->email }}</span>
                </div>

                <form action="{{ route('pmb.logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            title="Keluar dari akun"
                            class="p-2 rounded-xl border border-slate-200 text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Mobile Menu Toggle Button -->
            <button type="button"
                    @click="mobileNavOpen = !mobileNavOpen"
                    class="md:hidden p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100">
                <svg x-show="!mobileNavOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="mobileNavOpen" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Mobile Navigation Menu -->
        <div x-show="mobileNavOpen" x-cloak class="md:hidden border-t border-slate-200 bg-white px-4 py-3 space-y-1">
            <div class="pb-2 mb-2 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-900 block">{{ auth()->user()->name }}</span>
                    <span class="text-[10px] text-slate-500 block">{{ auth()->user()->email }}</span>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700">CAMABA</span>
            </div>

            <a href="{{ route('pmb.akun.dashboard') }}"
               class="block px-3 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('pmb.akun.dashboard') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                Dashboard
            </a>
            <a href="{{ route('pmb.akun.status') }}"
               class="block px-3 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('pmb.akun.status') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                Status Pendaftaran
            </a>
            <a href="{{ route('pmb.akun.bayar') }}"
               class="block px-3 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('pmb.akun.bayar') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                Pembayaran
            </a>
            <a href="{{ route('pmb.akun.profil') }}"
               class="block px-3 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('pmb.akun.profil') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                Profil Saya
            </a>
            <a href="{{ route('pmb.akun.bantuan') }}"
               class="block px-3 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('pmb.akun.bantuan') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                Bantuan & CS
            </a>

            <div class="pt-2 border-t border-slate-100">
                <form action="{{ route('pmb.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 flex items-center justify-between">
                        <span>Keluar Akun</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Body Container -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-6 sm:py-8">
        <!-- Flash Notifications -->
        @if(session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-3 shadow-2xs">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-center gap-3 shadow-2xs">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="py-6 bg-white border-t border-slate-200/80 text-center text-xs text-slate-500">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div>
                <span class="font-bold text-slate-700">Universitas Catur Insan Cendekia (UCIC)</span>
                <span class="hidden sm:inline"> • </span>
                <span>Sekretariat PMB Kampus Cirebon</span>
            </div>
            <div class="flex items-center gap-4 text-slate-400">
                <a href="{{ route('pmb.akun.bantuan') }}" class="hover:text-blue-600 transition">Pusat Bantuan</a>
                <span>•</span>
                <a href="https://wa.me/6281216700519" target="_blank" class="hover:text-emerald-600 transition">WhatsApp CS</a>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
