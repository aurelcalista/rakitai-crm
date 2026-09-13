<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - CRM Marketing & Sales Inbound UCIC</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-slate-50" x-data="{ 
    showPassword: false, 
    isLoading: false, 
    hasError: false,
    selectedRole: 'sales',
    email: 'aurel.calista@cic.ac.id',
    password: '••••••••••••'
}">

    <div class="min-h-full flex">
        
        <!-- Left Side: Login Form (Responsive Centered / Split) -->
        <div class="flex-1 flex flex-col justify-center py-10 px-4 sm:px-6 lg:px-20 xl:px-24 bg-white z-10 shadow-xl md:shadow-none">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                
                <!-- Logo & Brand Header -->
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-700 to-blue-500 text-white flex items-center justify-center font-bold text-xl shadow-md tracking-wider">
                            U
                        </div>
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-100">UCIC CAMPUS</span>
                            <h2 class="text-xl font-bold text-slate-900 tracking-tight">CRM Inbound</h2>
                        </div>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Selamat Datang Kembali</h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1.5 leading-relaxed">
                        Kelola prospek, follow-up, dan performa marketing dalam satu platform.
                    </p>
                </div>

                <!-- Role Quick Select for Demo (Sales, CS, SPV, HM, Admin) -->
                <div class="mb-6 p-3 bg-slate-50 rounded-2xl border border-slate-200/80">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Pilih Role Akses (Demo Mode):</p>
                    <div class="grid grid-cols-5 gap-1">
                        <button 
                            type="button" 
                            @click="selectedRole = 'sales'; email = 'aurel.calista@cic.ac.id'"
                            :class="selectedRole === 'sales' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'"
                            class="py-1.5 px-1.5 rounded-lg text-[11px] transition text-center cursor-pointer"
                        >
                            Sales
                        </button>
                        <button 
                            type="button" 
                            @click="selectedRole = 'cs'; email = 'dina.cs@cic.ac.id'"
                            :class="selectedRole === 'cs' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'"
                            class="py-1.5 px-1.5 rounded-lg text-[11px] transition text-center cursor-pointer"
                        >
                            CS
                        </button>
                        <button 
                            type="button" 
                            @click="selectedRole = 'spv'; email = 'hendra.spv@cic.ac.id'"
                            :class="selectedRole === 'spv' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'"
                            class="py-1.5 px-1.5 rounded-lg text-[11px] transition text-center cursor-pointer"
                        >
                            SPV
                        </button>
                        <button 
                            type="button" 
                            @click="selectedRole = 'hm'; email = 'head.marketing@cic.ac.id'"
                            :class="selectedRole === 'hm' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'"
                            class="py-1.5 px-1.5 rounded-lg text-[11px] transition text-center cursor-pointer"
                        >
                            HM
                        </button>
                        <button 
                            type="button" 
                            @click="selectedRole = 'admin'; email = 'admin@cic.ac.id'"
                            :class="selectedRole === 'admin' ? 'bg-purple-600 text-white shadow-xs font-bold' : 'bg-white text-slate-700 border border-slate-200 hover:bg-purple-50'"
                            class="py-1.5 px-1.5 rounded-lg text-[11px] transition text-center cursor-pointer"
                        >
                            Admin
                        </button>
                    </div>
                </div>

                <!-- Error Alert Box -->
                <div x-show="hasError" x-cloak class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Email atau password yang Anda masukkan tidak sesuai.</span>
                </div>

                <!-- Login Form -->
                <form @submit.prevent="
                    isLoading = true; 
                    setTimeout(() => { 
                        isLoading = false; 
                        window.location.href = (selectedRole === 'admin' ? '/dashboard/admin' : '/dashboard/' + selectedRole); 
                    }, 500)
                " class="space-y-4">
                    
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Email / Username</label>
                        <input 
                            id="email" 
                            type="email" 
                            x-model="email"
                            required 
                            class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                            placeholder="nama@cic.ac.id"
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                        <div class="relative">
                            <input 
                                id="password" 
                                :type="showPassword ? 'text' : 'password'" 
                                x-model="password"
                                required 
                                class="w-full text-xs sm:text-sm px-3.5 py-2.5 pr-10 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                placeholder="Masukkan password"
                            >
                            <button 
                                type="button" 
                                @click="showPassword = !showPassword" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                            >
                                <svg x-show="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs pt-1">
                        <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                            <input type="checkbox" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span>Ingat saya</span>
                        </label>
                        <a href="#" class="font-semibold text-blue-600 hover:text-blue-700">Lupa password?</a>
                    </div>

                    <button 
                        type="submit" 
                        :disabled="isLoading"
                        class="w-full mt-2 py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-xs sm:text-sm shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60"
                    >
                        <svg x-show="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isLoading ? 'Memproses Masuk...' : 'Masuk ke Akun'"></span>
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-slate-100 text-center text-xs text-slate-400">
                    &copy; {{ date('Y') }} Universitas Catur Insan Cendekia. All rights reserved.
                </div>

            </div>
        </div>

        <!-- Right Side: Clean Modern Graphic / Branding (Desktop Only) -->
        <div class="hidden lg:block relative flex-1 bg-gradient-to-br from-slate-900 via-blue-950 to-blue-900 text-white p-12 overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(59,130,246,0.3),transparent_50%)]"></div>
            
            <div class="relative z-10 h-full flex flex-col justify-between max-w-lg mx-auto">
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full bg-blue-500/20 text-blue-200 text-xs font-semibold border border-blue-400/30">
                        Inbound Sales & Marketing Platform
                    </span>
                </div>

                <div class="space-y-6">
                    <h2 class="text-3xl font-extrabold tracking-tight leading-tight">
                        Akselerasi Penerimaan Mahasiswa Baru & Kerjasama Kampus UCIC.
                    </h2>
                    <p class="text-slate-300 text-sm leading-relaxed">
                        Monitoring real-time pipeline prospek sekolah, kemitraan korporasi, follow-up WhatsApp terjadwal, dan evaluasi performa marketing secara transparan.
                    </p>

                    <!-- Feature Highlights Pills -->
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <div class="p-3.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/10">
                            <div class="text-lg font-bold text-white">100% Inbound</div>
                            <div class="text-xs text-blue-200">Terintegrasi Sales & CS</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/10">
                            <div class="text-lg font-bold text-white">Smart Stepper</div>
                            <div class="text-xs text-blue-200">Dari Lead ke Closing</div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-400 border-t border-white/10 pt-6">
                    <span>Universitas Catur Insan Cendekia</span>
                    <span>Cirebon, Jawa Barat</span>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
