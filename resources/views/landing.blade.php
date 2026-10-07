<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>CRM Inbound UCIC — Sistem CRM Pemasaran & Penerimaan Mahasiswa Baru</title>
    <meta name="description" content="Platform CRM terpadu Universitas Catur Insan Cendekia (UCIC) untuk mengoptimalkan alur kerja tim pemasaran, penanganan prospek sekolah, dan penerimaan mahasiswa baru.">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo-ucic.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-ucic.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }
        .font-handwriting { font-family: 'Caveat', cursive; }

        /* Subtle organic blob pulse */
        @keyframes blobPulse {
            0%, 100% { transform: scale(1) translate(0, 0); }
            50% { transform: scale(1.03) translate(-3px, 3px); }
        }
        .animate-blob-pulse {
            animation: blobPulse 8s ease-in-out infinite;
        }

        /* Gentle floating animation for FAQ side figures */
        @keyframes floatGentleLeft {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-12px); }
        }
        @keyframes floatGentleRight {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-12px); }
        }
        .animate-float-left {
            animation: floatGentleLeft 4.5s ease-in-out infinite;
        }
        .animate-float-right {
            animation: floatGentleRight 5.2s ease-in-out infinite 0.6s;
        }

        /* Silky smooth grid transition for accordion */
        .faq-content-grid {
            display: grid;
            transition: grid-template-rows 320ms cubic-bezier(0.16, 1, 0.3, 1);
        }
        .faq-content-grid.is-open {
            grid-template-rows: 1fr;
        }
        .faq-content-grid.is-closed {
            grid-template-rows: 0fr;
        }

        /* Focus outline improvements for accessibility */
        a:focus-visible, button:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 3px;
        }

        @media (prefers-reduced-motion: reduce) {
            *, ::before, ::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>
<body 
    class="font-body text-slate-800 bg-[#F8FAFC] selection:bg-blue-600 selection:text-white antialiased overflow-x-hidden min-h-screen flex flex-col"
    x-data="{ mobileMenuOpen: false, scrolled: false }"
    @scroll.window="scrolled = (window.pageYOffset > 20)"
>

    <!-- Accessibility Skip Link -->
    <a href="#beranda" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 z-[100] bg-blue-600 text-white px-4 py-2 rounded-xl font-bold shadow-lg">
        Lewati ke Konten Utama
    </a>

    <!-- =================================================================== -->
    <!-- NAVBAR                                                              -->
    <!-- Polished glassmorphism with dynamic scroll shadow & mobile drawer   -->
    <!-- =================================================================== -->
    <header 
        class="fixed top-0 inset-x-0 z-50 transition-all duration-300"
        :class="scrolled ? 'bg-white/95 backdrop-blur-md shadow-sm border-b border-slate-200/90 py-0' : 'bg-white/80 backdrop-blur-md border-b border-slate-200/60 py-0'"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                
                <!-- Brand / Logo -->
                <a href="#beranda" class="flex items-center gap-3 group select-none">
                    <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" class="h-10 sm:h-11 w-auto object-contain transition-transform duration-200 group-hover:scale-105">
                    <div>
                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-blue-700 bg-blue-100/70 border border-blue-200/90 px-2 py-0.5 rounded-full w-max leading-none mb-0.5">
                            UCIC CAMPUS
                        </span>
                        <span class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-none block font-display">
                            CRM Inbound
                        </span>
                    </div>
                </a>

                <!-- Desktop Navigation Menu -->
                <nav class="hidden md:flex items-center gap-7 lg:gap-8" aria-label="Navigasi Utama">
                    <a href="#beranda" class="text-sm font-semibold text-slate-600 hover:text-blue-600 transition-colors py-1">Beranda</a>
                    <a href="#fitur" class="text-sm font-semibold text-slate-600 hover:text-blue-600 transition-colors py-1">Fitur</a>
                    <a href="#keunggulan" class="text-sm font-semibold text-slate-600 hover:text-blue-600 transition-colors py-1">Keunggulan</a>
                    <a href="#faq" class="text-sm font-semibold text-slate-600 hover:text-blue-600 transition-colors py-1">FAQ</a>
                    <a href="#kontak" class="text-sm font-semibold text-slate-600 hover:text-blue-600 transition-colors py-1">Kontak</a>
                </nav>

                <!-- Desktop CTA & Mobile Toggle -->
                <div class="flex items-center gap-3">
                    <a 
                        href="{{ route('login') }}" 
                        class="hidden sm:inline-flex items-center gap-2 bg-[#0f172a] hover:bg-blue-900 text-white px-5 sm:px-6 py-2.5 rounded-full font-bold text-xs sm:text-sm transition-all duration-200 shadow-sm shadow-slate-900/10 hover:shadow-md hover:shadow-blue-900/20 hover:-translate-y-0.5 active:translate-y-0 active:scale-95 group font-display"
                    >
                        <span>Masuk ke Akun</span>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>

                    <!-- Hamburger button (Mobile) -->
                    <button 
                        @click="mobileMenuOpen = !mobileMenuOpen" 
                        class="md:hidden p-2.5 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 transition-colors focus:ring-2 focus:ring-blue-500 focus:outline-none" 
                        :aria-expanded="mobileMenuOpen" 
                        aria-label="Buka Menu Navigasi"
                    >
                        <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

            </div>
        </div>

        <!-- Mobile Menu Dropdown with Smooth Transitions -->
        <div 
            x-show="mobileMenuOpen" 
            x-cloak 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="md:hidden bg-white/98 backdrop-blur-lg border-t border-slate-100 shadow-xl px-5 py-6 space-y-3"
        >
            <a href="#beranda" @click="mobileMenuOpen = false" class="block text-base font-semibold text-slate-700 hover:text-blue-600 py-1.5 transition-colors">Beranda</a>
            <a href="#fitur" @click="mobileMenuOpen = false" class="block text-base font-semibold text-slate-700 hover:text-blue-600 py-1.5 transition-colors">Fitur</a>
            <a href="#keunggulan" @click="mobileMenuOpen = false" class="block text-base font-semibold text-slate-700 hover:text-blue-600 py-1.5 transition-colors">Keunggulan</a>
            <a href="#faq" @click="mobileMenuOpen = false" class="block text-base font-semibold text-slate-700 hover:text-blue-600 py-1.5 transition-colors">FAQ</a>
            <a href="#kontak" @click="mobileMenuOpen = false" class="block text-base font-semibold text-slate-700 hover:text-blue-600 py-1.5 transition-colors">Kontak</a>
            <div class="pt-4 border-t border-slate-100">
                <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 w-full bg-[#0f172a] hover:bg-blue-900 active:scale-95 text-white py-3.5 rounded-full font-bold text-sm shadow-md transition-all">
                    Masuk ke Akun →
                </a>
            </div>
        </div>
    </header>

    <main class="flex-grow">
        <!-- =================================================================== -->
        <!-- SECTION 1 — HERO                                                    -->
        <!-- Matches Reference Composition:                                      -->
        <!-- Pill Badge + Large Headline + Paragraph + 2 CTAs + Cutout with      -->
        <!-- Laptop + Doodle Arrow & Note "Teknologi untuk hasil yang lebih baik"-->
        <!-- =================================================================== -->
        <section id="beranda" class="pt-28 sm:pt-32 pb-16 lg:pt-36 lg:pb-24 relative overflow-hidden bg-white">
            <!-- Ambient Background Glows -->
            <div class="absolute top-1/4 -left-32 w-80 sm:w-96 h-80 sm:h-96 bg-blue-100/40 rounded-full blur-3xl pointer-events-none -z-10"></div>
            <div class="absolute top-1/3 right-1/4 w-72 sm:w-80 h-72 sm:h-80 bg-sky-100/30 rounded-full blur-3xl pointer-events-none -z-10"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-8 items-center">
                    
                    <!-- Left: Headline, Description & CTAs (7 cols) -->
                    <div class="lg:col-span-7 text-left">
                        
                        <!-- Eyebrow Badge (matches reference layout, wraps comfortably on mobile) -->
                        <div class="inline-flex flex-wrap items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50/90 border border-blue-200/80 text-blue-900 text-xs sm:text-sm font-semibold mb-6 shadow-2xs">
                            <span class="px-2.5 py-0.5 rounded-full bg-blue-600 text-white text-[10px] font-bold tracking-wider uppercase">CRM UCIC</span>
                            <span class="text-slate-600 font-medium">Sistem Pemasaran & PMB Terpadu Kampus</span>
                        </div>

                        <!-- Large Display Headline (Core Value: Prospek, Follow-Up, Closing PMB) -->
                        <h1 class="font-display text-3xl sm:text-4xl md:text-5xl lg:text-[52px] xl:text-[58px] font-black text-[#0f172a] tracking-tight leading-[1.1] mb-6">
                            Kelola Prospek.<br>
                            Atur Follow-Up.<br>
                            <span class="text-blue-600">Tingkatkan Closing PMB.</span>
                        </h1>

                        <!-- Supporting Paragraph (authentic CRM UCIC context) -->
                        <p class="text-sm sm:text-base lg:text-lg text-slate-600 mb-8 max-w-xl leading-relaxed">
                            CRM UCIC menghubungkan alur kerja pemasaran kampus secara menyeluruh — mulai dari pencatatan prospek sekolah & korporasi, kunjungan ber-GPS, pembagian target berjenjang, hingga validasi pelunasan mahasiswa baru.
                        </p>

                        <!-- CTAs (Navy Pill Primary + White Border Secondary) -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3.5 sm:gap-4">
                            <a 
                                href="{{ route('login') }}" 
                                class="inline-flex items-center justify-center gap-2.5 bg-[#0f172a] hover:bg-blue-900 text-white font-bold text-sm sm:text-base px-8 py-3.5 rounded-full transition-all duration-200 shadow-md shadow-slate-900/15 hover:shadow-lg hover:shadow-blue-900/25 hover:-translate-y-0.5 active:translate-y-0 active:scale-95 group font-display text-center"
                            >
                                <span>Mulai Sekarang</span>
                                <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                            <a 
                                href="#fitur" 
                                class="inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-bold text-sm sm:text-base px-7 py-3.5 rounded-full transition-all duration-200 shadow-2xs hover:border-slate-400 hover:-translate-y-0.5 active:translate-y-0 font-display text-center"
                            >
                                Pelajari Lebih Lanjut
                            </a>
                        </div>

                    </div>

                    <!-- Right: Visual Illustration / Photo Cutout with Laptop (5 cols) -->
                    <div class="lg:col-span-5 relative flex justify-center lg:justify-end items-end pt-4 sm:pt-6 lg:pt-0">
                        
                        <!-- Soft organic light-blue blob background behind the person (matches reference) -->
                        <div class="absolute -inset-2 sm:-inset-4 lg:-inset-6 bg-gradient-to-tr from-sky-100/90 via-blue-50/80 to-indigo-50/60 rounded-[3rem] sm:rounded-[4rem] -rotate-2 -z-10 animate-blob-pulse"></div>

                        <!-- Doodle Handwriting Note & Curved Arrow (Top-Right, matching reference) -->
                        <div class="absolute top-1 sm:top-4 right-1 sm:right-2 z-20 pointer-events-none select-none text-right">
                            <span class="font-handwriting text-base sm:text-lg lg:text-xl text-slate-700 -rotate-6 inline-block font-bold tracking-wide drop-shadow-2xs">
                                Teknologi untuk hasil<br>yang lebih baik
                            </span>
                            <!-- Hand-drawn curved doodle arrow pointing down towards laptop -->
                            <div class="flex justify-end pr-4 sm:pr-6 -mt-1">
                                <svg class="w-10 sm:w-12 h-8 sm:h-10 text-slate-700 -rotate-12" viewBox="0 0 50 40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M42 4 C35 15, 20 18, 12 30" />
                                    <path d="M8 24 L12 30 L18 28" />
                                </svg>
                            </div>
                        </div>

                        <!-- Small Decorative Doodle Sparks -->
                        <div class="absolute top-16 left-2 sm:left-4 z-20 text-slate-400 pointer-events-none hidden sm:block">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <path d="M12 2v4m0 12v4M2 12h4m12 0h4m-3.5-6.5l-2.8 2.8m-7.4 7.4l-2.8 2.8m13 0l-2.8-2.8m-7.4-7.4l-2.8-2.8"/>
                            </svg>
                        </div>

                        <!-- Main Cutout Photo: Woman with Laptop (images/foto-5.png) -->
                        <div class="relative z-10 w-full max-w-[300px] sm:max-w-[380px] lg:max-w-[440px]">
                            <img 
                                src="{{ asset('images/foto-5.png') }}" 
                                alt="CRM UCIC Platform - Tim Pemasaran dengan Laptop" 
                                class="w-full h-auto object-contain drop-shadow-xl select-none"
                                loading="eager"
                            >
                        </div>

                        <!-- Floating subtle doodle line near bottom right -->
                        <div class="absolute bottom-4 right-1 z-20 pointer-events-none text-slate-400 hidden sm:block">
                            <svg class="w-10 h-6" viewBox="0 0 40 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <path d="M4 14 C12 6, 24 18, 36 8" />
                            </svg>
                        </div>

                    </div>

                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- SECTION 2 — FEATURE HIGHLIGHTS                                      -->
        <!-- Matches Reference Composition: 4 compact feature cards in a row     -->
        <!-- Icons: People, Trending Up, Calendar, Shield Check                  -->
        <!-- =================================================================== -->
        <section id="fitur" class="py-12 sm:py-16 bg-[#F8FAFC] border-y border-slate-200/70">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 sm:gap-6">
                    
                    <!-- Card 1: Manajemen Tim -->
                    <div class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200/80 shadow-2xs hover:shadow-lg hover:shadow-blue-500/5 hover:border-blue-300 hover:-translate-y-1 transition-all duration-300 group">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-5 group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white transition-all duration-200">
                            <!-- Icon Users / People -->
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="font-display text-lg font-bold text-slate-900 mb-2">Manajemen Tim</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Kelola tim pemasaran, supervisor, dan CS dengan alur kerja berjenjang yang terstruktur dan efisien.
                        </p>
                    </div>

                    <!-- Card 2: Pantau Prospek -->
                    <div class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200/80 shadow-2xs hover:shadow-lg hover:shadow-blue-500/5 hover:border-blue-300 hover:-translate-y-1 transition-all duration-300 group">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-5 group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white transition-all duration-200">
                            <!-- Icon Trending Up / Chart -->
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <h3 class="font-display text-lg font-bold text-slate-900 mb-2">Pantau Prospek</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Pantau dan kelola prospek calon mahasiswa baru dari tahap awal hingga closing secara real-time.
                        </p>
                    </div>

                    <!-- Card 3: Akses Mudah -->
                    <div class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200/80 shadow-2xs hover:shadow-lg hover:shadow-blue-500/5 hover:border-blue-300 hover:-translate-y-1 transition-all duration-300 group">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-5 group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white transition-all duration-200">
                            <!-- Icon Calendar / Schedule -->
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="font-display text-lg font-bold text-slate-900 mb-2">Akses Mudah</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Dapat diakses kapan saja dan di mana saja, terintegrasi form kunjungan lapangan otomatis ber-GPS.
                        </p>
                    </div>

                    <!-- Card 4: Aman & Terpercaya -->
                    <div class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200/80 shadow-2xs hover:shadow-lg hover:shadow-blue-500/5 hover:border-blue-300 hover:-translate-y-1 transition-all duration-300 group">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-5 group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white transition-all duration-200">
                            <!-- Icon Shield Check -->
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="font-display text-lg font-bold text-slate-900 mb-2">Aman & Terpercaya</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Data prospek dan keuangan terlindungi dengan sistem verifikasi transaksi resmi serta audit log lengkap.
                        </p>
                    </div>

                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- SECTION 3 — PRODUCT VALUE / EXPLANATION                             -->
        <!-- Matches Reference Composition:                                      -->
        <!-- Left: Subtitle "Mengapa CRM UCIC?" + Headline + Desc + CTA Button   -->
        <!-- Center: Cutout with hands forward (foto-2.png) + action doodles     -->
        <!-- Right: 4 Checklist items with blue checkmarks + underline squiggle  -->
        <!-- =================================================================== -->
        <section id="keunggulan" class="py-16 lg:py-24 bg-white relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-8 items-center">
                    
                    <!-- Left: Text & CTA Button (4 cols) -->
                    <div class="lg:col-span-4 text-left">
                        <span class="text-xs sm:text-sm font-bold text-blue-600 tracking-wider uppercase mb-2 block font-display">
                            Mengapa CRM UCIC?
                        </span>
                        <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-black text-[#0f172a] tracking-tight leading-tight mb-4">
                            Didesain untuk Membantu Anda Tumbuh
                        </h2>
                        <p class="text-slate-600 text-sm sm:text-base leading-relaxed mb-8">
                            Dengan fitur yang lengkap dan mudah digunakan, CRM UCIC siap menjadi partner terbaik untuk mendukung proses kerja tim Anda mencapai target penerimaan mahasiswa.
                        </p>
                        <a 
                            href="#faq" 
                            class="inline-flex items-center gap-2 bg-[#0f172a] hover:bg-blue-900 text-white font-bold text-sm px-6 py-3 rounded-full transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 group font-display"
                        >
                            <span>Pelajari FAQ</span>
                            <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                    </div>

                    <!-- Center: Photo with hands forward (foto-2.png) + Doodles (4 cols) -->
                    <div class="lg:col-span-4 relative flex justify-center items-center my-4 lg:my-0">
                        
                        <!-- Soft decorative circular gradient behind cutout -->
                        <div class="absolute w-56 sm:w-64 lg:w-72 h-56 sm:h-64 lg:h-72 bg-gradient-to-tr from-blue-100/70 to-indigo-50/50 rounded-full blur-xl -z-10 animate-blob-pulse"></div>

                        <!-- Doodle Speed / Action Lines (Left & Right of hands, like reference) -->
                        <div class="absolute left-2 sm:left-4 top-1/2 -translate-y-12 z-20 pointer-events-none text-slate-700">
                            <svg class="w-7 h-7 sm:w-8 sm:h-8" viewBox="0 0 30 30" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M4 14 L14 18" />
                                <path d="M6 22 L18 20" />
                            </svg>
                        </div>
                        <div class="absolute right-2 sm:right-4 top-1/3 z-20 pointer-events-none text-slate-700">
                            <svg class="w-7 h-7 sm:w-8 sm:h-8" viewBox="0 0 30 30" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M22 6 L14 14" />
                                <path d="M26 14 L16 18" />
                                <path d="M24 22 L14 22" />
                            </svg>
                        </div>

                        <!-- Main Cutout Photo: Woman with hands forward (images/foto-2.png) -->
                        <div class="relative z-10 w-full max-w-[260px] sm:max-w-[300px] lg:max-w-[340px]">
                            <img 
                                src="{{ asset('images/foto-2.png') }}" 
                                alt="CRM UCIC Solusi Terpadu - Gestur Tangan Terbuka" 
                                class="w-full h-auto object-contain drop-shadow-lg select-none"
                                loading="eager"
                            >
                        </div>

                        <!-- Doodle Squiggle Underneath Person -->
                        <div class="absolute -bottom-2 left-6 sm:left-10 z-20 pointer-events-none text-slate-700">
                            <svg class="w-14 sm:w-16 h-6" viewBox="0 0 60 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <path d="M4 14 C15 4, 30 18, 56 6" />
                            </svg>
                        </div>

                    </div>

                    <!-- Right: 4 Checklist items + doodle curve (4 cols) -->
                    <div class="lg:col-span-4 space-y-4 sm:space-y-5">
                        
                        <!-- Checklist Item 1 -->
                        <div class="flex items-start gap-3.5 p-2 rounded-xl hover:bg-slate-50/80 transition-colors">
                            <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-display text-sm sm:text-base font-bold text-slate-900 leading-tight">Meningkatkan Produktivitas Tim</h4>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">Menghilangkan duplikasi data prospek dan mempermudah tindak lanjut harian.</p>
                            </div>
                        </div>

                        <!-- Checklist Item 2 -->
                        <div class="flex items-start gap-3.5 p-2 rounded-xl hover:bg-slate-50/80 transition-colors">
                            <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-display text-sm sm:text-base font-bold text-slate-900 leading-tight">Proses Kerja Lebih Efisien</h4>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">Handover mulus antara Sales lapangan dan Customer Service untuk proses closing.</p>
                            </div>
                        </div>

                        <!-- Checklist Item 3 -->
                        <div class="flex items-start gap-3.5 p-2 rounded-xl hover:bg-slate-50/80 transition-colors">
                            <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-display text-sm sm:text-base font-bold text-slate-900 leading-tight">Data Real-time & Akurat</h4>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">Pantau defisit target, potensi wilayah, dan rekapitulasi pembayaran akurat.</p>
                            </div>
                        </div>

                        <!-- Checklist Item 4 -->
                        <div class="flex items-start gap-3.5 p-2 rounded-xl hover:bg-slate-50/80 transition-colors">
                            <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-display text-sm sm:text-base font-bold text-slate-900 leading-tight">Dukungan Penuh untuk Pertumbuhan Anda</h4>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">Alur transparan bagi 6 level peran dari Head of Marketing hingga eksekutor lapangan.</p>
                            </div>
                        </div>

                        <!-- Doodle Squiggle Under Checklist (matches reference) -->
                        <div class="pt-3 pl-2 pointer-events-none text-slate-700">
                            <svg class="w-20 sm:w-24 h-6" viewBox="0 0 90 20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M4 14 C25 4, 50 18, 86 6" />
                            </svg>
                        </div>

                    </div>

                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- SECTION 4 — INTERACTIVE FAQ ACCORDION                               -->
        <!-- Replaces Visual Showcase with single-open, accessible FAQ accordion -->
        <!-- Content derived authentically from CRM UCIC features & roles        -->
        <!-- =================================================================== -->
        @php
            $faqs = [
                [
                    'id' => 1,
                    'question' => 'Apa saja yang dapat dikelola melalui platform CRM UCIC?',
                    'answer' => 'CRM Inbound UCIC dirancang untuk mengelola seluruh siklus penerimaan mahasiswa baru (PMB), mencakup pencatatan data prospek (sekolah SMA/SMK, korporasi, dan individu), pemantauan alur pipeline 8 tahap (dari status Baru hingga Lunas), pelaporan kunjungan lapangan ber-GPS, pembagian target berjenjang, hingga pencatatan transaksi formulir dan pembayaran termin pendidikan.'
                ],
                [
                    'id' => 2,
                    'question' => 'Siapa saja yang dapat menggunakan platform ini?',
                    'answer' => 'Platform ini menyediakan 6 tingkatan hak akses terstruktur sesuai peran kerja di kampus: Administrator (pengaturan sistem & master data), Head of Marketing (distribusi target & wilayah binaan), Supervisor (breakdown target & verifikasi kunjungan), Sales Lapangan (input prospek & visitasi sekolah), Customer Service (handover prospek & validasi transaksi PMB), serta Event Organizer (manajemen expo & pameran).'
                ],
                [
                    'id' => 3,
                    'question' => 'Bagaimana alur penanganan prospek calon mahasiswa baru?',
                    'answer' => 'Setiap prospek dicatat oleh sales lapangan dan diperbarui statusnya melalui pipeline interaktif (Baru, Kontak, Prospek, Hot Prospek). Setelah prospek siap mendaftar (closing), data dialihkan (handover) secara sistematis kepada Customer Service untuk pendampingan formulir, kelengkapan berkas, hingga pencatatan pembayaran lunas.'
                ],
                [
                    'id' => 4,
                    'question' => 'Bagaimana aktivitas kunjungan lapangan dan event expo dipantau?',
                    'answer' => 'Tim sales lapangan dapat mengisi formulir kunjungan secara langsung saat berada di lokasi sekolah atau mitra binaan, dilengkapi bukti foto dokumentasi dan deteksi koordinat GPS otomatis. Selain itu, Supervisor dan Event Organizer dapat menugaskan personil pada agenda expo atau pameran pendidikan serta memantau hasilnya secara transparan.'
                ],
                [
                    'id' => 5,
                    'question' => 'Bagaimana cara mulai masuk dan menggunakan CRM UCIC?',
                    'answer' => 'Staf dan tim pemasaran Universitas Catur Insan Cendekia dapat masuk ke sistem menggunakan akun email resmi kampus (@cic.ac.id) yang telah didaftarkan dan diberikan hak akses oleh Administrator CRM melalui tombol "Masuk ke Akun" di bagian navigasi atas.'
                ],
            ];
        @endphp

        <section id="faq" class="py-14 sm:py-20 lg:py-24 bg-gradient-to-b from-white via-slate-50/70 to-[#F8FAFC] relative overflow-hidden" x-data="{ activeFaq: null }">
            <!-- Subtle Ambient Background Glows -->
            <div class="absolute top-1/3 -left-32 w-80 sm:w-96 h-80 sm:h-96 bg-blue-100/40 rounded-full blur-3xl pointer-events-none -z-10"></div>
            <div class="absolute bottom-1/4 -right-32 w-80 sm:w-96 h-80 sm:h-96 bg-sky-100/40 rounded-full blur-3xl pointer-events-none -z-10"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Section Header (Centered, Branded, with Cute Doodles) -->
                <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-14 relative">
                    <span class="text-xs sm:text-sm font-bold text-blue-600 tracking-wider uppercase mb-2 inline-block font-display">
                        FAQ
                    </span>
                    
                    <div class="relative inline-block">
                        <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-black text-[#0f172a] tracking-tight">
                            Ada yang ingin kamu ketahui?
                        </h2>
                        <!-- Cute Doodle Sparkle (Top-right of title) -->
                        <svg class="w-6 h-6 text-amber-400 absolute -top-4 -right-7 animate-pulse hidden sm:block pointer-events-none" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/>
                        </svg>
                        <!-- Cute Doodle Sparkle (Top-left of title) -->
                        <svg class="w-4 h-4 text-blue-400 absolute -top-2 -left-5 animate-pulse hidden sm:block pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <path d="M12 3v4m0 10v4m-9-9h4m10 0h4"/>
                        </svg>
                        <!-- Cute Doodle Squiggle Underline -->
                        <div class="flex justify-center -mt-0.5 pointer-events-none text-blue-500/80">
                            <svg class="w-36 sm:w-48 h-4" viewBox="0 0 160 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M5 11 C45 3, 115 15, 155 7" />
                            </svg>
                        </div>
                    </div>

                    <p class="text-slate-600 text-xs sm:text-sm lg:text-base mt-2 max-w-lg mx-auto leading-relaxed">
                        Temukan jawaban lengkap seputar fitur, alur kerja prospek, dan manfaat platform CRM Inbound UCIC.
                    </p>

                    <!-- Cute Handwritten Note under subtitle -->
                    <div class="mt-2 text-center pointer-events-none select-none">
                        <span class="font-handwriting text-sm sm:text-base text-blue-600/90 font-bold -rotate-2 inline-block">
                            Klik salah satu pertanyaan di bawah ini ya! 👇
                        </span>
                    </div>
                </div>

                <!-- Visual Composition: Flanked by Photo Cutouts with Floating Animation & Doodles -->
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 lg:gap-8 items-end justify-center">
                    
                    <!-- Left: Woman Thinking (foto-1.png) — Floating animation & cute doodle -->
                    <div class="hidden xl:flex xl:col-span-2 justify-end items-end pb-2">
                        <div class="relative w-full max-w-[200px] select-none animate-float-left">
                            
                            <!-- Cute Doodle Thought Note above Left Girl -->
                            <div class="absolute -top-12 -right-4 z-20 pointer-events-none select-none text-left">
                                <span class="font-handwriting text-base sm:text-lg text-slate-700 -rotate-12 inline-block font-bold drop-shadow-2xs">
                                    Punya pertanyaan? 🤔
                                </span>
                                <!-- Doodle curved arrow pointing towards FAQ -->
                                <div class="pl-2 -mt-1">
                                    <svg class="w-8 h-6 text-slate-600 rotate-12" viewBox="0 0 45 35" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M5 10 C18 12, 28 20, 38 28" />
                                        <path d="M30 29 L38 28 L37 20" />
                                    </svg>
                                </div>
                            </div>

                            <div class="absolute -inset-2 bg-gradient-to-tr from-blue-100/70 to-sky-50/50 rounded-full blur-xl -z-10 animate-blob-pulse"></div>
                            <img 
                                src="{{ asset('images/foto-1.png') }}" 
                                alt="Pertanyaan Fitur CRM UCIC" 
                                class="w-full h-auto object-contain drop-shadow-md"
                                loading="eager"
                            >
                        </div>
                    </div>

                    <!-- Center: Interactive Accordion Container (12 cols on mobile/tablet/laptop, 8 cols on xl) -->
                    <div class="xl:col-span-8 w-full max-w-3xl mx-auto">
                        <div class="space-y-3.5 sm:space-y-4">
                            @foreach($faqs as $faq)
                            <div 
                                class="bg-white rounded-2xl sm:rounded-3xl border transition-all duration-300 group overflow-hidden"
                                :class="activeFaq === {{ $faq['id'] }} ? 'border-blue-500/50 shadow-md shadow-blue-500/5 ring-1 ring-blue-500/10' : 'border-slate-200/80 hover:border-slate-300 shadow-2xs hover:bg-slate-50/40'"
                            >
                                <!-- Accordion Header Trigger Button -->
                                <button 
                                    type="button"
                                    id="faq-header-{{ $faq['id'] }}"
                                    @click="activeFaq = (activeFaq === {{ $faq['id'] }} ? null : {{ $faq['id'] }})"
                                    :aria-expanded="activeFaq === {{ $faq['id'] }} ? 'true' : 'false'"
                                    aria-controls="faq-answer-{{ $faq['id'] }}"
                                    class="w-full flex items-center justify-between text-left gap-3.5 sm:gap-5 px-5 sm:px-7 py-4.5 sm:py-5 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-inset rounded-2xl sm:rounded-3xl cursor-pointer transition-colors"
                                >
                                    <span 
                                        class="font-display text-sm sm:text-base lg:text-lg font-bold transition-colors duration-200 leading-snug"
                                        :class="activeFaq === {{ $faq['id'] }} ? 'text-blue-600' : 'text-slate-900 group-hover:text-blue-600'"
                                    >
                                        {{ $faq['question'] }}
                                    </span>

                                    <!-- Interactive + / − Indicator Pill -->
                                    <span 
                                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-full flex items-center justify-center shrink-0 transition-all duration-300 ease-out"
                                        :class="activeFaq === {{ $faq['id'] }} ? 'bg-blue-600 text-white shadow-xs rotate-180' : 'bg-slate-100 text-slate-600 group-hover:bg-blue-50 group-hover:text-blue-600'"
                                        aria-hidden="true"
                                    >
                                        <!-- Plus Icon (Closed) -->
                                        <svg x-show="activeFaq !== {{ $faq['id'] }}" class="w-4 h-4 sm:w-4.5 sm:h-4.5 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <!-- Minus Icon (Open) -->
                                        <svg x-show="activeFaq === {{ $faq['id'] }}" x-cloak class="w-4 h-4 sm:w-4.5 sm:h-4.5 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                                        </svg>
                                    </span>
                                </button>

                                <!-- Accordion Body Answer Panel with Silky Smooth Grid Animation -->
                                <div 
                                    id="faq-answer-{{ $faq['id'] }}" 
                                    role="region" 
                                    aria-labelledby="faq-header-{{ $faq['id'] }}"
                                    class="faq-content-grid"
                                    :class="activeFaq === {{ $faq['id'] }} ? 'is-open' : 'is-closed'"
                                >
                                    <div class="overflow-hidden">
                                        <div 
                                            class="px-5 sm:px-7 pb-5 sm:pb-6 pt-2 text-slate-600 text-xs sm:text-sm lg:text-base leading-relaxed border-t border-slate-100/90 transition-all duration-300 ease-out"
                                            :class="activeFaq === {{ $faq['id'] }} ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-2 pointer-events-none'"
                                        >
                                            {{ $faq['answer'] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Right: Photo provided by user (foto-faq-kanan.png) — Floating animation & cute doodle -->
                    <div class="hidden xl:flex xl:col-span-2 justify-start items-end pb-2">
                        <div class="relative w-full max-w-[200px] select-none animate-float-right">
                            
                            <!-- Cute Doodle Answer Note above Right Girl -->
                            <div class="absolute -top-12 -left-4 z-20 pointer-events-none select-none text-right">
                                <span class="font-handwriting text-base sm:text-lg text-blue-600 rotate-6 inline-block font-bold drop-shadow-2xs">
                                    Jawaban lengkapnya! 💡
                                </span>
                                <!-- Doodle curved arrow pointing towards FAQ -->
                                <div class="flex justify-end pr-2 -mt-1">
                                    <svg class="w-8 h-6 text-blue-500 -rotate-12" viewBox="0 0 45 35" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M40 8 C28 14, 18 22, 8 28" />
                                        <path d="M8 20 L8 28 L16 27" />
                                    </svg>
                                </div>
                            </div>

                            <div class="absolute -inset-2 bg-gradient-to-tr from-sky-100/70 to-indigo-50/50 rounded-full blur-xl -z-10 animate-blob-pulse"></div>
                            <img 
                                src="{{ asset('images/foto-faq-kanan.png') }}" 
                                alt="Jawaban Solusi CRM UCIC" 
                                class="w-full h-auto object-contain drop-shadow-md scale-x-[-1]"
                                loading="eager"
                            >
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- =================================================================== -->
        <!-- SECTION 5 — DARK CTA / TRUST / VALUE SECTION                        -->
        <!-- Matches Reference Composition: Deep Navy Section with               -->
        <!-- Left: Eyebrow + Headline + Paragraph + Button                       -->
        <!-- Center: 3 Statistics Columns (300+, 150+, 99%)                      -->
        <!-- Right: Man in Suit sitting on stool (foto-4.png)                    -->
        <!-- =================================================================== -->
        <section class="py-10 sm:py-16 lg:py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Dark Navy Card Container -->
                <div class="bg-[#0b162c] rounded-3xl sm:rounded-[2.5rem] px-5 sm:px-10 lg:px-14 pt-8 sm:pt-14 pb-0 relative overflow-hidden border border-slate-800 shadow-2xl">
                    
                    <!-- Ambient Subtle Radial Glow -->
                    <div class="absolute -top-32 -left-32 w-80 sm:w-96 h-80 sm:h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute top-1/2 right-1/4 w-72 sm:w-80 h-72 sm:h-80 bg-indigo-600/15 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-end">
                        
                        <!-- Left: Headline & CTA (4 cols) -->
                        <div class="lg:col-span-4 text-left pb-6 sm:pb-12 z-10">
                            <span class="text-xs font-bold text-blue-400 tracking-wider uppercase mb-2 block font-display">
                                Dipercaya oleh Banyak Institusi
                            </span>
                            <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-black text-white tracking-tight leading-tight mb-3 sm:mb-4">
                                Bersama CRM UCIC,<br>
                                Capai Lebih Banyak
                            </h2>
                            <p class="text-slate-300 text-xs sm:text-sm leading-relaxed mb-6">
                                Ratusan sekolah binaan dan calon mahasiswa baru telah terkelola melalui sistem pemasaran, follow-up, dan validasi transaksi terpadu kami.
                            </p>
                            <a 
                                href="{{ route('login') }}" 
                                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs sm:text-sm px-6 py-3 rounded-full transition-all duration-200 shadow-md shadow-blue-600/30 hover:-translate-y-0.5 active:translate-y-0 group font-display"
                            >
                                <span>Mulai Sekarang</span>
                                <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                        </div>

                        <!-- Middle: 3 Statistics Columns (4 cols) -->
                        <div class="lg:col-span-4 grid grid-cols-3 gap-2 sm:gap-4 pb-6 sm:pb-12 z-10 text-center">
                            
                            <!-- Stat 1: 300+ Sekolah / Institusi -->
                            <div class="flex flex-col items-center">
                                <span class="font-display text-xl sm:text-2xl lg:text-3xl font-black text-white tracking-tight">300+</span>
                                <div class="w-7 h-7 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center my-1.5 sm:my-2">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <span class="text-[9px] sm:text-xs text-slate-300 leading-tight">Sekolah Mitra & Binaan</span>
                            </div>

                            <!-- Stat 2: 150+ Wilayah / Pengguna -->
                            <div class="flex flex-col items-center">
                                <span class="font-display text-xl sm:text-2xl lg:text-3xl font-black text-white tracking-tight">150+</span>
                                <div class="w-7 h-7 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center my-1.5 sm:my-2">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <span class="text-[9px] sm:text-xs text-slate-300 leading-tight">Wilayah Binaan Tercover</span>
                            </div>

                            <!-- Stat 3: 99% / Real-time Uptime -->
                            <div class="flex flex-col items-center">
                                <span class="font-display text-xl sm:text-2xl lg:text-3xl font-black text-white tracking-tight">99%</span>
                                <div class="w-7 h-7 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center my-1.5 sm:my-2">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <span class="text-[9px] sm:text-xs text-slate-300 leading-tight">Uptime & Real-time Sync</span>
                            </div>

                        </div>

                        <!-- Right: Man in Suit sitting (foto-4.png) — 4 cols -->
                        <div class="lg:col-span-4 flex justify-center lg:justify-end items-end z-10 pt-2 sm:pt-0">
                            <div class="w-full max-w-[240px] sm:max-w-[290px] lg:max-w-[340px]">
                                <img 
                                    src="{{ asset('images/foto-4.png') }}" 
                                    alt="Eksekutif Pemasaran CRM UCIC - Bersama Rakit AI" 
                                    class="w-full h-auto object-contain drop-shadow-2xl select-none"
                                    loading="eager"
                                >
                            </div>
                        </div>

                    </div>

                </div>

            </div>
        </section>

        <!-- =================================================================== -->
        <!-- SECTION 6 — PRE-FOOTER (Contact Banner)                             -->
        <!-- Matches Reference Composition:                                      -->
        <!-- Light Banner Card + Paper Airplane Icon + "Hubungi Kami Sekarang"   -->
        <!-- + CTA Button + Decorative Curved Line                               -->
        <!-- =================================================================== -->
        <section id="kontak" class="py-12 sm:py-16 bg-[#F8FAFC]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-10 lg:p-12 shadow-2xs flex flex-col md:flex-row justify-between items-center gap-6 sm:gap-8 relative overflow-hidden hover:shadow-md transition-shadow">
                    
                    <!-- Left: Paper Airplane Icon + Title + Description -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6 z-10 text-left w-full md:w-auto">
                        <!-- Blue Paper Airplane / Send Icon -->
                        <div class="w-12 sm:w-14 h-12 sm:h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100 shadow-2xs">
                            <svg class="w-6 sm:w-7 h-6 sm:h-7 transform -rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </div>

                        <div>
                            <span class="text-xs font-bold text-blue-600 tracking-wider uppercase block font-display">
                                Siap Memulai?
                            </span>
                            <h3 class="font-display text-xl sm:text-2xl lg:text-3xl font-black text-[#0f172a] tracking-tight leading-snug">
                                Hubungi Kami Sekarang
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-xl leading-relaxed">
                                Jangan ragu untuk menghubungi tim kami jika Anda memiliki pertanyaan seputar alur kerja atau ingin mengetahui lebih lanjut tentang CRM UCIC.
                            </p>
                        </div>
                    </div>

                    <!-- Right: Button & Decorative Wave Line -->
                    <div class="flex items-center gap-4 shrink-0 z-10 w-full md:w-auto justify-start sm:justify-end">
                        <a 
                            href="https://wa.me/6281234567890?text=Halo%20Admin%20CRM%20UCIC,%20saya%20ingin%20bertanya%20seputar%20sistem" 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            class="inline-flex items-center justify-center gap-2 bg-[#0f172a] hover:bg-blue-900 text-white font-bold text-sm px-7 py-3.5 rounded-full transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 group font-display w-full sm:w-auto text-center"
                        >
                            <span>Hubungi Kami</span>
                            <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                    </div>

                    <!-- Decorative Subtle Curved Wave Line in Bottom Right Corner (matches reference) -->
                    <div class="absolute -bottom-6 -right-6 pointer-events-none text-blue-200/60 hidden sm:block">
                        <svg class="w-48 h-28" viewBox="0 0 160 80" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <path d="M10 70 C50 10, 110 90, 150 20" />
                        </svg>
                    </div>

                </div>

            </div>
        </section>
    </main>

    <!-- =================================================================== -->
    <!-- SECTION 7 — FOOTER                                                  -->
    <!-- Consistent with Application Design System & Branding                -->
    <!-- =================================================================== -->
    <footer class="bg-white border-t border-slate-200/80 py-10 sm:py-12 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-6">
                
                <!-- Logo & Brand Info -->
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" class="h-9 sm:h-10 w-auto object-contain">
                    <div>
                        <h4 class="font-display font-black text-slate-900 text-sm sm:text-base leading-none">UCIC CAMPUS</h4>
                        <span class="text-[11px] sm:text-xs font-semibold text-slate-500 tracking-wider">CRM Inbound Marketing & PMB Platform</span>
                    </div>
                </div>

                <!-- Copyright & Version -->
                <div class="text-center sm:text-right">
                    <p class="text-xs text-slate-400 font-medium flex flex-wrap items-center justify-center sm:justify-end gap-2">
                        <span>&copy; {{ date('Y') }} Universitas Catur Insan Cendekia. All rights reserved.</span>
                        <span class="text-slate-300 select-none">•</span>
                        <span class="font-mono text-[11px] text-slate-400 font-medium">v.1.1.0</span>
                    </p>
                </div>

            </div>
        </div>
    </footer>

</body>
</html>
