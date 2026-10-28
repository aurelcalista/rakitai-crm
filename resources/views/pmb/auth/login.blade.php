<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Login - Portal PMB UCIC</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-ucic.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-full relative flex items-center justify-center p-4 antialiased selection:bg-[#004b99] selection:text-white"
      x-data="{ showPassword: false, loading: false }">

    <!-- Background Image with Deep Blue Overlay (Matching Reference Screenshot) -->
    <div class="fixed inset-0 z-0 select-none pointer-events-none">
        <img src="{{ asset('images/ucic-building.jpg') }}" alt="Gedung Kampus UCIC" class="w-full h-full object-cover object-center">
        <div class="absolute inset-0 bg-[#004b99]/85 mix-blend-multiply"></div>
        <div class="absolute inset-0 bg-[#0052a3]/45"></div>
    </div>

    <!-- Top Left Button: Kembali ke Beranda -->
    <a href="{{ route('pmb.portal') }}"
       class="fixed top-5 left-5 z-20 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/20 hover:bg-white/30 backdrop-blur-md text-white text-xs font-semibold shadow-xs transition active:scale-95">
        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        <span>Kembali ke Beranda</span>
    </a>

    <!-- Center Login Card -->
    <div class="relative z-10 w-full max-w-[420px] bg-white rounded-3xl shadow-2xl p-7 sm:p-9 text-center my-8">

        <!-- Circular Mortarboard / Graduation Cap Icon Badge -->
        <div class="w-16 h-16 rounded-full bg-[#004b99] text-white flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-900/20">
            <svg class="w-8 h-8 fill-current" viewBox="0 0 24 24">
                <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zm0 8.55L4.54 9 12 4.95 19.46 9 12 11.55zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/>
            </svg>
        </div>

        <!-- Heading -->
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-wider font-display uppercase mb-6">
            LOGIN
        </h1>

        <!-- Flash Notifications -->
        @if(session('success'))
            <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs text-left">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs text-left">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Login Form -->
        <form action="{{ route('pmb.login.post') }}" method="POST" @submit="loading = true">
            @csrf

            <!-- Email Field -->
            <div class="text-left mb-4">
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Email</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           required
                           placeholder="Masukkan email"
                           autocomplete="username"
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#004b99] focus:ring-1 focus:ring-[#004b99] transition placeholder:text-slate-400 text-slate-800">
                </div>
            </div>

            <!-- Password Field -->
            <div class="text-left mb-3">
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <input :type="showPassword ? 'text' : 'password'"
                           id="password"
                           name="password"
                           required
                           placeholder="Masukkan password"
                           autocomplete="current-password"
                           class="w-full pl-10 pr-11 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#004b99] focus:ring-1 focus:ring-[#004b99] transition placeholder:text-slate-400 text-slate-800">
                    <button type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition">
                        <svg x-show="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Ingat Saya Checkbox -->
            <div class="flex items-center text-left pt-1 mb-5">
                <label class="flex items-center gap-2 cursor-pointer select-none text-xs text-slate-600 font-medium">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-[#004b99] focus:ring-[#004b99]">
                    <span>Ingat saya</span>
                </label>
            </div>

            <!-- Submit Button (Masuk) -->
            <button type="submit"
                    :disabled="loading"
                    class="w-full py-3 rounded-xl font-bold text-sm bg-[#004b99] hover:bg-[#003d7c] text-white shadow-md transition active:scale-95 flex items-center justify-center gap-2">
                <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span x-text="loading ? 'Memproses...' : 'Masuk'"></span>
            </button>
        </form>

        <!-- Register Link -->
        <div class="mt-4 text-xs text-slate-600">
            Belum punya akun? <a href="{{ route('pmb.register') }}" class="font-bold text-[#004b99] hover:underline">Daftar disini</a>
        </div>

        <!-- Lupa Password Link -->
        <div class="mt-2.5">
            <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=Halo%20Admin%20PMB%20UCIC%2C%20saya%20lupa%20password%20akun%20PMB"
               target="_blank"
               class="text-xs font-bold text-[#004b99] hover:underline">
                Lupa Password?
            </a>
        </div>

        <!-- Divider -->
        <div class="my-4 text-xs text-slate-400">
            atau
        </div>

        <!-- Masuk dengan OTP Button -->
        <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=Halo%20Admin%20PMB%20UCIC%2C%20saya%20ingin%20masuk%20menggunakan%20kode%20OTP%20WhatsApp"
           target="_blank"
           class="w-full inline-block py-3 rounded-xl font-bold text-sm bg-[#004b99] hover:bg-[#003d7c] text-white shadow-md transition active:scale-95 text-center">
            Masuk dengan OTP
        </a>

    </div>

</body>
</html>
