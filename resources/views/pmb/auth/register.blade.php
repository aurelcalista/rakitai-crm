<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Registrasi - Portal PMB UCIC</title>
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
      x-data="{ showPassword: false, showConfirmPassword: false, loading: false }">

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

    <!-- Center Registration Card -->
    <div class="relative z-10 w-full max-w-[480px] bg-white rounded-3xl shadow-2xl p-6 sm:p-8 text-center my-8">

        <!-- Circular User-Plus Icon Badge -->
        <div class="w-14 h-14 rounded-full bg-[#004b99] text-white flex items-center justify-center mx-auto mb-3 shadow-md shadow-blue-900/20">
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
        </div>

        <!-- Heading -->
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-wider font-display uppercase">
            REGISTRASI
        </h1>
        <p class="text-xs text-slate-500 mt-1 mb-5">
            Buat akun baru untuk mendaftar ke UCIC
        </p>

        <!-- Flash Notifications -->
        @if($errors->any())
            <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs text-left">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Registration Form -->
        <form action="{{ route('pmb.register.post') }}" method="POST" @submit="loading = true">
            @csrf

            <!-- Nama Lengkap -->
            <div class="text-left mb-3.5">
                <label for="name" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Nama Lengkap <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           required
                           placeholder="Masukkan nama lengkap"
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#004b99] focus:ring-1 focus:ring-[#004b99] transition placeholder:text-slate-400 text-slate-800">
                </div>
            </div>

            <!-- Email Aktif -->
            <div class="text-left mb-3.5">
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Email Aktif <span class="text-rose-500">*</span>
                </label>
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
                           placeholder="Masukkan email aktif"
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#004b99] focus:ring-1 focus:ring-[#004b99] transition placeholder:text-slate-400 text-slate-800">
                </div>
            </div>

            <!-- Nomor HP Aktif -->
            <div class="text-left mb-3.5">
                <label for="whatsapp" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Nomor HP Aktif <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <input type="tel"
                           id="whatsapp"
                           name="whatsapp"
                           value="{{ old('whatsapp') }}"
                           required
                           placeholder="Contoh: 08123456789"
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#004b99] focus:ring-1 focus:ring-[#004b99] transition placeholder:text-slate-400 text-slate-800">
                </div>
            </div>

            <!-- Asal Sekolah -->
            <div class="text-left mb-3.5">
                <label for="asal_sekolah" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Asal Sekolah <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <input type="text"
                           id="asal_sekolah"
                           name="asal_sekolah"
                           value="{{ old('asal_sekolah') }}"
                           required
                           placeholder="Contoh: SMA Negeri 1 Cirebon"
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#004b99] focus:ring-1 focus:ring-[#004b99] transition placeholder:text-slate-400 text-slate-800">
                </div>
            </div>

            <!-- Buat Password Baru -->
            <div class="text-left mb-3.5">
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Buat Password Baru <span class="text-rose-500">*</span>
                </label>
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
                           placeholder="Minimal 8 karakter"
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

            <!-- Konfirmasi Password -->
            <div class="text-left mb-4">
                <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Konfirmasi Password <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <input :type="showConfirmPassword ? 'text' : 'password'"
                           id="password_confirmation"
                           name="password_confirmation"
                           required
                           placeholder="Ulangi password"
                           class="w-full pl-10 pr-11 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#004b99] focus:ring-1 focus:ring-[#004b99] transition placeholder:text-slate-400 text-slate-800">
                    <button type="button"
                            @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition">
                        <svg x-show="!showConfirmPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showConfirmPassword" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Minat Checkboxes (Exact Matching Screenshot) -->
            <div class="text-left space-y-2 mb-5">
                <label class="flex items-center gap-2.5 cursor-pointer text-xs text-slate-700 select-none">
                    <input type="checkbox"
                           name="minat_kuliah"
                           value="ya"
                           {{ old('minat_kuliah', 'ya') === 'ya' ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-slate-300 text-[#004b99] focus:ring-[#004b99]">
                    <span>Berminat melanjutkan Kuliah</span>
                </label>

                <label class="flex items-center gap-2.5 cursor-pointer text-xs text-slate-700 select-none">
                    <input type="checkbox"
                           name="minat_miniclass"
                           value="ya"
                           {{ old('minat_miniclass') === 'ya' ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-slate-300 text-[#004b99] focus:ring-[#004b99]">
                    <span>Berminat mengikuti MiniClass</span>
                </label>
            </div>

            <!-- Submit Button (Daftar Sekarang) -->
            <button type="submit"
                    :disabled="loading"
                    class="w-full py-3 rounded-xl font-bold text-sm bg-[#004b99] hover:bg-[#003d7c] text-white shadow-md transition active:scale-95 flex items-center justify-center gap-2">
                <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span x-text="loading ? 'Memproses...' : 'Daftar Sekarang'"></span>
            </button>
        </form>

        <!-- Login Link -->
        <div class="mt-4 text-xs text-slate-600">
            Sudah punya akun? <a href="{{ route('pmb.login') }}" class="font-bold text-[#004b99] hover:underline">Masuk disini</a>
        </div>

    </div>

</body>
</html>
