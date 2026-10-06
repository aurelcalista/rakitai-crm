<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CRM Inbound UCIC - Inbound Sales & Marketing Platform</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo-ucic.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-ucic.png') }}">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .font-sans { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-inter { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="font-sans antialiased text-slate-800 bg-[#F5F8FF] selection:bg-blue-600 selection:text-white" x-data="{ mobileMenuOpen: false }">

    <!-- Ambient Backgrounds -->
    <div class="fixed -top-40 -left-40 w-96 h-96 bg-blue-300/30 rounded-full blur-3xl pointer-events-none -z-10"></div>
    <div class="fixed top-1/4 right-0 w-80 h-80 bg-purple-300/20 rounded-full blur-3xl pointer-events-none -z-10"></div>
    <div class="fixed -bottom-40 left-1/3 w-96 h-96 bg-blue-200/30 rounded-full blur-3xl pointer-events-none -z-10"></div>

    <!-- 1. NAVBAR -->
    <nav class="fixed top-0 inset-x-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-200/60 transition-all">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo & Brand -->
                <div class="flex items-center gap-3 shrink-0">
                    <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" class="h-10 w-auto object-contain">
                    <div class="hidden sm:block">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-blue-700 bg-blue-100/70 border border-blue-200/90 px-2 py-0.5 rounded-full mb-0.5 w-max">
                            UCIC CAMPUS
                        </span>
                        <h1 class="text-lg font-black text-slate-900 tracking-tight leading-none">CRM Inbound</h1>
                    </div>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="#beranda" class="text-sm font-semibold text-slate-600 hover:text-blue-600 transition-colors">Beranda</a>
                    <a href="#fitur" class="text-sm font-semibold text-slate-600 hover:text-blue-600 transition-colors">Fitur</a>
                    <a href="#alur-kerja" class="text-sm font-semibold text-slate-600 hover:text-blue-600 transition-colors">Alur Kerja</a>
                    <a href="#analytics" class="text-sm font-semibold text-slate-600 hover:text-blue-600 transition-colors">Analytics</a>
                    <a href="#tentang" class="text-sm font-semibold text-slate-600 hover:text-blue-600 transition-colors">Tentang</a>
                </div>

                <!-- CTA & Mobile Toggle -->
                <div class="flex items-center gap-4">
                    <a href="{{ route('kontak-cepat') }}" class="hidden md:flex items-center gap-2 bg-emerald-100 hover:bg-emerald-200 text-emerald-700 border border-emerald-200 px-5 py-2.5 rounded-full font-bold text-sm transition-all shadow-sm">
                        Tambah Kontak Cepat
                    </a>
                    <a href="{{ route('login') }}" class="hidden md:flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-full font-bold text-sm transition-all shadow-md shadow-blue-500/20 hover:shadow-lg hover:shadow-blue-500/40">
                        Masuk ke Akun
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </a>
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 text-slate-600 hover:text-blue-600 focus:outline-none">
                        <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" x-cloak class="md:hidden bg-white border-t border-slate-100 shadow-xl absolute w-full">
            <div class="px-4 py-6 flex flex-col gap-4">
                <a href="#beranda" @click="mobileMenuOpen = false" class="text-base font-semibold text-slate-700 hover:text-blue-600">Beranda</a>
                <a href="#fitur" @click="mobileMenuOpen = false" class="text-base font-semibold text-slate-700 hover:text-blue-600">Fitur</a>
                <a href="#alur-kerja" @click="mobileMenuOpen = false" class="text-base font-semibold text-slate-700 hover:text-blue-600">Alur Kerja</a>
                <a href="#analytics" @click="mobileMenuOpen = false" class="text-base font-semibold text-slate-700 hover:text-blue-600">Analytics</a>
                <a href="#tentang" @click="mobileMenuOpen = false" class="text-base font-semibold text-slate-700 hover:text-blue-600">Tentang</a>
                <div class="pt-4 mt-2 border-t border-slate-100 flex flex-col gap-3">
                    <a href="{{ route('kontak-cepat') }}" class="flex justify-center items-center gap-2 w-full bg-emerald-50 text-emerald-600 border border-emerald-200 px-5 py-3 rounded-xl font-bold text-sm shadow-sm hover:bg-emerald-100">
                        Tambah Kontak Cepat
                    </a>
                    <a href="{{ route('login') }}" class="flex justify-center items-center gap-2 w-full bg-blue-600 text-white px-5 py-3 rounded-xl font-bold text-sm shadow-md hover:bg-blue-700">
                        Masuk ke Akun →
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- 2. HERO SECTION -->
    <section id="beranda" class="pt-32 pb-16 lg:pt-40 lg:pb-24 overflow-hidden relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-2 lg:gap-12 items-center">
                <!-- Text Content -->
                <div class="text-center lg:text-left mb-16 lg:mb-0">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-100/50 border border-blue-200/60 text-blue-700 text-xs sm:text-sm font-bold mb-6 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-blue-600 animate-ping"></span>
                        Inbound Sales & Marketing Platform
                    </div>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 tracking-tight leading-[1.1] mb-6">
                        Kelola Prospek.<br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Tingkatkan Konversi.</span><br>
                        Tumbuh Bersama UCIC.
                    </h1>
                    <p class="text-base sm:text-lg text-slate-600 mb-8 max-w-2xl mx-auto lg:mx-0">
                        Kelola seluruh proses marketing dan penerimaan mahasiswa baru dalam satu platform yang terintegrasi — mulai dari prospek, follow-up, pipeline, hingga Closing.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
                        <a href="{{ route('kontak-cepat') }}" class="w-full sm:w-auto bg-emerald-500 hover:bg-emerald-600 text-white px-8 py-3.5 rounded-xl font-bold transition-all shadow-lg shadow-emerald-500/30 flex items-center justify-center gap-2">
                            Tambah Kontak Cepat
                        </a>
                        <a href="{{ route('login') }}" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-8 py-3.5 rounded-xl font-bold transition-all shadow-lg shadow-blue-500/30 flex items-center justify-center gap-2">
                            Masuk ke CRM
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                        <a href="#fitur" class="w-full sm:w-auto bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 px-8 py-3.5 rounded-xl font-bold transition-all flex items-center justify-center gap-2 shadow-sm">
                            Pelajari Fitur
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Hero Image & Floating Cards -->
                <div class="relative max-w-lg mx-auto lg:max-w-none">
                    <!-- Decor Background -->
                    <div class="absolute inset-0 bg-gradient-to-tr from-blue-200/40 to-purple-200/40 rounded-[2.5rem] transform rotate-3 scale-105 -z-10"></div>
                    
                    <!-- Main Image -->
                    <div class="relative rounded-[2rem] overflow-hidden shadow-2xl border-4 border-white bg-white">
                        <img src="{{ asset('images/crm-team-illustration.jpg') }}" alt="Tim CRM" class="w-full h-auto object-cover">
                        <!-- Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/10 to-transparent"></div>
                    </div>

                    <!-- Floating Card 1 -->
                    <div class="absolute -top-6 -left-6 bg-white p-4 rounded-2xl shadow-xl border border-slate-100 flex items-center gap-3 animate-bounce" style="animation-duration: 3.2s;">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-800">100% Inbound</p>
                            <p class="text-[10px] text-slate-500 font-medium">Sales & CS Ready</p>
                        </div>
                    </div>

                    <!-- Floating Card 2 -->
                    <div class="absolute top-1/2 -right-8 bg-white p-4 rounded-2xl shadow-xl border border-slate-100 flex items-center gap-3 transform -translate-y-1/2 animate-bounce" style="animation-duration: 4.5s;">
                        <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-800">Pipeline Analytics</p>
                            <p class="text-[10px] text-slate-500 font-medium">Monitor real-time</p>
                        </div>
                    </div>

                    <!-- Floating Card 3 -->
                    <div class="absolute -bottom-6 left-10 bg-white p-4 rounded-2xl shadow-xl border border-slate-100 flex items-center gap-3 animate-bounce" style="animation-duration: 3.8s;">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-800">Smart Pipeline</p>
                            <p class="text-[10px] text-slate-500 font-medium">Lead → Closing</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. TRUST / HIGHLIGHT SECTION -->
    <section class="py-12 bg-white border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <h2 class="text-2xl lg:text-3xl font-black text-slate-900">Satu Platform, Satu Alur, Lebih Terarah.</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Highlight 1 -->
                <div class="bg-[#F5F8FF] p-6 rounded-2xl hover:-translate-y-1 transition-transform border border-slate-50 hover:border-blue-100 hover:shadow-md">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Smart Pipeline</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Pantau perjalanan prospek dari awal hingga Closing secara terstruktur.</p>
                </div>
                <!-- Highlight 2 -->
                <div class="bg-[#F5F8FF] p-6 rounded-2xl hover:-translate-y-1 transition-transform border border-slate-50 hover:border-indigo-100 hover:shadow-md">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Real-time Monitoring</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Pantau aktivitas dan performa marketing secara terstruktur dan transparan.</p>
                </div>
                <!-- Highlight 3 -->
                <div class="bg-[#F5F8FF] p-6 rounded-2xl hover:-translate-y-1 transition-transform border border-slate-50 hover:border-purple-100 hover:shadow-md">
                    <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Target Management</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Kelola target Sales dan lihat progress pencapaian tim secara lebih mudah.</p>
                </div>
                <!-- Highlight 4 -->
                <div class="bg-[#F5F8FF] p-6 rounded-2xl hover:-translate-y-1 transition-transform border border-slate-50 hover:border-emerald-100 hover:shadow-md">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Integrated CRM</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Satu platform untuk semua proses Marketing, Sales, hingga Customer Service.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. SECTION — SEMUA PROSES DALAM SATU PLATFORM -->
    <section id="alur-kerja" class="py-16 lg:py-24 bg-gradient-to-b from-white to-[#F5F8FF]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl lg:text-4xl font-black text-slate-900 mb-4">Dari Prospek Hingga Lunas, Semua Terpantau.</h2>
                <p class="text-lg text-slate-600">CRM Inbound membantu setiap role bekerja berdasarkan alur yang jelas, mulai dari pengelolaan prospek hingga proses pembayaran.</p>
            </div>

            <!-- Visual Workflow -->
            <div class="relative bg-white rounded-[2rem] p-8 lg:p-12 shadow-xl shadow-blue-500/5 border border-blue-100 overflow-x-auto no-scrollbar">
                <div class="flex flex-col md:flex-row items-center md:justify-center relative z-10 gap-6 lg:gap-4 md:min-w-max">
                    <!-- Line connector background (Desktop) -->
                    <div class="absolute top-1/2 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-100 via-purple-100 to-emerald-100 -z-10 -translate-y-1/2 hidden md:block rounded-full"></div>
                    
                    <!-- Line connector background (Mobile) -->
                    <div class="absolute left-1/2 top-0 bottom-0 w-1.5 bg-gradient-to-b from-blue-100 via-purple-100 to-emerald-100 -z-10 -translate-x-1/2 md:hidden rounded-full"></div>
                    
                    <!-- Steps -->
                    @php
                        $steps = [
                            ['title' => 'ADMIN', 'color' => 'bg-slate-100 text-slate-700', 'border' => 'border-slate-300'],
                            ['title' => 'HM', 'color' => 'bg-indigo-100 text-indigo-700', 'border' => 'border-indigo-300'],
                            ['title' => 'SPV', 'color' => 'bg-blue-100 text-blue-700', 'border' => 'border-blue-300'],
                            ['title' => 'SALES', 'color' => 'bg-purple-100 text-purple-700', 'border' => 'border-purple-300'],
                            ['title' => 'CLOSING', 'color' => 'bg-emerald-100 text-emerald-700', 'border' => 'border-emerald-300', 'highlight' => true],
                            ['title' => 'CS', 'color' => 'bg-orange-100 text-orange-700', 'border' => 'border-orange-300'],
                            ['title' => 'LUNAS', 'color' => 'bg-teal-100 text-teal-700', 'border' => 'border-teal-300', 'highlight' => true],
                        ];
                    @endphp

                    @foreach($steps as $index => $step)
                        <div class="flex flex-col items-center group relative w-full md:w-auto">
                            <div class="w-20 h-20 sm:w-24 sm:h-24 bg-white rounded-full border-4 {{ $step['border'] }} flex items-center justify-center shadow-lg transition-all duration-300 group-hover:scale-110 group-hover:-translate-y-2 relative z-10">
                                <span class="font-black text-xs sm:text-sm {{ isset($step['highlight']) ? $step['color'] : 'text-slate-800' }} px-2 py-1 rounded text-center leading-none">
                                    {{ $step['title'] }}
                                </span>
                            </div>
                            @if($index < count($steps) - 1)
                                <svg class="w-6 h-6 text-slate-300 absolute -bottom-6 left-1/2 -translate-x-1/2 md:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- 5. FEATURE SECTION -->
    <section id="fitur" class="py-16 lg:py-24 bg-slate-50 border-y border-slate-100 relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-[0.03]"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="inline-block text-blue-600 font-bold tracking-wider uppercase text-sm mb-2 bg-blue-100/50 px-3 py-1 rounded-full border border-blue-200">Fitur Unggulan</span>
                <h2 class="text-3xl lg:text-4xl font-black text-slate-900 mb-4">Semua yang Dibutuhkan Tim Marketing & Sales</h2>
                <p class="text-lg text-slate-600">Berbagai fitur canggih yang dirancang khusus untuk mempermudah alur kerja Anda.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Fitur Card -->
                <div class="bg-white p-8 rounded-[2rem] shadow-sm hover:shadow-xl hover:shadow-blue-500/10 hover:-translate-y-1 border border-slate-100 transition-all duration-300 group">
                    <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform group-hover:bg-blue-600 group-hover:text-white group-hover:shadow-lg group-hover:shadow-blue-500/30">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Manajemen Prospek</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">Kelola data calon mahasiswa baru secara terstruktur dalam satu tempat terpusat yang mudah diakses.</p>
                </div>
                <!-- Fitur Card -->
                <div class="bg-white p-8 rounded-[2rem] shadow-sm hover:shadow-xl hover:shadow-purple-500/10 hover:-translate-y-1 border border-slate-100 transition-all duration-300 group">
                    <div class="w-14 h-14 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform group-hover:bg-purple-600 group-hover:text-white group-hover:shadow-lg group-hover:shadow-purple-500/30">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Pipeline Management</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">Pantau status setiap prospek dari tahap awal hingga Closing dengan tampilan yang visual dan mudah dikelola.</p>
                </div>
                <!-- Fitur Card -->
                <div class="bg-white p-8 rounded-[2rem] shadow-sm hover:shadow-xl hover:shadow-emerald-500/10 hover:-translate-y-1 border border-slate-100 transition-all duration-300 group">
                    <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform group-hover:bg-emerald-600 group-hover:text-white group-hover:shadow-lg group-hover:shadow-emerald-500/30">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Follow-up</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">Bantu tim memastikan setiap prospek mendapatkan tindak lanjut tepat waktu tanpa ada yang terlewat.</p>
                </div>
                <!-- Fitur Card -->
                <div class="bg-white p-8 rounded-[2rem] shadow-sm hover:shadow-xl hover:shadow-orange-500/10 hover:-translate-y-1 border border-slate-100 transition-all duration-300 group">
                    <div class="w-14 h-14 bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform group-hover:bg-orange-600 group-hover:text-white group-hover:shadow-lg group-hover:shadow-orange-500/30">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Target Sales</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">Pantau target, realisasi, progress, dan sisa target masing-masing tim secara akurat.</p>
                </div>
                <!-- Fitur Card -->
                <div class="bg-white p-8 rounded-[2rem] shadow-sm hover:shadow-xl hover:shadow-indigo-500/10 hover:-translate-y-1 border border-slate-100 transition-all duration-300 group">
                    <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform group-hover:bg-indigo-600 group-hover:text-white group-hover:shadow-lg group-hover:shadow-indigo-500/30">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Analytics & Infografis</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">Dapatkan gambaran performa marketing melalui data visual yang mudah dipahami dan interaktif.</p>
                </div>
                <!-- Fitur Card -->
                <div class="bg-white p-8 rounded-[2rem] shadow-sm hover:shadow-xl hover:shadow-pink-500/10 hover:-translate-y-1 border border-slate-100 transition-all duration-300 group">
                    <div class="w-14 h-14 bg-pink-50 text-pink-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform group-hover:bg-pink-600 group-hover:text-white group-hover:shadow-lg group-hover:shadow-pink-500/30">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Potensi Wilayah</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">Analisis potensi wilayah berdasarkan area untuk menentukan strategi penetrasi yang tepat sasaran.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. ROLE SECTION -->
    <section class="py-16 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl lg:text-4xl font-black text-slate-900">Satu Platform untuk Setiap Peran</h2>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                <!-- Role ADMIN -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:border-slate-400 hover:shadow-lg hover:-translate-y-1 transition-all text-center">
                    <div class="w-12 h-12 bg-slate-100 text-slate-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3 class="font-bold text-slate-900 mb-2">ADMIN</h3>
                    <p class="text-sm text-slate-600">Mengelola sistem, user, event, dan administrasi.</p>
                </div>
                <!-- Role HM -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:border-indigo-400 hover:shadow-lg hover:-translate-y-1 transition-all text-center">
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="font-bold text-slate-900 mb-2">HM</h3>
                    <p class="text-sm text-slate-600">Memantau performa wilayah dan tim menyeluruh.</p>
                </div>
                <!-- Role SPV -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:border-blue-400 hover:shadow-lg hover:-translate-y-1 transition-all text-center">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <h3 class="font-bold text-slate-900 mb-2">SPV</h3>
                    <p class="text-sm text-slate-600">Mengelola Sales, target, dan performa tim.</p>
                </div>
                <!-- Role SALES -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:border-purple-400 hover:shadow-lg hover:-translate-y-1 transition-all text-center">
                    <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <h3 class="font-bold text-slate-900 mb-2">SALES</h3>
                    <p class="text-sm text-slate-600">Mengelola prospek, follow-up hingga Closing.</p>
                </div>
                <!-- Role CS -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:border-orange-400 hover:shadow-lg hover:-translate-y-1 transition-all text-center">
                    <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="font-bold text-slate-900 mb-2">CS</h3>
                    <p class="text-sm text-slate-600">Melanjutkan proses dari Closing hingga Lunas.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 7. ANALYTICS / DASHBOARD PREVIEW -->
    <section id="analytics" class="py-16 lg:py-24 bg-gradient-to-b from-slate-50 to-white relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl lg:text-4xl font-black text-slate-900 mb-4">Data Membantu Tim Mengambil Keputusan Lebih Cepat.</h2>
                <p class="text-lg text-slate-600">Pantau target, realisasi, pipeline, dan performa tim dalam satu tampilan yang mudah dipahami.</p>
            </div>

            <!-- Dashboard Mockup -->
            <div class="bg-white rounded-[2rem] shadow-2xl border border-slate-200 overflow-hidden transform hover:-translate-y-2 transition-transform duration-500 max-w-5xl mx-auto">
                <!-- Mockup Header -->
                <div class="bg-slate-100/80 border-b border-slate-200 px-4 py-3 flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-rose-400"></div>
                    <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                    <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                    <div class="ml-4 text-[10px] font-semibold text-slate-400 tracking-wider">crm.ucic.ac.id/dashboard</div>
                </div>
                <!-- Mockup Content -->
                <div class="p-6 sm:p-10 grid grid-cols-1 md:grid-cols-3 gap-6 bg-slate-50/50">
                    <!-- Target Card -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Target PMB</p>
                                <h4 class="text-3xl font-black text-slate-900 mt-1">2,500</h4>
                            </div>
                            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            </div>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5 mb-3">
                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: 75%"></div>
                        </div>
                        <p class="text-xs font-medium text-slate-500">Progress: 75% (<span class="text-emerald-600 font-bold">+15%</span> dari bulan lalu)</p>
                    </div>

                    <!-- Realisasi Card -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Realisasi Closing</p>
                                <h4 class="text-3xl font-black text-slate-900 mt-1">1,875</h4>
                            </div>
                            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mt-4">
                            <span class="px-2.5 py-1 bg-emerald-100/70 text-emerald-700 text-xs font-bold rounded-md border border-emerald-200">Lunas: 1,500</span>
                            <span class="px-2.5 py-1 bg-amber-100/70 text-amber-700 text-xs font-bold rounded-md border border-amber-200">Angsur: 375</span>
                        </div>
                    </div>

                    <!-- Pipeline Stats -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Status Pipeline</p>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-xs mb-1.5">
                                    <span class="font-medium text-slate-700">Follow-up</span>
                                    <span class="font-bold text-slate-900">450</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2"><div class="bg-purple-500 h-2 rounded-full" style="width: 45%"></div></div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs mb-1.5">
                                    <span class="font-medium text-slate-700">Presentasi</span>
                                    <span class="font-bold text-slate-900">210</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2"><div class="bg-blue-500 h-2 rounded-full" style="width: 25%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 flex justify-center flex-wrap gap-3">
                <span class="px-4 py-2 bg-white border border-slate-200 rounded-full text-xs font-bold text-slate-600 shadow-sm cursor-default hover:border-blue-300 transition-colors">Target</span>
                <span class="px-4 py-2 bg-white border border-slate-200 rounded-full text-xs font-bold text-slate-600 shadow-sm cursor-default hover:border-emerald-300 transition-colors">Realisasi</span>
                <span class="px-4 py-2 bg-white border border-slate-200 rounded-full text-xs font-bold text-slate-600 shadow-sm cursor-default hover:border-purple-300 transition-colors">Progress</span>
                <span class="px-4 py-2 bg-white border border-slate-200 rounded-full text-xs font-bold text-slate-600 shadow-sm cursor-default hover:border-indigo-300 transition-colors">Pipeline</span>
                <span class="px-4 py-2 bg-white border border-slate-200 rounded-full text-xs font-bold text-slate-600 shadow-sm cursor-default hover:border-orange-300 transition-colors">Closing</span>
                <span class="px-4 py-2 bg-white border border-slate-200 rounded-full text-xs font-bold text-slate-600 shadow-sm cursor-default hover:border-pink-300 transition-colors">Lunas</span>
            </div>
        </div>
    </section>

    <!-- 8. SECTION FOTO TIM / KOLABORASI -->
    <section id="tentang" class="py-16 lg:py-24 overflow-hidden relative bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <!-- Image Collage -->
                <div class="relative mb-12 lg:mb-0 order-2 lg:order-1">
                    <div class="absolute inset-0 bg-gradient-to-tr from-blue-100 to-purple-100 rounded-[3rem] transform -rotate-3 scale-105 -z-10"></div>
                    <div class="relative rounded-[2.5rem] overflow-hidden border-4 border-white shadow-2xl bg-white group">
                        <img src="{{ asset('images/crm-team-illustration.jpg') }}" alt="Tim Kolaborasi" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                    </div>
                    <!-- Decorative Badge -->
                    <div class="absolute -bottom-6 -right-6 bg-white p-5 rounded-2xl shadow-xl border border-slate-100 flex flex-col items-center justify-center animate-float">
                        <span class="text-3xl font-black text-blue-600">100+</span>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mt-1">Tim Aktif</span>
                    </div>
                </div>

                <!-- Content -->
                <div class="order-1 lg:order-2">
                    <h2 class="text-3xl lg:text-4xl font-black text-slate-900 mb-6 leading-tight">Kolaborasi Lebih Terarah, Hasil Lebih Terukur.</h2>
                    <p class="text-lg text-slate-600 mb-6 leading-relaxed">
                        Marketing, Sales, Supervisor, hingga Customer Service dapat terhubung dalam satu proses yang sama.
                    </p>
                    <p class="text-lg text-slate-600 mb-10 leading-relaxed">
                        Tidak perlu berpindah-pindah sistem untuk mengetahui perkembangan prospek. Semua informasi penting tersedia dalam satu platform.
                    </p>
                    <div class="space-y-5">
                        <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <span class="font-bold text-slate-800 text-lg">Lebih terhubung.</span>
                        </div>
                        <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="font-bold text-slate-800 text-lg">Lebih terarah.</span>
                        </div>
                        <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <span class="font-bold text-slate-800 text-lg">Lebih mudah dipantau.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 9. FINAL CTA -->
    <section class="py-24 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-800"></div>
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
        
        <!-- Glowing Orbs -->
        <div class="absolute top-0 left-0 w-64 h-64 bg-blue-400 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob"></div>
        <div class="absolute top-0 right-0 w-64 h-64 bg-purple-400 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-1/2 w-64 h-64 bg-indigo-400 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-4000"></div>
        
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <h2 class="text-3xl md:text-5xl font-black text-white mb-6 leading-tight">Siap Mengelola Marketing dengan Lebih Terstruktur?</h2>
            <p class="text-lg md:text-xl text-blue-100 mb-10 max-w-2xl mx-auto font-medium">
                Masuk ke CRM Inbound dan mulai kelola proses penerimaan mahasiswa baru dalam satu platform yang terintegrasi.
            </p>
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-3 bg-white text-blue-600 hover:bg-slate-50 px-8 py-4 rounded-xl font-black text-lg transition-all hover:scale-105 shadow-xl shadow-black/20 hover:shadow-black/30">
                Masuk ke Akun
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </section>

    <!-- 10. FOOTER -->
    <footer class="bg-white py-12 border-t border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" class="h-10 w-auto grayscale opacity-70">
                <div>
                    <h4 class="font-black text-slate-800 text-lg leading-none">UCIC CAMPUS</h4>
                    <span class="text-xs font-bold text-slate-500 tracking-wider">CRM Inbound</span>
                </div>
            </div>
            
            <div class="text-center md:text-right">
                <p class="text-sm font-bold text-slate-700 mb-1">Inbound Sales & Marketing Platform</p>
                <p class="text-xs font-medium text-slate-400">&copy; {{ date('Y') }} Universitas Catur Insan Cendekia. All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>
</html>
