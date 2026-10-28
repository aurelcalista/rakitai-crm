<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Penerimaan Mahasiswa Baru (PMB) - Universitas Catur Insan Cendekia (UCIC)</title>
    <meta name="description" content="Pendaftaran Online Penerimaan Mahasiswa Baru Universitas Catur Insan Cendekia (UCIC) Cirebon. Kampus Teknologi AI, Bisnis Digital, dan Desain Komunikasi Visual.">
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
<body class="min-h-full bg-slate-50 text-slate-800 antialiased selection:bg-blue-600 selection:text-white flex flex-col justify-between"
      x-data="{ mobileMenuOpen: false }">

    <!-- Navbar -->
    <header class="w-full bg-white/90 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-18 flex items-center justify-between">
            <!-- Brand -->
            <a href="{{ route('pmb.portal') }}" class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-white border border-slate-200/90 flex items-center justify-center p-2 shadow-2xs">
                    <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" class="w-full h-full object-contain">
                </div>
                <div>
                    <span class="text-base font-extrabold text-slate-900 leading-tight font-display block">Universitas CIC</span>
                    <span class="text-xs font-semibold text-blue-600 block">#KampusTeknologiAI & Bisnis</span>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-600">
                <a href="#prodi" class="hover:text-blue-600 transition">Program Studi</a>
                <a href="#alur" class="hover:text-blue-600 transition">Alur Pendaftaran</a>
                <a href="#kontak" class="hover:text-blue-600 transition">Bantuan & Kontak</a>
            </nav>

            <!-- Actions CTA -->
            <div class="hidden sm:flex items-center gap-3">
                @auth
                    @if(strtolower(auth()->user()->role) === 'camaba')
                        <a href="{{ route('pmb.akun.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>Dashboard Akun</span>
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white shadow-xs transition">
                            <span>CRM Dashboard</span>
                        </a>
                    @endif
                @else
                    <a href="{{ route('pmb.login') }}" class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-blue-700 hover:bg-slate-100 transition">
                        Masuk
                    </a>
                    <a href="{{ route('pmb.register') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-xs shadow-blue-500/20 transition">
                        Daftar Akun Baru
                    </a>
                @endauth
            </div>

            <!-- Mobile Menu Toggle -->
            <button type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="md:hidden p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100">
                <svg x-show="!mobileMenuOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="mobileMenuOpen" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div x-show="mobileMenuOpen" x-cloak class="md:hidden border-t border-slate-200 bg-white px-4 py-3 space-y-2">
            <a href="#prodi" @click="mobileMenuOpen = false" class="block py-2 text-sm font-semibold text-slate-700">Program Studi</a>
            <a href="#alur" @click="mobileMenuOpen = false" class="block py-2 text-sm font-semibold text-slate-700">Alur Pendaftaran</a>
            <a href="#kontak" @click="mobileMenuOpen = false" class="block py-2 text-sm font-semibold text-slate-700">Bantuan & Kontak</a>
            <div class="pt-3 border-t border-slate-100 flex gap-2">
                @auth
                    @if(strtolower(auth()->user()->role) === 'camaba')
                        <a href="{{ route('pmb.akun.dashboard') }}" class="w-full text-center py-2.5 rounded-xl text-xs font-bold bg-blue-600 text-white">Dashboard Akun</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="w-full text-center py-2.5 rounded-xl text-xs font-bold bg-slate-900 text-white">CRM Dashboard</a>
                    @endif
                @else
                    <a href="{{ route('pmb.login') }}" class="w-1/2 text-center py-2.5 rounded-xl text-xs font-bold border border-slate-300 text-slate-700">Masuk</a>
                    <a href="{{ route('pmb.register') }}" class="w-1/2 text-center py-2.5 rounded-xl text-xs font-bold bg-blue-600 text-white">Daftar</a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative pt-12 pb-20 overflow-hidden bg-gradient-to-b from-blue-50/60 via-white to-slate-50">
        <!-- Ambient shapes -->
        <div class="absolute -top-24 right-0 w-96 h-96 bg-blue-200/40 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/2 left-0 w-80 h-80 bg-indigo-200/30 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 relative z-10">
            <div class="text-center max-w-3xl mx-auto">
                <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-extrabold uppercase tracking-wider bg-blue-100/80 text-blue-800 border border-blue-200 mb-6 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    PMB UCIC TA {{ date('Y') }}/{{ date('Y') + 1 }} Resmi Dibuka
                </span>

                <h1 class="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight leading-tight font-display">
                    Wujudkan Masa Depan Cemerlang di <span class="text-blue-600">Kampus Teknologi AI</span>
                </h1>

                <p class="mt-4 text-base sm:text-lg text-slate-600 leading-relaxed">
                    Universitas Catur Insan Cendekia (UCIC) hadir di Cirebon dengan program studi unggulan di bidang Teknologi Informasi, Bisnis Digital, Desain Komunikasi Visual, dan Manajemen modern.
                </p>

                <!-- CTA Group -->
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ route('pmb.register') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-500/25 transition active:scale-95">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span>Daftar Akun CAMABA</span>
                    </a>

                    <a href="{{ route('pmb.login') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 shadow-xs transition">
                        <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        <span>Masuk ke Akun PMB</span>
                    </a>

                    <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=Halo%20Kak%2C%20saya%20ingin%20konsultasi%20PMB%20UCIC"
                       target="_blank"
                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-2xl text-sm font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition">
                        <span>💬 Tanya CS via WhatsApp</span>
                    </a>
                </div>

                <!-- Info Badges -->
                <div class="mt-10 grid grid-cols-2 sm:grid-cols-4 gap-3 text-left">
                    <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-2xs">
                        <span class="text-xs text-slate-400 block font-semibold">Biaya Formulir</span>
                        <span class="text-base font-extrabold text-slate-900 font-display">Rp 300.000</span>
                    </div>
                    <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-2xs">
                        <span class="text-xs text-slate-400 block font-semibold">Metode Bayar</span>
                        <span class="text-base font-extrabold text-slate-900 font-display">QRIS / VA Online</span>
                    </div>
                    <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-2xs">
                        <span class="text-xs text-slate-400 block font-semibold">Proses Seleksi</span>
                        <span class="text-base font-extrabold text-slate-900 font-display">Online & Cepat</span>
                    </div>
                    <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-2xs">
                        <span class="text-xs text-slate-400 block font-semibold">Konsultasi</span>
                        <span class="text-base font-extrabold text-emerald-600 font-display">Gratis 24 Jam</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Alur Pendaftaran Section -->
    <section id="alur" class="py-16 bg-white border-y border-slate-200/70">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="text-xs font-extrabold uppercase tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">
                    Langkah Mudah
                </span>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2 font-display">Alur Pendaftaran CAMABA</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">4 langkah sederhana untuk bergabung menjadi mahasiswa baru UCIC</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Step 1 -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 relative">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white font-black text-sm flex items-center justify-center mb-4 shadow-xs">
                        1
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Registrasi Akun</h3>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                        Daftar akun baru menggunakan email aktif, nomor WhatsApp, dan tentukan password akun portal PMB Anda.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 relative">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white font-black text-sm flex items-center justify-center mb-4 shadow-xs">
                        2
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Pembayaran Formulir</h3>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                        Lakukan pembayaran biaya pendaftaran Rp 300.000 melalui Midtrans Snap (QRIS, GoPay, ShopeePay, atau VA Bank).
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 relative">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white font-black text-sm flex items-center justify-center mb-4 shadow-xs">
                        3
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Lengkapi Data & Berkas</h3>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                        Pilih program studi yang Anda minati, lengkapi profil biodata diri, dan unggah dokumen persyaratan.
                    </p>
                </div>

                <!-- Step 4 -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 relative">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white font-black text-sm flex items-center justify-center mb-4 shadow-xs">
                        4
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Ujian & Verifikasi</h3>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                        Ikuti tes seleksi online atau verifikasi berkas hingga pengumuman kelulusan dan registrasi ulang.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Program Studi Section -->
    <section id="prodi" class="py-16 bg-slate-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="text-xs font-extrabold uppercase tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">
                    Pilihan Masa Depan
                </span>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2 font-display">Program Studi Unggulan UCIC</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Kurikulum berbasis industri dengan sertifikasi keahlian dan teknologi modern</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($prodis as $p)
                    <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-xs hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="px-2.5 py-1 rounded-lg text-[11px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $p->jenjang ?? 'S1' }}
                                </span>
                                <span class="text-xs font-semibold text-slate-400">
                                    {{ $p->kode }}
                                </span>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 leading-snug">{{ $p->nama }}</h3>
                            <p class="text-xs text-slate-500 mt-1">{{ $p->fakultas ?? 'Universitas CIC' }}</p>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-xs font-medium text-emerald-600">Terakreditasi</span>
                            <a href="{{ route('pmb.register') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline">
                                Pilih Prodi &rarr;
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-8 text-slate-400 text-sm">
                        Data program studi sedang dimuat.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Help & Contact Section -->
    <section id="kontak" class="py-16 bg-white border-t border-slate-200/80">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                <div>
                    <span class="text-xs font-extrabold uppercase tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">
                        Hubungi Kami
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2 font-display">Pusat Layanan PMB UCIC</h2>
                    <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                        Tim Customer Service & Sekretariat PMB UCIC siap membantu menjawab pertanyaan terkait biaya kuliah, beasiswa, pilihan prodi, dan tahapan pendaftaran.
                    </p>

                    <div class="mt-6 space-y-4 text-xs sm:text-sm text-slate-700">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                📍
                            </div>
                            <div>
                                <span class="font-bold block text-slate-900">Alamat Kampus:</span>
                                <span>Jl. Kesambi No. 202, Drajat, Kec. Kesambi, Kota Cirebon, Jawa Barat 45133</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                📞
                            </div>
                            <div>
                                <span class="font-bold block text-slate-900">Telepon Kampus:</span>
                                <a href="tel:0231200418" class="text-blue-600 hover:underline">(0231) 200418</a>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                ✉️
                            </div>
                            <div>
                                <span class="font-bold block text-slate-900">Email Resmi PMB:</span>
                                <a href="mailto:pmb@cic.ac.id" class="text-blue-600 hover:underline">pmb@cic.ac.id</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WhatsApp Card -->
                <div class="p-8 rounded-3xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white shadow-xl flex flex-col justify-between">
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/20 text-white mb-4">
                            💬 Respon Cepat CS PMB
                        </span>
                        <h3 class="text-xl sm:text-2xl font-bold font-display">Konsultasi Langsung via WhatsApp</h3>
                        <p class="text-xs sm:text-sm text-white/80 mt-2 leading-relaxed">
                            Dapatkan panduan pendaftaran secara personal langsung dari staf PMB kami tanpa biaya.
                        </p>
                    </div>

                    <div class="mt-8">
                        <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=halo+kak%2C+saya+ingin+bertanya+berkaitan+dengan+PMB+UCIC&type=phone_number&app_absent=0"
                           target="_blank"
                           class="inline-flex items-center justify-center gap-2 w-full py-3.5 px-6 rounded-2xl font-bold bg-emerald-500 hover:bg-emerald-600 text-white shadow-lg transition active:scale-95">
                            <span>Chat WhatsApp CS PMB (0812-1670-0519)</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-8 bg-slate-900 text-slate-400 text-xs border-t border-slate-800">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" class="h-8 w-auto brightness-0 invert object-contain">
                <div>
                    <span class="text-white font-bold block">Universitas Catur Insan Cendekia (UCIC)</span>
                    <span>Jl. Kesambi No. 202, Cirebon, Jawa Barat 45133</span>
                </div>
            </div>

            <div class="text-center sm:text-right">
                <p>&copy; {{ date('Y') }} PMB UCIC. All Rights Reserved.</p>
                <div class="mt-1 flex items-center justify-center sm:justify-end gap-3 text-slate-500">
                    <a href="{{ route('pmb.portal') }}" class="hover:text-white transition">Beranda</a>
                    <span>•</span>
                    <a href="{{ route('pmb.register') }}" class="hover:text-white transition">Daftar</a>
                    <span>•</span>
                    <a href="{{ route('pmb.login') }}" class="hover:text-white transition">Masuk</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
