<!DOCTYPE html>
<html lang="id" class="h-full bg-[#f0f5ff]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - CRM Marketing & Sales Inbound UCIC</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-[#f0f5ff] selection:bg-blue-600 selection:text-white" 
      x-data="{ 
          showPassword: false, 
          isLoading: false 
      }">

    <!-- Background Ambient Shapes & Soft Curved Glows -->
    <div class="fixed -top-24 -left-24 w-[480px] h-[480px] bg-white/80 rounded-full blur-3xl pointer-events-none -z-10 animate-blob"></div>
    <div class="fixed top-1/3 -left-12 w-28 h-28 bg-blue-200/50 rounded-full blur-2xl pointer-events-none -z-10"></div>
    <div class="fixed -bottom-24 -left-20 w-[420px] h-[420px] bg-blue-100/70 rounded-full blur-3xl pointer-events-none -z-10 animate-blob animation-delay-4000"></div>
    <div class="fixed -top-20 -right-20 w-[520px] h-[520px] bg-blue-100/60 rounded-full blur-3xl pointer-events-none -z-10 animate-blob animation-delay-2000"></div>
    <div class="fixed -bottom-20 -right-20 w-[480px] h-[480px] bg-indigo-100/60 rounded-full blur-3xl pointer-events-none -z-10 animate-blob"></div>

    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-2 relative overflow-hidden">
        
        <!-- ========================================== -->
        <!-- BAGIAN KIRI: Form Login                    -->
        <!-- ========================================== -->
        <div class="flex flex-col justify-between p-6 sm:p-10 md:p-14 lg:p-16 xl:p-20 z-20 max-w-xl mx-auto w-full lg:max-w-none">
            
            <div class="max-w-md w-full mx-auto my-auto py-6">
                
                <!-- Brand Header -->
                <div class="flex items-center gap-3.5 mb-7 animate-fade-in-up">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 via-blue-600 to-indigo-600 text-white flex items-center justify-center font-black text-2xl shadow-lg shadow-blue-500/25 transform transition-transform hover:scale-105 duration-200">
                        U
                    </div>
                    <div>
                        <span class="inline-block text-[11px] font-bold uppercase tracking-wider text-blue-700 bg-blue-100/70 border border-blue-200/90 px-2.5 py-0.5 rounded-full mb-1">
                            UCIC CAMPUS
                        </span>
                        <h2 class="text-xl font-black text-slate-900 tracking-tight leading-none">
                            CRM Inbound
                        </h2>
                    </div>
                </div>

                <!-- Headline & Greeting -->
                <div class="mb-6 animate-fade-in-up animation-delay-100">
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Selamat Datang Kembali</span>
                        <span class="inline-block transform hover:rotate-12 transition-transform cursor-default">👋</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-2 font-medium leading-relaxed">
                        Kelola prospek, follow-up, dan performa marketing dalam satu platform.
                    </p>
                </div>

                <!-- Status / Error Alert Box -->
                @if ($errors->any() || session('status'))
                <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2.5 animate-fade-in-up">
                    <svg class="w-4 h-4 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium">{{ $errors->first() ?: session('status') }}</span>
                </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-4" @submit="isLoading = true">
                    @csrf
                    
                    <!-- Field Email / Username -->
                    <div class="animate-fade-in-up animation-delay-200">
                        <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Email / Username
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input 
                                id="email" 
                                name="email"
                                type="email" 
                                value="{{ old('email') }}"
                                required 
                                autocomplete="email"
                                class="w-full text-xs sm:text-sm pl-10 pr-4 py-3 rounded-xl bg-[#eef4fe] border border-slate-200/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all duration-200 text-slate-800 placeholder:text-slate-400 shadow-xs"
                                placeholder="nama@cic.ac.id"
                            >
                        </div>
                    </div>

                    <!-- Field Password -->
                    <div class="animate-fade-in-up animation-delay-300">
                        <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Password
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input 
                                id="password" 
                                name="password"
                                :type="showPassword ? 'text' : 'password'" 
                                required 
                                autocomplete="current-password"
                                class="w-full text-xs sm:text-sm pl-10 pr-11 py-3 rounded-xl bg-[#eef4fe] border border-slate-200/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all duration-200 text-slate-800 placeholder:text-slate-400 shadow-xs"
                                placeholder="Masukkan password"
                            >
                            <button 
                                type="button" 
                                @click="showPassword = !showPassword" 
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-transform active:scale-90 cursor-pointer"
                                title="Lihat password"
                            >
                                <svg x-show="!showPassword" class="w-4 h-4 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showPassword" x-cloak class="w-4 h-4 transition-all text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between text-xs pt-1 animate-fade-in-up animation-delay-400">
                        <label class="flex items-center gap-2 cursor-pointer text-slate-600 hover:text-slate-900 transition select-none">
                            <input type="checkbox" checked class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 transition cursor-pointer">
                            <span class="font-medium">Ingat saya</span>
                        </label>
                        <a href="#" class="font-bold text-blue-600 hover:text-blue-700 hover:underline transition">
                            Lupa password?
                        </a>
                    </div>

                    <!-- Button Masuk -->
                    <div class="animate-fade-in-up animation-delay-500 pt-2">
                        <button 
                            type="submit" 
                            :disabled="isLoading"
                            class="w-full py-3.5 px-5 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-sm shadow-md shadow-blue-500/30 hover:shadow-lg hover:shadow-blue-500/40 transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:pointer-events-none"
                        >
                            <svg x-show="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isLoading ? 'Memproses Masuk...' : 'Masuk ke Akun'"></span>
                            <span x-show="!isLoading" class="text-base font-bold leading-none">→</span>
                        </button>
                    </div>
                </form>

            </div>

            <!-- Footer Copyright (Left) -->
            <div class="pt-4 text-center text-xs text-slate-400 font-normal">
                &copy; {{ date('Y') }} Universitas Catur Insan Cendekia. All rights reserved.
            </div>

        </div>

        <!-- =================================================================== -->
        <!-- BAGIAN KANAN: Seamless Canvas & Animated Illustration Showcase     -->
        <!-- =================================================================== -->
        <div class="hidden lg:flex flex-col justify-between p-6 sm:p-10 md:p-14 lg:p-16 xl:p-20 z-20 relative bg-gradient-to-br from-[#ddf9fd] via-[#def0fe] to-[#dee9fd]">
            
            <div class="max-w-xl w-full mx-auto my-auto py-2">
                
                <!-- Pill Badge -->
                <div class="mb-4 animate-fade-in-up">
                    <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-500/15 backdrop-blur-md text-blue-700 text-xs font-bold border border-blue-400/30 shadow-xs">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                        </svg>
                        Inbound Sales & Marketing Platform
                    </span>
                </div>

                <!-- Headline & Subtitle -->
                <div class="mb-5 animate-fade-in-up animation-delay-100">
                    <h2 class="text-2xl sm:text-3xl xl:text-4xl font-black text-slate-900 tracking-tight leading-tight">
                        Akselerasi Penerimaan <br>
                        Mahasiswa Baru & Kerjasama <br>
                        Kampus <span class="text-blue-600 font-black">UCIC.</span>
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2.5 font-normal leading-relaxed">
                        Monitoring real-time pipeline prospek sekolah, kemitraan korporasi, follow-up WhatsApp terjadwal, dan evaluasi performa marketing secara transparan.
                    </p>
                </div>

                <!-- 2 Feature Cards -->
                <div class="grid grid-cols-2 gap-3.5 mb-5 animate-fade-in-up animation-delay-200">
                    <!-- Card 1 -->
                    <div class="p-3.5 rounded-2xl bg-white/95 backdrop-blur-sm border border-slate-200/70 shadow-sm hover:shadow-md hover:border-blue-300 transition-all duration-300 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-500 text-white flex items-center justify-center shrink-0 shadow-sm shadow-blue-500/20">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs sm:text-sm font-extrabold text-slate-900">100% Inbound</div>
                            <div class="text-[10px] sm:text-[11px] text-slate-500 font-medium">Terintegrasi Sales & CS</div>
                        </div>
                    </div>
                    
                    <!-- Card 2 -->
                    <div class="p-3.5 rounded-2xl bg-white/95 backdrop-blur-sm border border-slate-200/70 shadow-sm hover:shadow-md hover:border-purple-300 transition-all duration-300 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-purple-500 text-white flex items-center justify-center shrink-0 shadow-sm shadow-purple-500/20">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs sm:text-sm font-extrabold text-slate-900">Smart Stepper</div>
                            <div class="text-[10px] sm:text-[11px] text-slate-500 font-medium">Dari Lead ke Closing</div>
                        </div>
                    </div>
                </div>

                <!-- Team Illustration Section with Seamless Blend & Interactive Floating Animations -->
                <div class="relative pt-4 pb-2 animate-fade-in-up animation-delay-300">
                    
                    <!-- Handwriting Slogan Annotation with Floating Motion -->
                    <div class="absolute -top-3 right-1 sm:right-4 z-30 pointer-events-none select-none text-right animate-float-slow">
                        <span class="font-['Caveat',cursive] text-lg sm:text-xl text-blue-600/90 -rotate-3 inline-block font-bold tracking-wide drop-shadow-xs">
                            Bersama Mewujudkan<br>Masa Depan Lebih Baik
                        </span>
                    </div>

                    <!-- FLOATING UI BADGE 1: Analytics Bar Chart (Left) -->
                    <div class="absolute top-8 -left-4 xl:-left-6 z-30 bg-white/95 backdrop-blur-md px-3 py-2 rounded-2xl shadow-xl shadow-blue-500/10 border border-white/80 flex items-center gap-2 animate-float">
                        <div class="w-7 h-7 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-xs font-bold">
                            📊
                        </div>
                        <div>
                            <div class="text-[10px] font-extrabold text-slate-800">Pipeline Analytics</div>
                            <div class="text-[8px] font-semibold text-emerald-600">+32% Konversi PMB</div>
                        </div>
                    </div>

                    <!-- FLOATING UI BADGE 2: Chat Bubble Notification (Top Right) -->
                    <div class="absolute top-12 right-6 z-30 bg-blue-600 text-white px-3 py-1.5 rounded-2xl shadow-lg shadow-blue-600/30 flex items-center gap-1.5 animate-float-reverse">
                        <span class="text-xs">💬</span>
                        <span class="text-[10px] font-bold">Follow-Up Terjadwal</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    </div>

                    <!-- FLOATING UI BADGE 3: Active User Pill (Far Right) -->
                    <div class="absolute bottom-12 -right-3 xl:-right-5 z-30 bg-white/95 backdrop-blur-md p-2 rounded-2xl shadow-xl shadow-indigo-500/10 border border-white/80 flex items-center gap-2 animate-float-slow">
                        <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-purple-500 to-indigo-500 text-white flex items-center justify-center text-xs font-bold">
                            👤
                        </div>
                        <div class="pr-1">
                            <div class="text-[10px] font-bold text-slate-800">Sales & CS Active</div>
                            <div class="text-[8px] text-slate-400">100% Inbound Ready</div>
                        </div>
                    </div>

                    <!-- Seamless Illustration Container (Matches Background) -->
                    <div class="relative rounded-3xl overflow-hidden group transition-transform duration-500 hover:scale-[1.02]">
                        <img 
                            src="{{ asset('images/crm-team-illustration.jpg') }}" 
                            alt="Tim Sales dan Marketing CRM UCIC" 
                            class="w-full h-auto object-cover rounded-3xl shadow-sm"
                        >
                    </div>

                </div>

            </div>

            <!-- Empty spacer for symmetry -->
            <div class="hidden lg:block h-4"></div>

        </div>

    </div>

</body>
</html>
