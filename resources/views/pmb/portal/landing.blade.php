<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>PMB UCIC {{ date('Y') }}/{{ date('Y') + 1 }} – Kampus Teknologi AI #1 di Cirebon</title>
    <meta name="description" content="Pendaftaran Online Penerimaan Mahasiswa Baru Universitas Catur Insan Cendekia (UCIC) Cirebon. Kampus Teknologi AI, Bisnis Digital, DKV, dan Sistem Informasi.">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-ucic.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
        .bg-ucic-blue { background-color: #0b3d82; }
        .bg-ucic-dark { background-color: #072b5c; }
        .border-ucic-blue { border-color: #0b3d82; }
    </style>
</head>
<body class="min-h-full bg-[#f8fafc] text-slate-800 antialiased selection:bg-[#0b3d82] selection:text-white flex flex-col justify-between"
      x-data="{
          mobileMenuOpen: false,
          days: 7,
          hours: 13,
          minutes: 16,
          seconds: 5,
          initCountdown() {
              const targetDate = new Date('2026-10-30T23:59:59').getTime();
              const update = () => {
                  const now = new Date().getTime();
                  const diff = Math.max(0, targetDate - now);
                  this.days = String(Math.floor(diff / (1000 * 60 * 60 * 24))).padStart(2, '0');
                  this.hours = String(Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0');
                  this.minutes = String(Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
                  this.seconds = String(Math.floor((diff % (1000 * 60)) / 1000)).padStart(2, '0');
              };
              update();
              setInterval(update, 1000);
          }
      }"
      x-init="initCountdown()">

    <!-- =================================================================== -->
    <!-- 1. HEADER                                                           -->
    <!-- =================================================================== -->
    <header class="w-full bg-[#0b3d82] text-white sticky top-0 z-50 border-b border-white/10 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-18 flex items-center justify-between">
            <!-- Brand Logo & Name -->
            <a href="#beranda" class="flex items-center gap-3 group">
                <img src="{{ asset('images/logo-no-text.png') }}" alt="Logo UCIC" class="h-9 sm:h-10 w-auto object-contain shrink-0 transition-transform group-hover:scale-105">
                <div>
                    <span class="text-sm sm:text-base font-extrabold text-white leading-tight font-display block">Universitas Catur Insan Cendekia</span>
                    <span class="text-[11px] font-bold text-[#facc15] block">#Kampus Teknologi AI</span>
                </div>
            </a>

            <!-- Right Buttons: Tanya CS & Daftar Sekarang / Login CAMABA / Dashboard -->
            <div class="flex items-center gap-2.5">
                <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=halo+kak%2C+saya+ingin+bertanya+berkaitan+dengan+PMB+UCIC&type=phone_number&app_absent=0"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-[#10b981] hover:bg-[#059669] text-white shadow-sm transition active:scale-95">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <span>Tanya CS</span>
                </a>

                @auth
                    @if(strtolower(auth()->user()->role) === 'camaba')
                        <a href="{{ route('pmb.akun.dashboard') }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-[#f59e0b] hover:bg-[#d97706] text-white shadow-sm transition active:scale-95 font-display">
                            <span>Dashboard</span>
                        </a>
                    @else
                        <a href="{{ route('pmb.akun.dashboard') }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-[#f59e0b] hover:bg-[#d97706] text-white shadow-sm transition active:scale-95 font-display">
                            <span>Dashboard</span>
                        </a>
                    @endif
                @else
                    <a href="{{ route('pmb.login') }}"
                       class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-white/10 hover:bg-white/20 text-white border border-white/20 shadow-sm transition active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        <span>Login CAMABA</span>
                    </a>
                    <a href="{{ route('pmb.register') }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-[#f59e0b] hover:bg-[#d97706] text-white shadow-sm transition active:scale-95 font-display">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Daftar Sekarang</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main class="flex-grow">
        <!-- =================================================================== -->
        <!-- 2. HERO SECTION & PROMO CARD                                        -->
        <!-- =================================================================== -->
        <section id="beranda" class="bg-[#0b3d82] text-white pt-10 pb-16 px-4 sm:px-6 relative overflow-hidden">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">

                    <!-- Left Column: Headline, Subheading, Countdown, CTAs -->
                    <div class="lg:col-span-7 space-y-6">
                        <!-- Label Gelombang -->
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-white/10 text-white border border-white/15">
                            <span class="w-2 h-2 rounded-full bg-[#f59e0b] animate-pulse"></span>
                            <span>PMB 2027/2028 – Gelombang 1 Beasiswa Rektor TERBUKA</span>
                        </div>

                        <!-- Title -->
                        <h1 class="text-3xl sm:text-5xl font-black font-display tracking-tight leading-tight">
                            Mulai Karir Cerdasmu<br>di Era <span class="text-[#f59e0b]">Teknologi AI</span>
                        </h1>

                        <!-- Description -->
                        <p class="text-sm sm:text-base text-blue-100 max-w-xl leading-relaxed">
                            Kuliah di UCIC Cirebon – kampus teknologi AI pertama di Cirebon. Dapatkan gelar sarjana sekaligus bootcamp AI bersertifikat industri dalam satu paket terjangkau.
                        </p>

                        <!-- Countdown Header -->
                        <div class="space-y-2.5 pt-2">
                            <div class="flex items-center gap-2 text-[11px] font-extrabold tracking-wider uppercase text-blue-200">
                                <svg class="w-4 h-4 text-[#f59e0b]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>PROMO GELOMBANG 1 BEASISWA REKTOR BERAKHIR DALAM</span>
                            </div>

                            <!-- Countdown Timer Boxes -->
                            <div class="flex items-center gap-3">
                                <div class="w-14 sm:w-16 h-14 sm:h-16 rounded-xl bg-white/10 border border-white/15 flex flex-col items-center justify-center">
                                    <span class="text-xl sm:text-2xl font-black font-display text-white" x-text="days">07</span>
                                    <span class="text-[9px] font-bold text-blue-200 uppercase">HARI</span>
                                </div>
                                <div class="w-14 sm:w-16 h-14 sm:h-16 rounded-xl bg-white/10 border border-white/15 flex flex-col items-center justify-center">
                                    <span class="text-xl sm:text-2xl font-black font-display text-white" x-text="hours">13</span>
                                    <span class="text-[9px] font-bold text-blue-200 uppercase">JAM</span>
                                </div>
                                <div class="w-14 sm:w-16 h-14 sm:h-16 rounded-xl bg-white/10 border border-white/15 flex flex-col items-center justify-center">
                                    <span class="text-xl sm:text-2xl font-black font-display text-white" x-text="minutes">16</span>
                                    <span class="text-[9px] font-bold text-blue-200 uppercase">MENIT</span>
                                </div>
                                <div class="w-14 sm:w-16 h-14 sm:h-16 rounded-xl bg-white/10 border border-white/15 flex flex-col items-center justify-center">
                                    <span class="text-xl sm:text-2xl font-black font-display text-white" x-text="seconds">05</span>
                                    <span class="text-[9px] font-bold text-blue-200 uppercase">DETIK</span>
                                </div>
                            </div>
                        </div>

                        <!-- CTA Buttons -->
                        <div class="flex flex-wrap items-center gap-3 pt-3">
                            <a href="{{ route('pmb.register') }}"
                               class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl font-bold text-sm bg-[#f59e0b] hover:bg-[#d97706] text-slate-900 shadow-md transition active:scale-95 font-display">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                    <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zm0 8.55L4.54 9 12 4.95 19.46 9 12 11.55zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/>
                                </svg>
                                <span>Daftar Online Sekarang</span>
                            </a>

                            <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=halo+kak%2C+saya+ingin+konsultasi+gratis+PMB+UCIC"
                               target="_blank"
                               class="inline-flex items-center gap-2 px-5 py-3.5 rounded-xl font-bold text-sm bg-[#10b981] hover:bg-[#059669] text-white shadow-md transition active:scale-95 font-display">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <span>Konsultasi Gratis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Right Column: Promo Beasiswa Card -->
                    <div class="lg:col-span-5 relative">
                        <!-- Badge Promo -->
                        <div class="inline-block px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-[#f59e0b] text-slate-900 mb-2">
                            PROMO
                        </div>

                        <div class="p-6 sm:p-7 rounded-3xl bg-white/10 backdrop-blur-md border border-white/20 shadow-xl space-y-5">
                            <!-- Big 60% Header -->
                            <div class="text-center pb-2 border-b border-white/10">
                                <span class="text-5xl sm:text-6xl font-black font-display text-[#f59e0b] block leading-none">60%</span>
                                <span class="text-base font-bold text-white block mt-1">Diskon UKT</span>
                                <span class="text-xs text-blue-200 block">Khusus Pendaftaran Gelombang 1 Beasiswa Rektor</span>
                            </div>

                            <!-- Benefit Checklist -->
                            <div class="space-y-2.5 text-xs sm:text-sm text-blue-50">
                                <div class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-[#f59e0b] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Gelar Sarjana + Skill Premium AI & Robotik</span>
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-[#f59e0b] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Sertifikat Bootcamp diakui industri</span>
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-[#f59e0b] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Lab Gaming RAM 32GB, NVIDIA RTX 3060</span>
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-[#f59e0b] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Kelas AI, IoT & Robotik terintegrasi</span>
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-[#f59e0b] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Free akses Rakit AI (platform edukasi teknologi)</span>
                                </div>
                            </div>

                            <!-- Sisa Kuota Alert (Clean SVG, No Emoji) -->
                            <div class="p-2.5 rounded-xl bg-amber-500/15 border border-amber-400/30 text-center text-xs text-[#f59e0b] font-semibold flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Sisa kuota: 50 mahasiswa – jangan sampai kehabisan!</span>
                            </div>

                            <!-- Claim Button -->
                            <a href="{{ route('pmb.register') }}"
                               class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-xl font-bold text-sm bg-[#f59e0b] hover:bg-[#d97706] text-slate-900 shadow-md transition active:scale-95 font-display">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <span>Claim Diskon 60% Sekarang</span>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- 3. RINGKASAN KEUNGGULAN (STATS HIGHLIGHT STRIP)                     -->
        <!-- =================================================================== -->
        <section class="bg-[#072b5c] text-white py-8 px-4 sm:px-6 border-y border-white/10">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-6 text-center">
                    <!-- Stat 1 -->
                    <div>
                        <span class="text-3xl sm:text-4xl font-black font-display text-white block">60%</span>
                        <span class="text-xs text-blue-200 mt-1 block">Diskon UKT Gelombang 1 Beasiswa Rektor</span>
                    </div>
                    <!-- Stat 2 -->
                    <div>
                        <span class="text-3xl sm:text-4xl font-black font-display text-white block">9</span>
                        <span class="text-xs text-blue-200 mt-1 block">Program Studi Pilihan</span>
                    </div>
                    <!-- Stat 3 -->
                    <div>
                        <span class="text-3xl sm:text-4xl font-black font-display text-white block">RTX 3060</span>
                        <span class="text-xs text-blue-200 mt-1 block">Lab Gaming Spec</span>
                    </div>
                    <!-- Stat 4 -->
                    <div>
                        <span class="text-3xl sm:text-4xl font-black font-display text-white block">100%</span>
                        <span class="text-xs text-blue-200 mt-1 block">Bootcamp AI Bersertifikat</span>
                    </div>
                    <!-- Stat 5 -->
                    <div class="col-span-2 md:col-span-1">
                        <span class="text-3xl sm:text-4xl font-black font-display text-white block">Cirebon</span>
                        <span class="text-xs text-blue-200 mt-1 block">Kampus Teknologi AI #1</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- 4. BAGIAN KENAPA UCIC? (6 KEUNGGULAN LENGKAP)                       -->
        <!-- =================================================================== -->
        <section class="py-16 sm:py-20 px-4 sm:px-6 bg-[#f8fafc]">
            <div class="max-w-7xl mx-auto">
                <!-- Section Header -->
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-blue-100 text-blue-700 mb-3">
                        KENAPA UCIC?
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black font-display text-slate-900 leading-tight">
                        Kuliah atau Bootcamp?<br>di UCIC kamu <span class="text-blue-600">Dapat Keduanya!</span>
                    </h2>
                    <p class="text-sm text-slate-600 mt-3 leading-relaxed">
                        Satu-satunya kampus di Cirebon yang mengintegrasikan pendidikan formal dengan program bootcamp teknologi AI langsung dari industri.
                    </p>
                </div>

                <!-- 6 Cards Grid with Orange Top Accent & Rounded Corners -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Keunggulan 1: Kurikulum AI & Teknologi Industri -->
                    <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm border-t-4 border-t-[#f59e0b] flex flex-col justify-between">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-blue-500 text-white flex items-center justify-center text-xl mb-4 shadow-sm">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 font-display">Kurikulum AI & Teknologi Industri</h3>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                                Belajar AI, IoT, Robotik, dan pemrograman terkini sesuai kebutuhan perusahaan multinasional.
                            </p>
                        </div>
                    </div>

                    <!-- Keunggulan 2: Lab Gaming Kelas Dewa -->
                    <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm border-t-4 border-t-[#f59e0b] flex flex-col justify-between">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xl mb-4 shadow-sm">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 font-display">Lab Gaming Kelas Dewa</h3>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                                Lab komputer dengan spesifikasi gaming: RAM 32GB, AMD Ryzen 7, dan NVIDIA GeForce RTX 3060.
                            </p>
                        </div>
                    </div>

                    <!-- Keunggulan 3: Bootcamp AI Bersertifikat -->
                    <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm border-t-4 border-t-[#f59e0b] flex flex-col justify-between">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-blue-700 text-white flex items-center justify-center text-xl mb-4 shadow-sm">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 font-display">Bootcamp AI Bersertifikat</h3>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                                Seluruh mahasiswa mendapatkan pelatihan bootcamp langsung bersama praktisi dan sertifikat industri.
                            </p>
                        </div>
                    </div>

                    <!-- Keunggulan 4: Kolaborasi Internasional -->
                    <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm border-t-4 border-t-[#f59e0b] flex flex-col justify-between">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-blue-800 text-white flex items-center justify-center text-xl mb-4 shadow-sm">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 font-display">Kolaborasi Internasional</h3>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                                Seminar dan workshop internasional bersama universitas terkemuka luar negeri untuk memperluas jejaring global.
                            </p>
                        </div>
                    </div>

                    <!-- Keunggulan 5: Fasilitas Kelas Dunia -->
                    <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm border-t-4 border-t-[#f59e0b] flex flex-col justify-between">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xl mb-4 shadow-sm">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 font-display">Fasilitas Kelas Dunia</h3>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                                Ruang kuliah modern ber-AC, perpustakaan digital, smart classroom, dan area kreativitas mahasiswa.
                            </p>
                        </div>
                    </div>

                    <!-- Keunggulan 6: OCW & Mini Class Gratis -->
                    <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm border-t-4 border-t-[#f59e0b] flex flex-col justify-between">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-blue-700 text-white flex items-center justify-center text-xl mb-4 shadow-sm">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 font-display">OCW & Mini Class Gratis</h3>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                                Akses materi perkuliahan terbuka (OpenCourseWare) dan program mini class peningkatan skill secara gratis.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- 5. PROGRAM STUDI (11 ENTRI LENGKAP)                                 -->
        <!-- =================================================================== -->
        <section id="prodi" class="py-16 sm:py-20 px-4 sm:px-6 bg-white border-t border-slate-200/80">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="text-center max-w-3xl mx-auto mb-12">
                    <h2 class="text-3xl sm:text-4xl font-black font-display text-slate-900 leading-tight">
                        Pilih Jurusan Favoritmu,<br><span class="text-blue-600">Masa Depanmu Dimulai Di Sini!</span>
                    </h2>
                    <p class="text-sm text-slate-500 mt-2">
                        9 program studi yang siap mencetak generasi profesional dan inovator masa depan.
                    </p>
                </div>

                @php
                    $prodiList = [
                        [
                            'jenjang' => 'S1',
                            'nama' => 'Teknik Informatika',
                            'desc' => 'Menjadi ahli teknologi informasi dan pengembangan software siap kerja di era digital.'
                        ],
                        [
                            'jenjang' => 'S1',
                            'nama' => 'Sistem Informasi',
                            'desc' => 'Fokus pada perancangan dan pengelolaan sistem informasi modern untuk industri.'
                        ],
                        [
                            'jenjang' => 'S1',
                            'nama' => 'Desain Komunikasi Visual',
                            'desc' => 'Mengembangkan talenta seni dan desain kreatif untuk industri media dan hiburan.'
                        ],
                        [
                            'jenjang' => 'S1',
                            'nama' => 'Akuntansi',
                            'desc' => 'Mencetak akuntan profesional dengan kemampuan analisis keuangan yang unggul.'
                        ],
                        [
                            'jenjang' => 'S1',
                            'nama' => 'Manajemen',
                            'desc' => 'Mempersiapkan pemimpin bisnis masa depan dengan kurikulum manajemen komprehensif.'
                        ],
                        [
                            'jenjang' => 'S2',
                            'nama' => 'Manajemen',
                            'desc' => 'Mempersiapkan pemimpin bisnis masa depan dengan kurikulum manajemen komprehensif.'
                        ],
                        [
                            'jenjang' => 'S1',
                            'nama' => 'Bisnis Digital',
                            'desc' => 'Mempersiapkan entrepreneur digital dan ahli strategi bisnis di era teknologi.'
                        ],
                        [
                            'jenjang' => 'D3',
                            'nama' => 'Manajemen Bisnis',
                            'desc' => 'Tenaga ahli madya bidang manajemen dan operasional bisnis yang profesional.'
                        ],
                        [
                            'jenjang' => 'D3',
                            'nama' => 'Manajemen Informatika',
                            'desc' => 'Tenaga ahli madya terampil dalam rekayasa aplikasi dan operasional IT industri.'
                        ],
                        [
                            'jenjang' => 'S1',
                            'nama' => 'Pendidikan Kepelatihan Olahraga',
                            'desc' => 'Mencetak pendidik dan pelatih olahraga profesional dengan sains keolahragaan modern.'
                        ],
                        [
                            'jenjang' => 'S1',
                            'nama' => 'Pendidikan Matematika',
                            'desc' => 'Mencetak pendidik matematika unggul dan adaptif terhadap teknologi pendidikan.'
                        ],
                    ];
                @endphp

                <!-- Program Studi Cards (Clean minimal white with rounded pill tag) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($prodiList as $item)
                        <div class="p-6 rounded-2xl bg-white border border-slate-200/90 shadow-xs hover:shadow-md transition flex flex-col justify-between">
                            <div>
                                <!-- Jenjang Pill -->
                                <div class="w-12 h-6 rounded-md bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-600 mb-4 font-display">
                                    {{ $item['jenjang'] }}
                                </div>

                                <h3 class="text-base font-bold text-slate-900 font-display">{{ $item['nama'] }}</h3>
                                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                                    {{ $item['desc'] }}
                                </p>
                            </div>

                            <div class="mt-6 pt-2">
                                <a href="{{ auth()->check() ? route('pmb.akun.status') : route('pmb.register') }}"
                                   class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800 transition">
                                    <span>Lihat biaya</span>
                                    <span>&rarr;</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- 6. ALUR PMB 6 TAHAP                                                 -->
        <!-- =================================================================== -->
        <section class="bg-[#0b3d82] text-white py-16 sm:py-20 px-4 sm:px-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="max-w-2xl mb-12">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-white/10 text-[#f59e0b] border border-[#f59e0b]/20 mb-3">
                        ALUR PMB
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black font-display text-white leading-tight">
                        Cara Daftar yang<br><span class="text-[#f59e0b]">Mudah & Cepat</span>
                    </h2>
                    <p class="text-sm text-blue-200 mt-2">
                        Proses pendaftaran 100% online, selesai dalam hitungan menit.
                    </p>
                </div>

                <!-- 6 Steps Horizontal Grid with Yellow Circles -->
                <div class="grid grid-cols-2 md:grid-cols-6 gap-6 text-center">
                    <!-- Step 1 -->
                    <div class="flex flex-col items-center">
                        <div class="w-14 h-14 rounded-full bg-[#f59e0b] text-slate-900 font-black text-lg flex items-center justify-center mb-4 shadow-md font-display">
                            1
                        </div>
                        <h3 class="text-sm font-bold text-white">Sosialisasi</h3>
                        <p class="text-[11px] text-blue-200 mt-1">Kenali UCIC dan program unggulan</p>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex flex-col items-center">
                        <div class="w-14 h-14 rounded-full bg-[#f59e0b] text-slate-900 font-black text-lg flex items-center justify-center mb-4 shadow-md font-display">
                            2
                        </div>
                        <h3 class="text-sm font-bold text-white">Daftar Online</h3>
                        <p class="text-[11px] text-blue-200 mt-1">Isi formulir di pmb.cic.ac.id</p>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex flex-col items-center">
                        <div class="w-14 h-14 rounded-full bg-[#f59e0b] text-slate-900 font-black text-lg flex items-center justify-center mb-4 shadow-md font-display">
                            3
                        </div>
                        <h3 class="text-sm font-bold text-white">Simulasi Ujian</h3>
                        <p class="text-[11px] text-blue-200 mt-1">Ikuti ujian TPA online</p>
                    </div>

                    <!-- Step 4 -->
                    <div class="flex flex-col items-center">
                        <div class="w-14 h-14 rounded-full bg-[#f59e0b] text-slate-900 font-black text-lg flex items-center justify-center mb-4 shadow-md font-display">
                            4
                        </div>
                        <h3 class="text-sm font-bold text-white">Lihat Hasil</h3>
                        <p class="text-[11px] text-blue-200 mt-1">Cek pengumuman kelulusan</p>
                    </div>

                    <!-- Step 5 -->
                    <div class="flex flex-col items-center">
                        <div class="w-14 h-14 rounded-full bg-[#f59e0b] text-slate-900 font-black text-lg flex items-center justify-center mb-4 shadow-md font-display">
                            5
                        </div>
                        <h3 class="text-sm font-bold text-white">Pembayaran</h3>
                        <p class="text-[11px] text-blue-200 mt-1">Bayar dengan diskon 60%</p>
                    </div>

                    <!-- Step 6 -->
                    <div class="flex flex-col items-center">
                        <div class="w-14 h-14 rounded-full bg-[#f59e0b] text-slate-900 font-black text-lg flex items-center justify-center mb-4 shadow-md font-display">
                            6
                        </div>
                        <h3 class="text-sm font-bold text-white">Upload Berkas</h3>
                        <p class="text-[11px] text-blue-200 mt-1">Lengkapi dokumen persyaratan</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- 7. TESTIMONI MAHASISWA                                              -->
        <!-- =================================================================== -->
        <section class="py-16 sm:py-20 px-4 sm:px-6 bg-[#f8fafc]">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-blue-100 text-blue-700 mb-3">
                        TESTIMONI
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black font-display text-slate-900 leading-tight">
                        Apa Kata Mahasiswa <span class="text-blue-600">UCIC?</span>
                    </h2>
                    <p class="text-sm text-slate-500 mt-2">
                        Cerita nyata dari mahasiswa yang sudah membuktikan kualitas UCIC.
                    </p>
                </div>

                <!-- 3 Testimonial Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Testimonial 1 -->
                    <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm flex flex-col justify-between">
                        <div>
                            <!-- Stars (SVG Icons, No Emoji) -->
                            <div class="flex items-center gap-1 text-[#f59e0b] mb-3">
                                @for($i = 0; $i < 5; $i++)
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed italic">
                                "Daftar UCIC adalah keputusan terbaik! Dapat bootcamp AI langsung dari praktisi industri, lab-nya juga keren banget. Langsung bisa praktek dari hari pertama kuliah."
                            </p>
                        </div>

                        <div class="mt-6 flex items-center gap-3 pt-4 border-t border-slate-100">
                            <div class="w-10 h-10 rounded-full bg-[#0b3d82] text-white font-bold text-xs flex items-center justify-center shrink-0">
                                R
                            </div>
                            <div>
                                <span class="text-xs font-bold text-slate-900 block">Rizky A.</span>
                                <span class="text-[11px] text-slate-400 block">S1 Teknik Informatika</span>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm flex flex-col justify-between">
                        <div>
                            <!-- Stars -->
                            <div class="flex items-center gap-1 text-[#f59e0b] mb-3">
                                @for($i = 0; $i < 5; $i++)
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed italic">
                                "Kuliah sambil dapat sertifikat bootcamp AI – mana ada kampus lain yang bisa begini? Plus fasilitas lab gamenya keren abis. Worth it banget!"
                            </p>
                        </div>

                        <div class="mt-6 flex items-center gap-3 pt-4 border-t border-slate-100">
                            <div class="w-10 h-10 rounded-full bg-[#f59e0b] text-slate-900 font-bold text-xs flex items-center justify-center shrink-0">
                                S
                            </div>
                            <div>
                                <span class="text-xs font-bold text-slate-900 block">Sari M.</span>
                                <span class="text-[11px] text-slate-400 block">S1 Sistem Informasi</span>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm flex flex-col justify-between">
                        <div>
                            <!-- Stars -->
                            <div class="flex items-center gap-1 text-[#f59e0b] mb-3">
                                @for($i = 0; $i < 5; $i++)
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed italic">
                                "Promonya memang ada, tapi yang bikin betah adalah kualitas dosennya dan suasana kampusnya yang kondusif. Banyak proyek nyata yang bisa dipraktekin."
                            </p>
                        </div>

                        <div class="mt-6 flex items-center gap-3 pt-4 border-t border-slate-100">
                            <div class="w-10 h-10 rounded-full bg-[#072b5c] text-white font-bold text-xs flex items-center justify-center shrink-0">
                                D
                            </div>
                            <div>
                                <span class="text-xs font-bold text-slate-900 block">Dian P.</span>
                                <span class="text-[11px] text-slate-400 block">S1 Bisnis Digital</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- 8. CTA PENUTUP                                                      -->
        <!-- =================================================================== -->
        <section class="bg-[#0b3d82] text-white py-16 px-4 sm:px-6 relative overflow-hidden">
            <div class="max-w-4xl mx-auto text-center space-y-6">
                <span class="inline-block px-3.5 py-1 rounded-full text-xs font-bold bg-white/10 text-[#f59e0b] border border-white/15">
                    KESEMPATAN TERBATAS
                </span>

                <h2 class="text-2xl sm:text-4xl font-black font-display tracking-tight text-white leading-tight">
                    Jangan Sampai Menyesal — Amankan Kursimu Sebelum Promo Berakhir!
                </h2>

                <p class="text-xs sm:text-sm text-blue-100 max-w-2xl mx-auto leading-relaxed">
                    Dapatkan potongan UKT 60% dan paket bootcamp AI bersertifikat industri di Universitas Catur Insan Cendekia. Amankan kesempatan kuliah di kampus teknologi terbaik Cirebon.
                </p>

                <div class="pt-2 flex flex-wrap items-center justify-center gap-4">
                    <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=halo+kak%2C+saya+ingin+tanya+promo+PMB+UCIC"
                       target="_blank"
                       class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl font-bold text-sm bg-[#10b981] hover:bg-[#059669] text-white shadow-md transition active:scale-95 font-display">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <span>Chat CS Sekarang</span>
                    </a>

                    <a href="{{ route('pmb.register') }}"
                       class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl font-bold text-sm bg-[#f59e0b] hover:bg-[#d97706] text-slate-900 shadow-md transition active:scale-95 font-display">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Daftar Online</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- 9. HUBUNGI KAMI                                                     -->
        <!-- =================================================================== -->
        <section id="kontak" class="py-16 sm:py-20 px-4 sm:px-6 bg-white border-t border-slate-200/80">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-blue-100 text-blue-700 mb-3">
                            PUSAT INFORMASI
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black font-display text-slate-900 leading-tight">
                            Hubungi Kami
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-600 mt-3 leading-relaxed">
                            Sekretariat Penerimaan Mahasiswa Baru (PMB) Universitas Catur Insan Cendekia siap melayani dan mendampingi proses pendaftaran Anda.
                        </p>

                        <div class="mt-8 space-y-4 text-xs sm:text-sm text-slate-700">
                            <!-- Alamat -->
                            <div class="flex items-start gap-3.5">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0 border border-blue-100">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 block">Alamat Kampus:</span>
                                    <span>Jl. Kesambi No.202, Drajat, Kec. Kesambi, Kota Cirebon, Jawa Barat 45133.</span>
                                </div>
                            </div>

                            <!-- Telepon -->
                            <div class="flex items-start gap-3.5">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0 border border-blue-100">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 block">Telepon Kampus:</span>
                                    <a href="tel:0231200418" class="text-blue-600 hover:underline">(0231) 200418</a>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="flex items-start gap-3.5">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0 border border-blue-100">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 block">Email Resmi:</span>
                                    <a href="mailto:info@cic.ac.id" class="text-blue-600 hover:underline">info@cic.ac.id</a>
                                </div>
                            </div>

                            <!-- Website -->
                            <div class="flex items-start gap-3.5">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0 border border-blue-100">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 block">Website PMB:</span>
                                    <span class="text-slate-800">pmb.cic.ac.id</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Card: WhatsApp CS Support -->
                    <div class="p-8 rounded-3xl bg-gradient-to-br from-[#0b3d82] to-[#072b5c] text-white shadow-xl flex flex-col justify-between">
                        <div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/10 text-white mb-4">
                                <svg class="w-3.5 h-3.5 text-[#10b981]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <span>Respon Cepat Panitia PMB</span>
                            </span>
                            <h3 class="text-xl sm:text-2xl font-black font-display">Konsultasi Pendaftaran via WhatsApp</h3>
                            <p class="text-xs sm:text-sm text-blue-100 mt-2 leading-relaxed">
                                Dapatkan panduan lengkap tentang beasiswa, rincian biaya kuliah per semester, simulasi ujian, dan jadwal seleksi langsung dari panitia PMB.
                            </p>
                        </div>

                        <div class="mt-8">
                            <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=halo+kak%2C+saya+ingin+konsultasi+langsung+PMB+UCIC"
                               target="_blank"
                               class="inline-flex items-center justify-center gap-2 w-full py-3.5 px-6 rounded-2xl font-bold bg-[#10b981] hover:bg-[#059669] text-white shadow-lg transition active:scale-95 font-display text-sm">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <span>Hubungi via WhatsApp</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- =================================================================== -->
    <!-- 10. FOOTER RESMI                                                    -->
    <!-- =================================================================== -->
    <footer class="bg-[#0b3d82] text-white pt-14 pb-8 px-4 sm:px-6 border-t border-white/10">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 pb-10 border-b border-white/10">
            <!-- Col 1: Deskripsi Singkat UCIC -->
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo-no-text.png') }}" alt="Logo UCIC" class="h-9 sm:h-10 w-auto object-contain shrink-0">
                    <div>
                        <span class="text-sm font-bold text-white block">Universitas Catur Insan Cendekia</span>
                        <span class="text-[11px] font-bold text-[#facc15] block">#Kampus Teknologi AI</span>
                    </div>
                </div>
                <p class="text-xs text-blue-200 leading-relaxed">
                    Universitas Catur Insan Cendekia (UCIC) adalah institusi pendidikan tinggi terdepan di Cirebon yang mengintegrasikan perkuliahan formal dengan bootcamp AI dan sertifikasi industri.
                </p>
            </div>

            <!-- Col 2: Program Studi -->
            <div>
                <h4 class="text-sm font-bold text-white font-display mb-3">Program Studi</h4>
                <ul class="space-y-1.5 text-xs text-blue-200">
                    <li><a href="#prodi" class="hover:text-white transition">S1 Teknik Informatika</a></li>
                    <li><a href="#prodi" class="hover:text-white transition">S1 Sistem Informasi</a></li>
                    <li><a href="#prodi" class="hover:text-white transition">S1 Desain Komunikasi Visual</a></li>
                    <li><a href="#prodi" class="hover:text-white transition">S1 Bisnis Digital</a></li>
                    <li><a href="#prodi" class="hover:text-white transition">S1 Manajemen & S1 Akuntansi</a></li>
                    <li><a href="#prodi" class="hover:text-white transition">Program Vokasi (D3) & S2</a></li>
                </ul>
            </div>

            <!-- Col 3: Menu PMB -->
            <div>
                <h4 class="text-sm font-bold text-white font-display mb-3">Menu PMB</h4>
                <ul class="space-y-1.5 text-xs text-blue-200">
                    <li><a href="#beranda" class="hover:text-white transition">Beranda</a></li>
                    <li><a href="{{ auth()->check() ? route('pmb.akun.status') : route('pmb.register') }}" class="hover:text-white transition">Biaya Kuliah</a></li>
                    <li><a href="#alur-pmb" class="hover:text-white transition">Alur PMB</a></li>
                    <li><a href="{{ route('pmb.login') }}" class="hover:text-white transition">Login CAMABA</a></li>
                    <li><a href="{{ route('pmb.register') }}" class="hover:text-white transition">Daftar Online</a></li>
                </ul>
            </div>

            <!-- Col 4: Kontak -->
            <div>
                <h4 class="text-sm font-bold text-white font-display mb-3">Kontak</h4>
                <ul class="space-y-2 text-xs text-blue-200">
                    <li>Jl. Kesambi No. 202, Cirebon, Jawa Barat 45133</li>
                    <li>Telp: (0231) 200418</li>
                    <li>Email: info@cic.ac.id</li>
                    <li>WhatsApp: 0812-1670-0519</li>
                </ul>
            </div>
        </div>

        <div class="max-w-7xl mx-auto pt-6 text-center text-xs text-blue-300">
            &copy; {{ date('Y') }} Universitas Catur Insan Cendekia. Hak Cipta Dilindungi.
        </div>
    </footer>

    <!-- Floating WhatsApp Button (Green bottom-right, clean SVG, No Emoji) -->
    <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=halo+kak%2C+saya+ingin+bertanya+berkaitan+dengan+PMB+UCIC&type=phone_number&app_absent=0"
       target="_blank"
       title="Hubungi CS PMB UCIC via WhatsApp"
       class="fixed bottom-6 right-6 w-14 h-14 rounded-full bg-[#10b981] hover:bg-[#059669] text-white flex items-center justify-center shadow-xl shadow-emerald-500/30 transition hover:scale-110 active:scale-95 z-50">
        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
    </a>

</body>
</html>
