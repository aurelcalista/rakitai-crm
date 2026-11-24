<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>PMB UCIC {{ date('Y') }}/{{ date('Y') + 1 }} – Kampus Teknologi AI Cirebon</title>
    <meta name="description" content="Daftar kuliah di Universitas Catur Insan Cendekia (UCIC) Cirebon. Kampus teknologi AI pertama di Cirebon. Beasiswa hingga 60%, bootcamp AI bersertifikat industri.">
    <meta name="keywords" content="PMB UCIC, kuliah Cirebon, kampus AI Cirebon, Universitas Catur Insan Cendekia, beasiswa kuliah">
    <meta name="author" content="UCIC">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-ucic.png') }}">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="PMB UCIC {{ date('Y') }}/{{ date('Y') + 1 }} – Kampus Teknologi AI Cirebon">
    <meta property="og:description" content="Daftar kuliah di UCIC Cirebon. Beasiswa 60%, bootcamp AI bersertifikat, kelas IoT & Robotik terintegrasi.">

    <!-- Google Fonts: Plus Jakarta Sans & Space Grotesk -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --cic-blue-dk: #0D3B6E;
            --cic-blue: #1A5FAB;
            --cic-blue-lt: #3276C3;
            --cic-gold: #F5A623;
            --cic-gold-dk: #D4881B;
            --cic-wa: #25D366;
            --cic-off: #F4F7FC;
            --cic-text: #1A2B45;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--cic-text);
            background: #ffffff;
            overflow-x: hidden;
        }

        .font-mono-num {
            font-family: 'Space Grotesk', 'Plus Jakarta Sans', sans-serif;
        }

        [x-cloak] { display: none !important; }

        /* ── Animations Keyframes ── */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(26px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-26px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(26px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes floatSlow {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50%      { transform: translateY(-10px) rotate(1deg); }
        }
        @keyframes pulseRing {
            0%   { box-shadow: 0 0 0 0 rgba(245, 166, 35, 0.45); }
            70%  { box-shadow: 0 0 0 16px rgba(245, 166, 35, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 166, 35, 0); }
        }
        @keyframes shimmerSweep {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(200%); }
        }

        .animate-fade-up    { animation: fadeInUp 0.65s cubic-bezier(.16, 1, .3, 1) forwards; }
        .animate-fade-left  { animation: fadeInLeft 0.65s cubic-bezier(.16, 1, .3, 1) forwards; }
        .animate-fade-right { animation: fadeInRight 0.65s cubic-bezier(.16, 1, .3, 1) forwards; }
        .animate-float      { animation: floatSlow 4s ease-in-out infinite; }

        .delay-100 { animation-delay: 100ms; }
        .delay-150 { animation-delay: 150ms; }
        .delay-200 { animation-delay: 200ms; }
        .delay-300 { animation-delay: 300ms; }
        .delay-400 { animation-delay: 400ms; }
        .delay-500 { animation-delay: 500ms; }

        /* ── Modern Card Effects ── */
        .card-interactive {
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .card-interactive:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 35px -10px rgba(13, 59, 110, 0.12);
        }

        .glow-gold {
            box-shadow: 0 8px 24px -4px rgba(245, 166, 35, 0.4);
        }
        .glow-wa {
            box-shadow: 0 8px 24px -4px rgba(37, 211, 102, 0.4);
        }

        /* Ambient Hero Backdrop */
        .hero-pattern {
            background-color: #0D3B6E;
            background-image: 
                radial-gradient(circle at 80% 20%, rgba(245, 166, 35, 0.15) 0%, transparent 45%),
                radial-gradient(circle at 10% 85%, rgba(26, 95, 171, 0.4) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(13, 59, 110, 0.9) 0%, #082648 100%);
        }

        .shimmer-badge {
            position: relative;
            overflow: hidden;
        }
        .shimmer-badge::after {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 50%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
            animation: shimmerSweep 3s infinite;
        }

        /* Reading Progress */
        #page-progress {
            position: fixed;
            top: 0; left: 0;
            height: 3px;
            width: 0%;
            background: linear-gradient(90deg, #F5A623, #25D366);
            z-index: 1000;
            transition: width 0.1s linear;
        }
    </style>
</head>
<body class="min-h-full flex flex-col justify-between selection:bg-[#F5A623] selection:text-[#0D3B6E]"
      x-data="{
          scrolled: false,
          days: '00',
          hours: '00',
          minutes: '00',
          seconds: '00',
          initCountdown() {
              const targetDate = new Date('2026-11-30T23:59:59').getTime();
              const update = () => {
                  const now = new Date().getTime();
                  const diff = Math.max(0, targetDate - now);
                  this.days    = String(Math.floor(diff / (1000 * 60 * 60 * 24))).padStart(2, '0');
                  this.hours   = String(Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0');
                  this.minutes = String(Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
                  this.seconds = String(Math.floor((diff % (1000 * 60)) / 1000)).padStart(2, '0');
              };
              update();
              setInterval(update, 1000);
          },
          initScroll() {
              window.addEventListener('scroll', () => {
                  this.scrolled = window.scrollY > 20;
                  const el = document.getElementById('page-progress');
                  if (el) {
                      const total = document.body.scrollHeight - window.innerHeight;
                      el.style.width = (total > 0 ? (window.scrollY / total * 100) : 0) + '%';
                  }
              });
          },
          initObserver() {
              const els = document.querySelectorAll('[data-animate]');
              const obs = new IntersectionObserver((entries) => {
                  entries.forEach(e => {
                      if (e.isIntersecting) {
                          e.target.classList.add(...e.target.dataset.animate.split(' '));
                          obs.unobserve(e.target);
                      }
                  });
              }, { threshold: 0.12 });
              els.forEach(el => {
                  el.style.opacity = '0';
                  obs.observe(el);
              });
          }
      }"
      x-init="initCountdown(); initScroll(); initObserver()">

    <!-- Top Reading Progress Bar -->
    <div id="page-progress"></div>

    <!-- =================================================================== -->
    <!-- 1. HEADER / NAVBAR (Preserving Identitas pmb.cic.ac.id)             -->
    <!-- =================================================================== -->
    <nav class="sticky top-0 z-50 transition-all duration-300 border-b"
         :class="scrolled 
            ? 'bg-[#0D3B6E]/95 backdrop-blur-md border-[#F5A623]/20 shadow-lg shadow-[#0D3B6E]/30 py-3' 
            : 'bg-[#0D3B6E] border-white/10 py-4'">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <!-- Brand Logo & Identity -->
            <a href="#hero" class="flex items-center gap-3.5 group">
                <img src="{{ asset('images/logo-no-text.png') }}" alt="Logo UCIC" 
                     class="h-9 sm:h-10 w-auto object-contain shrink-0 transition-transform duration-300 group-hover:scale-105">
                <div>
                    <span class="text-sm sm:text-base font-extrabold text-white leading-tight block tracking-tight">
                        Universitas Catur Insan Cendekia
                    </span>
                    <span class="text-[11px] font-semibold text-[#F5A623] block tracking-wide">
                        #Kampus Teknologi AI
                    </span>
                </div>
            </a>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2.5 sm:gap-3">
                <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=halo+kak%2C+saya+ingin+bertanya+berkaitan+dengan+PMB+UCIC&type=phone_number&app_absent=0"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold bg-[#25D366] hover:bg-[#1fb855] text-white transition-all duration-200 active:scale-95 glow-wa">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.073-2.127-.47-1.745-.662-2.883-2.42-2.97-2.535-.088-.116-.708-.941-.708-1.794s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.299.144.35.49 1.199.534 1.286.044.087.073.188.014.304-.058.116-.087.188-.173.289l-.26.303c-.087.087-.179.182-.077.357.101.174.45 1.077 1.286 1.821 1.077.957 1.986 1.254 2.269 1.394.283.14.448.117.616-.076.168-.193.722-.84.914-1.129.193-.29.385-.241.644-.145.26.096 1.646.776 1.929.921.283.145.472.217.541.333.069.116.069.667-.075 1.072z"/>
                    </svg>
                    <span>Tanya CS</span>
                </a>

                @auth
                    <a href="{{ route('pmb.akun.dashboard') }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs sm:text-sm font-extrabold bg-[#F5A623] hover:bg-[#D4881B] text-[#0D3B6E] transition-all duration-200 active:scale-95 glow-gold">
                        <span>Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('pmb.login') }}"
                       class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold bg-white/10 hover:bg-white/20 text-white border border-white/20 transition-all duration-200 active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        <span>Login CAMABA</span>
                    </a>
                    <a href="{{ route('pmb.register') }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs sm:text-sm font-extrabold bg-[#F5A623] hover:bg-[#D4881B] text-[#0D3B6E] transition-all duration-200 active:scale-95 glow-gold">
                        <span>Daftar Sekarang</span>
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <main class="flex-grow">
        <!-- =================================================================== -->
        <!-- 2. HERO SECTION & PROMO CARD (100% Identitas pmb.cic.ac.id)         -->
        <!-- =================================================================== -->
        <section id="hero" class="hero-pattern text-white pt-10 pb-20 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
            <!-- Decorative geometric rings matching pmb.cic.ac.id -->
            <div class="absolute -right-24 -bottom-32 w-[600px] h-[600px] rounded-full border border-[#F5A623]/15 pointer-events-none"></div>
            <div class="absolute -left-20 top-20 w-80 h-80 rounded-full border border-white/5 pointer-events-none"></div>

            <div class="max-w-7xl mx-auto relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-center">

                    <!-- Left: Headline, Gelombang, Countdown, CTAs -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- Badge Gelombang (Persis pmb.cic.ac.id) -->
                        <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full text-xs font-bold bg-[#F5A623]/20 border border-[#F5A623]/40 text-[#F5A623] backdrop-blur-sm animate-fade-up">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#F5A623] animate-ping"></span>
                            <span>PMB 2027/2028 – Gelombang Early Bird dan Beasiswa Rektor TERBUKA</span>
                        </div>

                        <!-- Headline (Persis pmb.cic.ac.id) -->
                        <h1 class="text-3xl sm:text-5xl lg:text-[52px] font-black tracking-tight leading-[1.12] animate-fade-up delay-100">
                            Mulai Karir Cerdasmu<br>di Era <span class="text-[#F5A623]">Teknologi AI</span>
                        </h1>

                        <!-- Subheadline (Persis pmb.cic.ac.id) -->
                        <p class="text-sm sm:text-base text-white/80 max-w-xl leading-relaxed animate-fade-up delay-200">
                            Kuliah di UCIC Cirebon – kampus teknologi AI pertama di Cirebon. Dapatkan gelar sarjana sekaligus bootcamp AI bersertifikat industri dalam satu paket terjangkau.
                        </p>

                        <!-- Countdown Box -->
                        <div class="space-y-3 pt-2 animate-fade-up delay-300">
                            <div class="flex items-center gap-2 text-xs font-bold tracking-wider uppercase text-[#F5A623]">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Promo Gelombang Early Bird dan Beasiswa Rektor Berakhir Dalam</span>
                            </div>

                            <div class="flex items-center gap-2.5 sm:gap-3.5">
                                <div class="w-16 sm:w-20 h-16 sm:h-20 rounded-2xl bg-white/10 border border-white/20 backdrop-blur-md flex flex-col items-center justify-center shadow-inner">
                                    <span class="text-2xl sm:text-3xl font-black font-mono-num text-white leading-none" x-text="days">00</span>
                                    <span class="text-[10px] font-bold text-white/60 uppercase mt-1">Hari</span>
                                </div>
                                <span class="text-white/40 font-bold text-xl">:</span>
                                <div class="w-16 sm:w-20 h-16 sm:h-20 rounded-2xl bg-white/10 border border-white/20 backdrop-blur-md flex flex-col items-center justify-center shadow-inner">
                                    <span class="text-2xl sm:text-3xl font-black font-mono-num text-white leading-none" x-text="hours">00</span>
                                    <span class="text-[10px] font-bold text-white/60 uppercase mt-1">Jam</span>
                                </div>
                                <span class="text-white/40 font-bold text-xl">:</span>
                                <div class="w-16 sm:w-20 h-16 sm:h-20 rounded-2xl bg-white/10 border border-white/20 backdrop-blur-md flex flex-col items-center justify-center shadow-inner">
                                    <span class="text-2xl sm:text-3xl font-black font-mono-num text-white leading-none" x-text="minutes">00</span>
                                    <span class="text-[10px] font-bold text-white/60 uppercase mt-1">Menit</span>
                                </div>
                                <span class="text-white/40 font-bold text-xl">:</span>
                                <div class="w-16 sm:w-20 h-16 sm:h-20 rounded-2xl bg-white/10 border border-white/20 backdrop-blur-md flex flex-col items-center justify-center shadow-inner">
                                    <span class="text-2xl sm:text-3xl font-black font-mono-num text-white leading-none" x-text="seconds">00</span>
                                    <span class="text-[10px] font-bold text-white/60 uppercase mt-1">Detik</span>
                                </div>
                            </div>
                        </div>

                        <!-- Hero CTAs -->
                        <div class="flex flex-wrap items-center gap-3.5 pt-4 animate-fade-up delay-400">
                            <a href="{{ route('pmb.register') }}"
                               class="inline-flex items-center gap-2.5 px-6 sm:px-8 py-3.5 sm:py-4 rounded-xl font-extrabold text-sm sm:text-base bg-[#F5A623] hover:bg-[#D4881B] text-[#0D3B6E] glow-gold transition-all duration-200 active:scale-95 hover:-translate-y-0.5">
                                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                    <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zm0 8.55L4.54 9 12 4.95 19.46 9 12 11.55zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/>
                                </svg>
                                <span>Daftar Online Sekarang</span>
                            </a>

                            <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=halo+kak%2C+saya+ingin+bertanya+berkaitan+dengan+biaya+kuliah+di+UCIC&type=phone_number&app_absent=0"
                               target="_blank"
                               class="inline-flex items-center gap-2.5 px-6 sm:px-7 py-3.5 sm:py-4 rounded-xl font-bold text-sm sm:text-base bg-[#25D366] hover:bg-[#1fb855] text-white glow-wa transition-all duration-200 active:scale-95 hover:-translate-y-0.5">
                                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.073-2.127-.47-1.745-.662-2.883-2.42-2.97-2.535-.088-.116-.708-.941-.708-1.794s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.299.144.35.49 1.199.534 1.286.044.087.073.188.014.304-.058.116-.087.188-.173.289l-.26.303c-.087.087-.179.182-.077.357.101.174.45 1.077 1.286 1.821 1.077.957 1.986 1.254 2.269 1.394.283.14.448.117.616-.076.168-.193.722-.84.914-1.129.193-.29.385-.241.644-.145.26.096 1.646.776 1.929.921.283.145.472.217.541.333.069.116.069.667-.075 1.072z"/>
                                </svg>
                                <span>Konsultasi Gratis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Right: Promo Card (Persis pmb.cic.ac.id) -->
                    <div class="lg:col-span-5 relative animate-fade-right delay-200">
                        <div class="relative bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
                            
                            <!-- Promo Badge -->
                            <div class="absolute -top-3.5 left-7 px-3.5 py-1 rounded-full text-[11px] font-black tracking-widest uppercase bg-[#F5A623] text-[#0D3B6E] shadow-md shimmer-badge">
                                PROMO
                            </div>

                            <!-- Discount Headline -->
                            <div class="text-center pt-2 pb-4 border-b border-white/10">
                                <span class="text-7xl sm:text-8xl font-black font-mono-num text-[#F5A623] block leading-none drop-shadow-md">60%</span>
                                <span class="text-lg font-semibold text-white/90 block mt-2">Diskon UKT</span>
                                <span class="text-xs font-bold text-[#F5A623] block mt-0.5">Khusus Pendaftaran Gelombang Early Bird dan Beasiswa Rektor</span>
                            </div>

                            <!-- Perks Checklist (100% Persis pmb.cic.ac.id) -->
                            <ul class="space-y-3 text-xs sm:text-sm text-white/90">
                                <li class="flex items-start gap-2.5">
                                    <span class="text-[#F5A623] font-bold text-base leading-tight">✓</span>
                                    <span>Gelar Sarjana + Skill Premium AI & Robotik</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="text-[#F5A623] font-bold text-base leading-tight">✓</span>
                                    <span>Sertifikat Bootcamp diakui industri</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="text-[#F5A623] font-bold text-base leading-tight">✓</span>
                                    <span>Lab Gaming RAM 32GB, NVIDIA RTX 3060</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="text-[#F5A623] font-bold text-base leading-tight">✓</span>
                                    <span>Kelas AI, IoT & Robotik terintegrasi</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="text-[#F5A623] font-bold text-base leading-tight">✓</span>
                                    <span>Free akses Rakit AI (platform edukasi teknologi)</span>
                                </li>
                            </ul>

                            <!-- Quota Warning -->
                            <div class="py-2.5 px-3 rounded-xl bg-[#F5A623]/15 border border-[#F5A623]/30 text-center text-xs text-[#F5A623] font-bold flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Sisa kuota: <strong>50 mahasiswa</strong> – jangan sampai kehabisan!</span>
                            </div>

                            <!-- Claim Button -->
                            <a href="{{ route('pmb.register') }}"
                               class="w-full inline-flex items-center justify-center gap-2 py-4 px-4 rounded-xl font-black text-sm sm:text-base bg-[#F5A623] hover:bg-[#D4881B] text-[#0D3B6E] glow-gold transition-all duration-200 active:scale-95 hover:-translate-y-0.5">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                    <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <span>Claim Diskon 60% Sekarang</span>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- 3. STATS STRIP (Persis pmb.cic.ac.id)                               -->
        <!-- =================================================================== -->
        <section class="bg-[#0D3B6E] text-white py-9 px-4 sm:px-6 lg:px-8 border-y border-white/10">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-6 text-center">
                    <div class="space-y-1" data-animate="animate-fade-up">
                        <span class="text-3xl sm:text-4xl font-black font-mono-num text-[#F5A623] block">60%</span>
                        <span class="text-xs text-white/70 block">Diskon UKT Gelombang Early Bird & Beasiswa Rektor</span>
                    </div>
                    <div class="space-y-1" data-animate="animate-fade-up delay-100">
                        <span class="text-3xl sm:text-4xl font-black font-mono-num text-[#F5A623] block">9</span>
                        <span class="text-xs text-white/70 block">Program Studi Pilihan</span>
                    </div>
                    <div class="space-y-1" data-animate="animate-fade-up delay-200">
                        <span class="text-3xl sm:text-4xl font-black font-mono-num text-[#F5A623] block">RTX 3060</span>
                        <span class="text-xs text-white/70 block">Lab Gaming Spec</span>
                    </div>
                    <div class="space-y-1" data-animate="animate-fade-up delay-300">
                        <span class="text-3xl sm:text-4xl font-black font-mono-num text-[#F5A623] block">100%</span>
                        <span class="text-xs text-white/70 block">Bootcamp AI Bersertifikat</span>
                    </div>
                    <div class="space-y-1 col-span-2 md:col-span-1" data-animate="animate-fade-up delay-400">
                        <span class="text-3xl sm:text-4xl font-black font-mono-num text-[#F5A623] block">Cirebon</span>
                        <span class="text-xs text-white/70 block">Kampus Teknologi AI #1</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- 4. KENAPA UCIC? (6 KEUNGGULAN / USP PERSIS pmb.cic.ac.id)           -->
        <!-- =================================================================== -->
        <section class="py-20 sm:py-24 px-4 sm:px-6 lg:px-8 bg-[#F4F7FC]">
            <div class="max-w-7xl mx-auto">
                <div class="text-center max-w-2xl mx-auto mb-16" data-animate="animate-fade-up">
                    <span class="inline-block px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-[#1A5FAB]/10 text-[#1A5FAB] mb-3">
                        Kenapa UCIC?
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-[#1A2B45] leading-tight">
                        Kuliah atau Bootcamp?<br>di UCIC kamu <span class="text-[#1A5FAB]">Dapat Keduanya!</span>
                    </h2>
                    <p class="text-sm text-slate-500 mt-3 leading-relaxed">
                        Satu-satunya kampus di Cirebon yang mengintegrasikan pendidikan formal dengan program bootcamp teknologi AI langsung dari industri.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-7">
                    <!-- 1. Kurikulum AI -->
                    <div class="group relative bg-white p-7 sm:p-8 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-xl hover:shadow-[#1A5FAB]/10 hover:-translate-y-2 transition-all duration-300 flex flex-col justify-between overflow-hidden" data-animate="animate-fade-up">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#1A5FAB] to-[#F5A623] transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                        <div>
                            <div class="w-[54px] h-[54px] min-w-[54px] min-h-[54px] max-w-[54px] max-h-[54px] rounded-2xl bg-gradient-to-br from-[#1A5FAB] to-[#3276C3] text-white flex items-center justify-center text-2xl shadow-md shadow-[#1A5FAB]/20 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-[#1A5FAB]/30 group-hover:rotate-3 transition-all duration-300 shrink-0 mb-5">
                                🤖
                            </div>
                            <h3 class="text-base sm:text-lg font-bold text-[#1A2B45] group-hover:text-[#1A5FAB] transition-colors leading-snug mb-2">
                                Kurikulum AI & Teknologi Industri
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                                Belajar AI, IoT, Robotik, dan Pemrograman yang terintegrasi langsung dengan kebutuhan dunia kerja masa kini.
                            </p>
                        </div>
                        <div class="mt-6 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-[#1A5FAB]">
                            <span class="group-hover:text-[#0D3B6E] transition-colors">Program Terintegrasi</span>
                            <svg class="w-4 h-4 transform group-hover:translate-x-1.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>

                    <!-- 2. Lab Gaming -->
                    <div class="group relative bg-white p-7 sm:p-8 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-xl hover:shadow-[#1A5FAB]/10 hover:-translate-y-2 transition-all duration-300 flex flex-col justify-between overflow-hidden" data-animate="animate-fade-up delay-100">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#1A5FAB] to-[#F5A623] transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                        <div>
                            <div class="w-[54px] h-[54px] min-w-[54px] min-h-[54px] max-w-[54px] max-h-[54px] rounded-2xl bg-gradient-to-br from-[#1A5FAB] to-[#3276C3] text-white flex items-center justify-center text-2xl shadow-md shadow-[#1A5FAB]/20 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-[#1A5FAB]/30 group-hover:rotate-3 transition-all duration-300 shrink-0 mb-5">
                                🎮
                            </div>
                            <h3 class="text-base sm:text-lg font-bold text-[#1A2B45] group-hover:text-[#1A5FAB] transition-colors leading-snug mb-2">
                                Lab Gaming Kelas Dewa
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                                Lab komputer dengan spesifikasi gaming: RAM 32GB, AMD Ryzen 7, NVIDIA RTX 3060 untuk pengalaman belajar maksimal.
                            </p>
                        </div>
                        <div class="mt-6 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-[#1A5FAB]">
                            <span class="group-hover:text-[#0D3B6E] transition-colors">Performa Maksimal</span>
                            <svg class="w-4 h-4 transform group-hover:translate-x-1.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>

                    <!-- 3. Bootcamp AI -->
                    <div class="group relative bg-white p-7 sm:p-8 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-xl hover:shadow-[#1A5FAB]/10 hover:-translate-y-2 transition-all duration-300 flex flex-col justify-between overflow-hidden" data-animate="animate-fade-up delay-200">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#1A5FAB] to-[#F5A623] transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                        <div>
                            <div class="w-[54px] h-[54px] min-w-[54px] min-h-[54px] max-w-[54px] max-h-[54px] rounded-2xl bg-gradient-to-br from-[#1A5FAB] to-[#3276C3] text-white flex items-center justify-center text-2xl shadow-md shadow-[#1A5FAB]/20 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-[#1A5FAB]/30 group-hover:rotate-3 transition-all duration-300 shrink-0 mb-5">
                                🏆
                            </div>
                            <h3 class="text-base sm:text-lg font-bold text-[#1A2B45] group-hover:text-[#1A5FAB] transition-colors leading-snug mb-2">
                                Bootcamp AI Bersertifikat
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                                Seluruh mahasiswa mendapatkan bootcamp langsung dengan materi dan pengajar dari industri rekanan berpengalaman.
                            </p>
                        </div>
                        <div class="mt-6 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-[#1A5FAB]">
                            <span class="group-hover:text-[#0D3B6E] transition-colors">Portofolio Kerja</span>
                            <svg class="w-4 h-4 transform group-hover:translate-x-1.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>

                    <!-- 4. Kolaborasi Internasional -->
                    <div class="group relative bg-white p-7 sm:p-8 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-xl hover:shadow-[#1A5FAB]/10 hover:-translate-y-2 transition-all duration-300 flex flex-col justify-between overflow-hidden" data-animate="animate-fade-up delay-300">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#1A5FAB] to-[#F5A623] transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                        <div>
                            <div class="w-[54px] h-[54px] min-w-[54px] min-h-[54px] max-w-[54px] max-h-[54px] rounded-2xl bg-gradient-to-br from-[#1A5FAB] to-[#3276C3] text-white flex items-center justify-center text-2xl shadow-md shadow-[#1A5FAB]/20 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-[#1A5FAB]/30 group-hover:rotate-3 transition-all duration-300 shrink-0 mb-5">
                                🌐
                            </div>
                            <h3 class="text-base sm:text-lg font-bold text-[#1A2B45] group-hover:text-[#1A5FAB] transition-colors leading-snug mb-2">
                                Kolaborasi Internasional
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                                Seminar dan workshop internasional bersama universitas luar negeri untuk memperluas wawasan global kamu.
                            </p>
                        </div>
                        <div class="mt-6 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-[#1A5FAB]">
                            <span class="group-hover:text-[#0D3B6E] transition-colors">Wawasan Global</span>
                            <svg class="w-4 h-4 transform group-hover:translate-x-1.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>

                    <!-- 5. Fasilitas Kelas Dunia -->
                    <div class="group relative bg-white p-7 sm:p-8 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-xl hover:shadow-[#1A5FAB]/10 hover:-translate-y-2 transition-all duration-300 flex flex-col justify-between overflow-hidden" data-animate="animate-fade-up delay-400">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#1A5FAB] to-[#F5A623] transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                        <div>
                            <div class="w-[54px] h-[54px] min-w-[54px] min-h-[54px] max-w-[54px] max-h-[54px] rounded-2xl bg-gradient-to-br from-[#1A5FAB] to-[#3276C3] text-white flex items-center justify-center text-2xl shadow-md shadow-[#1A5FAB]/20 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-[#1A5FAB]/30 group-hover:rotate-3 transition-all duration-300 shrink-0 mb-5">
                                📺
                            </div>
                            <h3 class="text-base sm:text-lg font-bold text-[#1A2B45] group-hover:text-[#1A5FAB] transition-colors leading-snug mb-2">
                                Fasilitas Kelas Dunia
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                                Ruang kelas dilengkapi Smart TV, AC, dan fasilitas berkelas untuk mendukung proses belajar yang nyaman dan modern.
                            </p>
                        </div>
                        <div class="mt-6 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-[#1A5FAB]">
                            <span class="group-hover:text-[#0D3B6E] transition-colors">Kenyamanan Belajar</span>
                            <svg class="w-4 h-4 transform group-hover:translate-x-1.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>

                    <!-- 6. OCW & Mini Class Gratis -->
                    <div class="group relative bg-white p-7 sm:p-8 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-xl hover:shadow-[#1A5FAB]/10 hover:-translate-y-2 transition-all duration-300 flex flex-col justify-between overflow-hidden" data-animate="animate-fade-up delay-500">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#1A5FAB] to-[#F5A623] transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                        <div>
                            <div class="w-[54px] h-[54px] min-w-[54px] min-h-[54px] max-w-[54px] max-h-[54px] rounded-2xl bg-gradient-to-br from-[#1A5FAB] to-[#3276C3] text-white flex items-center justify-center text-2xl shadow-md shadow-[#1A5FAB]/20 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-[#1A5FAB]/30 group-hover:rotate-3 transition-all duration-300 shrink-0 mb-5">
                                🎓
                            </div>
                            <h3 class="text-base sm:text-lg font-bold text-[#1A2B45] group-hover:text-[#1A5FAB] transition-colors leading-snug mb-2">
                                OCW & Mini Class Gratis
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                                Akses puluhan kelas online gratis dari dosen UCIC melalui Open CourseWare. Belajar kapan saja dan di mana saja.
                            </p>
                        </div>
                        <div class="mt-6 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-[#1A5FAB]">
                            <span class="group-hover:text-[#0D3B6E] transition-colors">Akses Terbuka</span>
                            <svg class="w-4 h-4 transform group-hover:translate-x-1.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- 5. PROGRAM STUDI (100% PERSIS pmb.cic.ac.id)                       -->
        <!-- =================================================================== -->
        <section id="prodi" class="py-20 sm:py-24 px-4 sm:px-6 lg:px-8 bg-white border-t border-slate-200/70">
            <div class="max-w-7xl mx-auto">
                <div class="text-center max-w-3xl mx-auto mb-16" data-animate="animate-fade-up">
                    <span class="inline-block px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-[#1A5FAB]/10 text-[#1A5FAB] mb-3">
                        Program Studi
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-[#1A2B45] leading-tight">
                        Pilih Jurusan Favoritmu,<br><span class="text-[#1A5FAB]">Masa Depanmu Dimulai Di Sini!</span>
                    </h2>
                    <p class="text-sm text-slate-500 mt-2">
                        9 program studi yang siap mencetak generasi profesional dan inovator masa depan.
                    </p>
                </div>

                @php
                    $prodiItems = [
                        [
                            'badge' => 'S1',
                            'name'  => 'Teknik Informatika',
                            'desc'  => 'Menjadi ahli teknologi informasi dan pengembangan software siap kerja di era digital.'
                        ],
                        [
                            'badge' => 'S1',
                            'name'  => 'Sistem Informasi',
                            'desc'  => 'Fokus pada perancangan dan pengelolaan sistem informasi modern untuk industri.'
                        ],
                        [
                            'badge' => 'S1',
                            'name'  => 'Desain Komunikasi Visual',
                            'desc'  => 'Mengembangkan talenta seni dan desain kreatif untuk industri media dan hiburan.'
                        ],
                        [
                            'badge' => 'S1',
                            'name'  => 'Akuntansi',
                            'desc'  => 'Mencetak akuntan profesional dengan kemampuan analisis keuangan yang unggul.'
                        ],
                        [
                            'badge' => 'S1',
                            'name'  => 'Manajemen',
                            'desc'  => 'Mempersiapkan pemimpin bisnis masa depan dengan kurikulum manajemen komprehensif.'
                        ],
                        [
                            'badge' => 'S2',
                            'name'  => 'Manajemen',
                            'desc'  => 'Mempersiapkan pemimpin bisnis masa depan dengan kurikulum manajemen komprehensif.'
                        ],
                        [
                            'badge' => 'S1',
                            'name'  => 'Bisnis Digital',
                            'desc'  => 'Mempersiapkan entrepreneur digital dan ahli strategi bisnis di era teknologi.'
                        ],
                        [
                            'badge' => 'D3',
                            'name'  => 'Manajemen Bisnis',
                            'desc'  => 'Tenaga ahli madya bidang manajemen dan operasional bisnis yang profesional.'
                        ],
                        [
                            'badge' => 'D3',
                            'name'  => 'Manajemen Informatika',
                            'desc'  => 'Tenaga ahli madya bidang manajemen untuk proyek di bidang informatika.'
                        ],
                        [
                            'badge' => 'S1',
                            'name'  => 'Pendidikan Kepelatihan Olahraga',
                            'desc'  => 'Mencetak pelatih olahraga profesional dan pendidik keolahragaan bersertifikasi.'
                        ],
                        [
                            'badge' => 'S1',
                            'name'  => 'Pendidikan Matematika',
                            'desc'  => 'Mencetak tenaga ahli dalam bidang Matematika.'
                        ],
                    ];
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach($prodiItems as $p)
                    <div class="card-interactive bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between group" data-animate="animate-fade-up">
                        <div>
                            <div class="inline-block px-2.5 py-0.5 rounded-md text-xs font-bold mb-3 font-mono-num
                                {{ $p['badge'] === 'S2' ? 'bg-purple-100 text-purple-700' : ($p['badge'] === 'D3' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-[#1A5FAB]') }}">
                                {{ $p['badge'] }}
                            </div>
                            <h3 class="text-base font-extrabold text-[#1A2B45] group-hover:text-[#1A5FAB] transition-colors leading-snug">{{ $p['name'] }}</h3>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">{{ $p['desc'] }}</p>
                        </div>
                        <div class="mt-5 pt-3 border-t border-slate-100">
                            <a href="{{ auth()->check() ? route('pmb.akun.status') : route('pmb.register') }}"
                               class="inline-flex items-center gap-1.5 text-xs font-bold text-[#1A5FAB] hover:text-[#0D3B6E] transition-all group-hover:translate-x-1">
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
        <!-- 6. ALUR PMB (100% PERSIS pmb.cic.ac.id)                             -->
        <!-- =================================================================== -->
        <section id="alur" class="bg-[#0D3B6E] text-white py-20 sm:py-24 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
            <div class="max-w-7xl mx-auto">
                <div class="max-w-2xl mb-16" data-animate="animate-fade-left">
                    <span class="inline-block px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-[#F5A623]/20 text-[#F5A623] mb-3">
                        Alur PMB
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black leading-tight">
                        Cara Daftar yang<br><span class="text-[#F5A623]">Mudah & Cepat</span>
                    </h2>
                    <p class="text-sm text-white/70 mt-2">
                        Proses pendaftaran 100% online, selesai dalam hitungan menit.
                    </p>
                </div>

                @php
                    $steps = [
                        ['num' => '1', 'title' => 'Sosialisasi',    'desc' => 'Kenali UCIC dan program unggulan'],
                        ['num' => '2', 'title' => 'Daftar Online',  'desc' => 'Isi formulir di pmb.cic.ac.id'],
                        ['num' => '3', 'title' => 'Simulasi Ujian', 'desc' => 'Ikuti ujian TPA online'],
                        ['num' => '4', 'title' => 'Lihat Hasil',    'desc' => 'Cek pengumuman kelulusan'],
                        ['num' => '5', 'title' => 'Pembayaran',     'desc' => 'Bayar dengan diskon 60%'],
                        ['num' => '6', 'title' => 'Upload Berkas',  'desc' => 'Lengkapi dokumen persyaratan'],
                    ];
                @endphp

                <div class="grid grid-cols-2 md:grid-cols-6 gap-6 text-center">
                    @foreach($steps as $s)
                    <div class="flex flex-col items-center group" data-animate="animate-fade-up">
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-[#F5A623] to-[#D4881B] text-[#0D3B6E] font-black text-xl font-mono-num flex items-center justify-center mb-4 shadow-lg group-hover:scale-110 transition-transform">
                            {{ $s['num'] }}
                        </div>
                        <h4 class="text-sm font-bold text-white mb-1">{{ $s['title'] }}</h4>
                        <p class="text-xs text-white/60 leading-snug">{{ $s['desc'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- 7. TESTIMONI (100% PERSIS pmb.cic.ac.id)                           -->
        <!-- =================================================================== -->
        <section class="py-20 sm:py-24 px-4 sm:px-6 lg:px-8 bg-[#F4F7FC]">
            <div class="max-w-7xl mx-auto">
                <div class="text-center max-w-2xl mx-auto mb-16" data-animate="animate-fade-up">
                    <span class="inline-block px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-[#1A5FAB]/10 text-[#1A5FAB] mb-3">
                        Testimoni
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-[#1A2B45] leading-tight">
                        Apa Kata Mahasiswa <span class="text-[#1A5FAB]">UCIC?</span>
                    </h2>
                    <p class="text-sm text-slate-500 mt-2">
                        Cerita nyata dari mahasiswa yang sudah membuktikan kualitas UCIC.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- 1 -->
                    <div class="card-interactive bg-white p-7 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between" data-animate="animate-fade-up">
                        <div>
                            <div class="text-[#F5A623] text-base mb-3 font-bold tracking-widest">★★★★★</div>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed italic">
                                "Daftar UCIC adalah keputusan terbaik! Dapat bootcamp AI langsung dari praktisi industri, lab-nya juga keren banget. Langsung bisa praktek dari hari pertama kuliah."
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center gap-3">
                            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-[#1A5FAB] to-[#3276C3] text-white font-black text-sm flex items-center justify-center shrink-0">
                                R
                            </div>
                            <div>
                                <span class="text-sm font-extrabold text-[#1A2B45] block">Rizky A.</span>
                                <span class="text-xs text-slate-400 block">S1 Teknik Informatika</span>
                            </div>
                        </div>
                    </div>

                    <!-- 2 -->
                    <div class="card-interactive bg-white p-7 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between" data-animate="animate-fade-up delay-150">
                        <div>
                            <div class="text-[#F5A623] text-base mb-3 font-bold tracking-widest">★★★★★</div>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed italic">
                                "Kuliah sambil dapat sertifikat bootcamp AI – mana ada kampus lain yang bisa begini? Plus fasilitas lab gamenya keren abis. Worth it banget!"
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center gap-3">
                            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-[#D4881B] to-[#F5A623] text-white font-black text-sm flex items-center justify-center shrink-0">
                                S
                            </div>
                            <div>
                                <span class="text-sm font-extrabold text-[#1A2B45] block">Sari M.</span>
                                <span class="text-xs text-slate-400 block">S1 Sistem Informasi</span>
                            </div>
                        </div>
                    </div>

                    <!-- 3 -->
                    <div class="card-interactive bg-white p-7 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between" data-animate="animate-fade-up delay-300">
                        <div>
                            <div class="text-[#F5A623] text-base mb-3 font-bold tracking-widest">★★★★★</div>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed italic">
                                "Promonya memang ada, tapi yang bikin betah adalah kualitas dosennya dan suasana kampusnya yang kondusif. Banyak proyek nyata yang bisa dipraktekin."
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center gap-3">
                            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-[#0D3B6E] to-[#1A5FAB] text-white font-black text-sm flex items-center justify-center shrink-0">
                                D
                            </div>
                            <div>
                                <span class="text-sm font-extrabold text-[#1A2B45] block">Dian P.</span>
                                <span class="text-xs text-slate-400 block">S1 Bisnis Digital</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- 8. CTA BANNER (100% PERSIS pmb.cic.ac.id)                           -->
        <!-- =================================================================== -->
        <section class="bg-gradient-to-br from-[#1A5FAB] to-[#0D3B6E] text-white py-20 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
            <div class="absolute -right-20 -top-28 w-[500px] h-[500px] rounded-full border border-[#F5A623]/15 pointer-events-none"></div>

            <div class="max-w-4xl mx-auto text-center space-y-6 relative z-10" data-animate="animate-fade-up">
                <span class="inline-block px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-[#F5A623]/20 text-[#F5A623]">
                    Jangan Sampai Menyesal
                </span>

                <h2 class="text-3xl sm:text-5xl font-black tracking-tight leading-tight">
                    Amankan Kursimu Sebelum<br>Promo <span class="text-[#F5A623]">Berakhir!</span>
                </h2>

                <p class="text-sm sm:text-base text-white/80 max-w-2xl mx-auto leading-relaxed">
                    Hanya 50 kursi tersisa dengan diskon UKT 60%. Ratusan pendaftar sudah bergabung – giliran kamu sekarang. Konsultasi gratis, tidak ada biaya daftar.
                </p>

                <div class="pt-4 flex flex-wrap items-center justify-center gap-4">
                    <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=halo+kak%2C+saya+ingin+bertanya+berkaitan+dengan+biaya+kuliah+di+UCIC&type=phone_number&app_absent=0"
                       target="_blank"
                       class="inline-flex items-center gap-2.5 px-8 py-4 rounded-xl font-extrabold text-sm sm:text-base bg-[#25D366] hover:bg-[#1fb855] text-white glow-wa transition-all duration-200 active:scale-95 hover:-translate-y-0.5">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.073-2.127-.47-1.745-.662-2.883-2.42-2.97-2.535-.088-.116-.708-.941-.708-1.794s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.299.144.35.49 1.199.534 1.286.044.087.073.188.014.304-.058.116-.087.188-.173.289l-.26.303c-.087.087-.179.182-.077.357.101.174.45 1.077 1.286 1.821 1.077.957 1.986 1.254 2.269 1.394.283.14.448.117.616-.076.168-.193.722-.84.914-1.129.193-.29.385-.241.644-.145.26.096 1.646.776 1.929.921.283.145.472.217.541.333.069.116.069.667-.075 1.072z"/>
                        </svg>
                        <span>Chat CS Sekarang – GRATIS</span>
                    </a>

                    <a href="{{ route('pmb.register') }}"
                       class="inline-flex items-center gap-2.5 px-8 py-4 rounded-xl font-bold text-sm sm:text-base border-2 border-white/50 hover:border-[#F5A623] hover:bg-[#F5A623]/10 text-white transition-all duration-200 active:scale-95">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                            <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zm0 8.55L4.54 9 12 4.95 19.46 9 12 11.55zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/>
                        </svg>
                        <span>Daftar Online</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- 9. CONTACT / MASIH ADA PERTANYAAN (100% PERSIS pmb.cic.ac.id)       -->
        <!-- =================================================================== -->
        <section id="kontak" class="py-20 sm:py-24 px-4 sm:px-6 lg:px-8 bg-white border-t border-slate-200/70">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    
                    <!-- Left: Contact info -->
                    <div data-animate="animate-fade-left">
                        <span class="inline-block px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-[#1A5FAB]/10 text-[#1A5FAB] mb-3">
                            Hubungi Kami
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black text-[#1A2B45] leading-tight">
                            Masih Ada <span class="text-[#1A5FAB]">Pertanyaan?</span>
                        </h2>
                        <p class="text-sm text-slate-500 mt-3 mb-8 leading-relaxed">
                            Tim CS kami siap membantu kamu 24 jam sehari. Konsultasi gratis, tanpa tekanan!
                        </p>

                        <div class="space-y-5 text-sm">
                            <div class="flex items-start gap-4">
                                <div class="w-11 h-11 rounded-xl bg-[#1A5FAB]/10 text-[#1A5FAB] flex items-center justify-center shrink-0 text-xl">
                                    📍
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-400 block">Alamat Kampus</span>
                                    <strong class="text-[#1A2B45] text-sm block leading-snug">Jl. Kesambi No.202, Drajat, Kec. Kesambi, Kota Cirebon, Jawa Barat 45133</strong>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-11 h-11 rounded-xl bg-[#1A5FAB]/10 text-[#1A5FAB] flex items-center justify-center shrink-0 text-xl">
                                    📞
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-400 block">Telepon</span>
                                    <a href="tel:0231200418" class="text-[#1A2B45] font-extrabold hover:text-[#1A5FAB]">(0231) 200418</a>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-11 h-11 rounded-xl bg-[#1A5FAB]/10 text-[#1A5FAB] flex items-center justify-center shrink-0 text-xl">
                                    ✉️
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-400 block">Email</span>
                                    <a href="mailto:info@cic.ac.id" class="text-[#1A2B45] font-extrabold hover:text-[#1A5FAB]">info@cic.ac.id</a>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-11 h-11 rounded-xl bg-[#1A5FAB]/10 text-[#1A5FAB] flex items-center justify-center shrink-0 text-xl">
                                    🌐
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-400 block">Website PMB</span>
                                    <a href="https://pmb.cic.ac.id" target="_blank" class="text-[#1A5FAB] font-extrabold hover:underline">pmb.cic.ac.id</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: WhatsApp Green Card -->
                    <div class="card-interactive bg-gradient-to-br from-[#25D366] to-[#1DA851] text-white p-8 sm:p-10 rounded-3xl shadow-xl text-center space-y-4" data-animate="animate-fade-right">
                        <div class="text-6xl animate-bounce">💬</div>
                        <h3 class="text-2xl font-black leading-tight">Chat Langsung dengan CS</h3>
                        <p class="text-sm text-white/90 leading-relaxed max-w-md mx-auto">
                            Tanya apapun tentang pendaftaran, biaya kuliah, program studi, beasiswa, dan fasilitas kampus. Respon cepat!
                        </p>
                        <div class="pt-4">
                            <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=halo+kak%2C+saya+ingin+bertanya+berkaitan+dengan+PMB+UCIC+2025&type=phone_number&app_absent=0"
                               target="_blank"
                               class="inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-xl font-black text-sm sm:text-base bg-white text-[#1DA851] hover:bg-slate-100 shadow-lg transition-transform active:scale-95 hover:scale-105">
                                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.073-2.127-.47-1.745-.662-2.883-2.42-2.97-2.535-.088-.116-.708-.941-.708-1.794s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.299.144.35.49 1.199.534 1.286.044.087.073.188.014.304-.058.116-.087.188-.173.289l-.26.303c-.087.087-.179.182-.077.357.101.174.45 1.077 1.286 1.821 1.077.957 1.986 1.254 2.269 1.394.283.14.448.117.616-.076.168-.193.722-.84.914-1.129.193-.29.385-.241.644-.145.26.096 1.646.776 1.929.921.283.145.472.217.541.333.069.116.069.667-.075 1.072z"/>
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
    <!-- 10. FOOTER (100% PERSIS pmb.cic.ac.id)                              -->
    <!-- =================================================================== -->
    <footer class="bg-[#0D3B6E] text-white/70 pt-16 pb-8 px-4 sm:px-6 lg:px-8 border-t border-white/10 text-xs sm:text-sm">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 pb-12 border-b border-white/10">
                <!-- Col 1: Identity & Desc -->
                <div class="space-y-4">
                    <a href="#hero" class="flex items-center gap-3">
                        <img src="{{ asset('images/logo-no-text.png') }}" alt="Logo UCIC" class="h-9 w-auto object-contain">
                        <div>
                            <span class="text-sm font-extrabold text-white block">Universitas Catur Insan Cendekia</span>
                            <span class="text-[11px] font-semibold text-[#F5A623] block">#Kampus Teknologi AI</span>
                        </div>
                    </a>
                    <p class="text-xs text-white/60 leading-relaxed max-w-xs">
                        Kampus teknologi AI dan kreatif yang progresif di Cirebon. Kuliah + Bootcamp dalam satu paket terjangkau.
                    </p>
                </div>

                <!-- Col 2: Program Studi -->
                <div>
                    <h4 class="text-sm font-extrabold text-white mb-4">Program Studi</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="#prodi" class="hover:text-[#F5A623] transition-colors">S1 Teknik Informatika</a></li>
                        <li><a href="#prodi" class="hover:text-[#F5A623] transition-colors">S1 Sistem Informasi</a></li>
                        <li><a href="#prodi" class="hover:text-[#F5A623] transition-colors">S1 Desain Komunikasi Visual</a></li>
                        <li><a href="#prodi" class="hover:text-[#F5A623] transition-colors">S1 Bisnis Digital</a></li>
                        <li><a href="#prodi" class="hover:text-[#F5A623] transition-colors">S1 Akuntansi</a></li>
                        <li><a href="#prodi" class="hover:text-[#F5A623] transition-colors">Lihat Semua Prodi</a></li>
                    </ul>
                </div>

                <!-- Col 3: PMB -->
                <div>
                    <h4 class="text-sm font-extrabold text-white mb-4">PMB</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ route('pmb.register') }}" class="hover:text-[#F5A623] transition-colors">Daftar Online</a></li>
                        <li><a href="{{ auth()->check() ? route('pmb.akun.status') : route('pmb.register') }}" class="hover:text-[#F5A623] transition-colors">Info Biaya & Beasiswa</a></li>
                        <li><a href="#prodi" class="hover:text-[#F5A623] transition-colors">Katalog Prodi</a></li>
                        <li><a href="https://miniclass.cic.ac.id" target="_blank" class="hover:text-[#F5A623] transition-colors">Mini Class AI</a></li>
                        <li><a href="#alur" class="hover:text-[#F5A623] transition-colors">Alur Pendaftaran</a></li>
                    </ul>
                </div>

                <!-- Col 4: Kontak -->
                <div>
                    <h4 class="text-sm font-extrabold text-white mb-4">Kontak</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="tel:0231200418" class="hover:text-[#F5A623] transition-colors">(0231) 200418</a></li>
                        <li><a href="mailto:info@cic.ac.id" class="hover:text-[#F5A623] transition-colors">info@cic.ac.id</a></li>
                        <li><a href="https://api.whatsapp.com/send/?phone=6281216700519" target="_blank" class="hover:text-[#F5A623] transition-colors">WhatsApp CS</a></li>
                        <li><a href="https://pmb.cic.ac.id" target="_blank" class="hover:text-[#F5A623] transition-colors">pmb.cic.ac.id</a></li>
                    </ul>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-white/50">
                <span>© {{ date('Y') }} Universitas Catur Insan Cendekia (UCIC). All rights reserved.</span>
                <span>Jl. Kesambi No.202, Cirebon, Jawa Barat 45133</span>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp Widget -->
    <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=halo+kak%2C+saya+ingin+bertanya+berkaitan+dengan+PMB+UCIC+2025&type=phone_number&app_absent=0"
       target="_blank"
       title="Chat WhatsApp CS UCIC"
       class="fixed bottom-6 right-6 w-14 h-14 rounded-full bg-[#25D366] hover:bg-[#1fb855] text-white flex items-center justify-center glow-wa transition-all duration-300 hover:scale-110 active:scale-95 z-50">
        <svg class="w-7 h-7 fill-current" viewBox="0 0 24 24">
            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.073-2.127-.47-1.745-.662-2.883-2.42-2.97-2.535-.088-.116-.708-.941-.708-1.794s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.299.144.35.49 1.199.534 1.286.044.087.073.188.014.304-.058.116-.087.188-.173.289l-.26.303c-.087.087-.179.182-.077.357.101.174.45 1.077 1.286 1.821 1.077.957 1.986 1.254 2.269 1.394.283.14.448.117.616-.076.168-.193.722-.84.914-1.129.193-.29.385-.241.644-.145.26.096 1.646.776 1.929.921.283.145.472.217.541.333.069.116.069.667-.075 1.072z"/>
        </svg>
    </a>

</body>
</html>
