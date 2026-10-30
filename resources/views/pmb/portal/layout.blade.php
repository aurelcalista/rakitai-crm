<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-18 flex items-center justify-between">
            <!-- Left Branding: UCIC Logo -->
            <div class="flex items-center gap-3">
                <a href="{{ route('pmb.akun.dashboard') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" class="h-10 w-auto object-contain">
                </a>
            </div>

            <!-- Desktop Nav Menu (Beranda, Biaya, Katalog, Dashboard, Logout) -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="{{ route('pmb.portal') }}"
                   class="transition hover:text-blue-600 {{ request()->routeIs('pmb.portal') ? 'text-blue-600 font-bold' : 'text-slate-600' }}">
                    Beranda
                </a>
                <a href="{{ route('pmb.akun.status') }}"
                   class="transition hover:text-blue-600 {{ request()->routeIs('pmb.akun.status') ? 'text-blue-600 font-bold' : 'text-slate-600' }}">
                    Biaya
                </a>
                <a href="{{ route('pmb.akun.katalog') }}"
                   class="transition hover:text-blue-600 {{ request()->routeIs('pmb.akun.katalog*') ? 'text-blue-600 font-bold' : 'text-slate-600' }}">
                    Katalog
                </a>
                <a href="{{ route('pmb.akun.dashboard') }}"
                   class="transition hover:text-blue-600 {{ request()->routeIs('pmb.akun.dashboard') ? 'text-blue-600 font-bold' : 'text-slate-600' }}">
                    Dashboard
                </a>
                <form action="{{ route('pmb.logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            class="text-slate-600 hover:text-rose-600 font-medium transition cursor-pointer">
                        Logout
                    </button>
                </form>
            </nav>

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

            <a href="{{ route('pmb.portal') }}"
               class="block px-3 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('pmb.portal') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                Beranda
            </a>
            <a href="{{ route('pmb.akun.status') }}"
               class="block px-3 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('pmb.akun.status') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                Biaya
            </a>
            <a href="{{ route('pmb.akun.katalog') }}"
               class="block px-3 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('pmb.akun.katalog*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                Katalog
            </a>
            <a href="{{ route('pmb.akun.dashboard') }}"
               class="block px-3 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('pmb.akun.dashboard') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                Dashboard
            </a>
            <a href="{{ route('pmb.akun.bayar') }}"
               class="block px-3 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('pmb.akun.bayar') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                Pembayaran Midtrans
            </a>
            <a href="{{ route('pmb.akun.profil') }}"
               class="block px-3 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('pmb.akun.profil') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                Profil Saya
            </a>

            <div class="pt-2 border-t border-slate-100">
                <form action="{{ route('pmb.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 flex items-center justify-between">
                        <span>Logout</span>
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

    <!-- Footer matching Screenshot 2 & 3 -->
    <footer class="py-10 bg-white border-t border-slate-200/80 text-xs text-slate-600 mt-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
            <div>
                <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" class="h-10 w-auto mb-3">
                <p class="text-slate-600 leading-relaxed text-xs">
                    Jl. Kesambi No.202<br>
                    Drajat, Kec. Kesambi, Kota Cirebon, Jawa Barat 45133
                </p>
                <div class="mt-2 text-xs text-slate-600 space-y-0.5">
                    <p><strong class="font-semibold text-slate-700">Phone:</strong> (0231)200418</p>
                    <p><strong class="font-semibold text-slate-700">Email:</strong> info@cic.ac.id</p>
                </div>
            </div>
            <div class="md:text-left">
                <h4 class="font-bold text-slate-900 text-sm mb-2">Ikuti Kami</h4>
                <p class="text-slate-600 leading-relaxed text-xs mb-4">
                    Tetap terhubung dengan kami untuk mendapatkan informasi terbaru tentang pendaftaran mahasiswa baru dan kegiatan kampus
                </p>
                <!-- Social media icons matching Screenshot 3 -->
                <div class="flex items-center gap-2">
                    <a href="https://twitter.com" target="_blank" class="w-9 h-9 rounded-lg border border-slate-300 flex items-center justify-center text-slate-600 hover:text-blue-600 hover:border-blue-500 transition">
                        <svg class="w-4 h-4 fill-currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="https://facebook.com" target="_blank" class="w-9 h-9 rounded-lg border border-slate-300 flex items-center justify-center text-slate-600 hover:text-blue-600 hover:border-blue-500 transition">
                        <svg class="w-4 h-4 fill-currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                    </a>
                    <a href="https://instagram.com" target="_blank" class="w-9 h-9 rounded-lg border border-slate-300 flex items-center justify-center text-slate-600 hover:text-rose-600 hover:border-rose-500 transition">
                        <svg class="w-4 h-4 fill-currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                    <a href="https://linkedin.com" target="_blank" class="w-9 h-9 rounded-lg border border-slate-300 flex items-center justify-center text-slate-600 hover:text-blue-700 hover:border-blue-600 transition">
                        <svg class="w-4 h-4 fill-currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Blue Square Button (Matching Screenshot 3) -->
    <button type="button"
            onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
            title="Scroll ke Atas"
            class="fixed bottom-6 right-6 z-40 w-11 h-11 bg-[#0b3d82] hover:bg-[#082a5c] text-white rounded-lg flex items-center justify-center shadow-lg transition hover:scale-105 active:scale-95 cursor-pointer">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
        </svg>
    </button>

    @stack('scripts')
</body>
</html>
