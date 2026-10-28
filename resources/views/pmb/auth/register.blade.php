<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Pendaftaran Akun CAMABA - Universitas Catur Insan Cendekia</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-ucic.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-full bg-slate-50 text-slate-800 antialiased selection:bg-blue-600 selection:text-white flex flex-col justify-between"
      x-data="{
          showPassword: false,
          showConfirmPassword: false,
          loading: false,
          password: '',
          get passwordStrength() {
              if (!this.password) return { text: '', score: 0, class: '' };
              let s = 0;
              if (this.password.length >= 8) s++;
              if (/[a-z]/.test(this.password)) s++;
              if (/[A-Z]/.test(this.password)) s++;
              if (/[0-9]/.test(this.password)) s++;
              if (/[^A-Za-z0-9]/.test(this.password)) s++;
              if (s < 3) return { text: 'Password lemah', score: s, class: 'bg-rose-500 text-rose-600' };
              if (s < 4) return { text: 'Password sedang', score: s, class: 'bg-amber-500 text-amber-600' };
              return { text: 'Password kuat', score: s, class: 'bg-emerald-500 text-emerald-600' };
          }
      }">

    <!-- Top Floating Navbar -->
    <header class="w-full bg-white/80 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="{{ route('pmb.portal') }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-white border border-slate-200/90 flex items-center justify-center p-1.5 shadow-2xs group-hover:border-blue-300 transition">
                    <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" class="w-full h-full object-contain">
                </div>
                <div>
                    <span class="text-sm font-bold text-slate-900 group-hover:text-blue-700 transition font-display">PMB Universitas CIC</span>
                    <p class="text-[11px] text-slate-500">Pendaftaran Online Calon Mahasiswa</p>
                </div>
            </a>

            <div class="flex items-center gap-2">
                <a href="{{ route('pmb.portal') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Beranda PMB</span>
                </a>
                <a href="{{ route('pmb.login') }}" class="inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-bold border border-slate-300 hover:bg-slate-100 text-slate-700 transition">
                    Sudah Punya Akun? Masuk
                </a>
            </div>
        </div>
    </header>

    <!-- Main Registration Section -->
    <main class="flex-1 flex items-center justify-center py-10 px-4 sm:px-6 relative overflow-hidden">
        <!-- Ambient Glows -->
        <div class="absolute -top-32 -right-32 w-96 h-96 bg-blue-200/40 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-indigo-200/30 rounded-full blur-3xl pointer-events-none"></div>

        <div class="w-full max-w-xl bg-white rounded-3xl border border-slate-200/90 shadow-xl p-6 sm:p-8 relative z-10">
            <!-- Header -->
            <div class="text-center mb-6">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200 mb-2">
                    🎓 PMB Online UCIC {{ date('Y') }}
                </span>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight font-display">Daftar Akun Baru</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Lengkapi data awal untuk membuat akun pendaftaran mahasiswa baru</p>
            </div>

            <!-- Errors Alert -->
            @if($errors->any())
                <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                    <div class="flex items-center gap-2 font-bold mb-1.5">
                        <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Terdapat kesalahan pada isian form:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-rose-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Registration Form -->
            <form action="{{ route('pmb.register.post') }}" method="POST" @submit="loading = true" class="space-y-4">
                @csrf

                <!-- Nama Lengkap -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
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
                               placeholder="Nama lengkap sesuai identitas"
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400">
                    </div>
                </div>

                <!-- Grid: Email & WhatsApp -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Email Aktif <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                </svg>
                            </div>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   required
                                   placeholder="nama@email.com"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400">
                        </div>
                    </div>

                    <!-- WhatsApp / HP -->
                    <div>
                        <label for="whatsapp" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nomor WhatsApp / HP <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <input type="tel"
                                   id="whatsapp"
                                   name="whatsapp"
                                   value="{{ old('whatsapp') }}"
                                   required
                                   placeholder="081234567890"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400">
                        </div>
                    </div>
                </div>

                <!-- Asal Sekolah -->
                <div>
                    <label for="asal_sekolah" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Asal Sekolah (SMA/SMK/MA) <span class="text-rose-500">*</span>
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
                               placeholder="Contoh: SMAN 1 Cirebon"
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400">
                    </div>
                </div>

                <!-- Grid: Password & Konfirmasi -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Password <span class="text-rose-500">*</span>
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
                                   x-model="password"
                                   required
                                   placeholder="Minimal 8 karakter"
                                   class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400">
                            <button type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition">
                                <svg x-show="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                        <template x-if="passwordStrength.text">
                            <div class="mt-1 flex items-center gap-1.5 text-[11px]" :class="passwordStrength.class">
                                <span class="w-1.5 h-1.5 rounded-full" :class="passwordStrength.class"></span>
                                <span x-text="passwordStrength.text"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Konfirmasi Password -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
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
                                   class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400">
                            <button type="button"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition">
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
                </div>

                <!-- Minat Checkboxes (Sesuai PMB UCIC) -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2.5">
                    <label class="flex items-start gap-2.5 cursor-pointer text-xs text-slate-700">
                        <input type="checkbox"
                               name="minat_kuliah"
                               value="ya"
                               {{ old('minat_kuliah', 'ya') === 'ya' ? 'checked' : '' }}
                               class="w-4 h-4 mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span><strong>Berminat melanjutkan Kuliah</strong> di Universitas Catur Insan Cendekia (UCIC)</span>
                    </label>

                    <label class="flex items-start gap-2.5 cursor-pointer text-xs text-slate-700">
                        <input type="checkbox"
                               name="minat_miniclass"
                               value="ya"
                               {{ old('minat_miniclass') === 'ya' ? 'checked' : '' }}
                               class="w-4 h-4 mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span><strong>Berminat mengikuti MiniClass AI & Bootcamp</strong> (Gratis sertifikat & pelatihan)</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        :disabled="loading"
                        class="w-full mt-2 py-3 px-4 rounded-xl font-bold text-white bg-blue-600 hover:bg-blue-700 active:scale-[0.99] transition shadow-md shadow-blue-500/20 flex items-center justify-center gap-2 disabled:opacity-75">
                    <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-text="loading ? 'Mendaftarkan Akun...' : 'Daftar Sekarang'"></span>
                </button>
            </form>

            <!-- Login Link -->
            <div class="mt-6 pt-5 border-t border-slate-100 text-center text-xs text-slate-600">
                <span>Sudah punya akun?</span>
                <a href="{{ route('pmb.login') }}" class="font-bold text-blue-600 hover:text-blue-800 hover:underline ml-1">
                    Masuk disini &rarr;
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-4 text-center text-xs text-slate-400 border-t border-slate-200/60 bg-white">
        <p>&copy; {{ date('Y') }} Universitas Catur Insan Cendekia (UCIC). Hak Cipta Dilindungi.</p>
    </footer>

</body>
</html>
