@extends('pmb.portal.layout')

@section('title', 'Katalog Program Studi - Portal PMB UCIC')

@section('content')
<div class="max-w-6xl mx-auto space-y-8 pb-16 relative"
     x-data="{
         search: '',
         selectedFakultas: 'Semua',
         selectedJenjang: 'Semua',
         totalItems: {{ count($prodis) }},
         get visibleCount() {
             let count = 0;
             const items = document.querySelectorAll('.prodi-card');
             items.forEach(el => {
                 if (el.style.display !== 'none') count++;
             });
             return count;
         },
         isMatch(nama, kode, fakultas, jenjang, desc, karir) {
             const q = this.search.toLowerCase().trim();
             const hay = (nama + ' ' + kode + ' ' + fakultas + ' ' + jenjang + ' ' + desc + ' ' + karir).toLowerCase();
             const matchSearch = !q || hay.includes(q);
             const matchFakultas = this.selectedFakultas === 'Semua' || fakultas === this.selectedFakultas;
             const matchJenjang = this.selectedJenjang === 'Semua' || jenjang === this.selectedJenjang;
             return matchSearch && matchFakultas && matchJenjang;
         },
         resetFilters() {
             this.search = '';
             this.selectedFakultas = 'Semua';
             this.selectedJenjang = 'Semua';
         }
     }">

    <!-- =================================================================== -->
    <!-- 1. HEADER UTAMA: KATALOG PROGRAM STUDI                              -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-2xl sm:rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2">
            <div class="flex items-center gap-3 text-[#0b3d82]">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200/80 text-[#0b3d82] flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0b3d82] font-display tracking-tight">
                        Katalog Program Studi
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-600 mt-0.5">
                        Universitas Catur Insan Cendekia (UCIC) • Pilihan Tepat Untuk Masa Depan
                    </p>
                </div>
            </div>

            <!-- Ringkasan Statistik -->
            <div class="pt-2 flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-semibold bg-blue-50 text-[#0b3d82] border border-blue-200">
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    {{ count($prodis) }} Program Studi Aktif
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    {{ count($fakultasList) }} Fakultas
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    Jenjang: S1, D3, S2
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                    Akreditasi: Baik Sekali
                </span>
            </div>
        </div>

        <!-- Tombol Aksi Navigasi Kembali ke Dashboard -->
        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('pmb.akun.dashboard') }}"
               class="w-full sm:w-auto px-5 py-3 rounded-xl font-bold text-xs sm:text-sm text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition flex items-center justify-center gap-2 shadow-2xs">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('pmb.akun.profil') }}"
               class="w-full sm:w-auto px-5 py-3 rounded-xl font-bold text-xs sm:text-sm text-white bg-[#0b3d82] hover:bg-[#072b5c] transition flex items-center justify-center gap-2 shadow-xs">
                <span>Form Pendaftaran</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 2. CARD RESMI BROSUR / KATALOG UCIC (Sesuai Referensi Screenshot)    -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
        <!-- Top Bar matching Screenshot 1 -->
        <div class="bg-[#0b3d82] px-6 py-4 flex flex-wrap items-center justify-between gap-3 text-white">
            <div class="flex items-center gap-2.5 font-bold text-base font-display">
                <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/>
                </svg>
                <span>Katalog UCIC</span>
            </div>

            <div class="flex items-center gap-2">
                <button type="button"
                        @click="resetFilters()"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white/10 hover:bg-white/20 text-white transition flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Refresh</span>
                </button>
                <a href="{{ route('pmb.akun.profil') }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-[#ca8a04] hover:bg-[#a16207] text-white transition flex items-center gap-1.5 shadow-xs">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Download Katalog / Formulir</span>
                </a>
            </div>
        </div>

        <!-- Banner Kampus Teknologi AI & Akreditasi matching Screenshot 1 -->
        <div class="p-6 sm:p-8 bg-gradient-to-br from-blue-50/70 via-white to-slate-50 relative overflow-hidden">
            <div class="max-w-3xl space-y-4">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-blue-100 text-[#0b3d82] text-xs font-bold">
                    <span>#KAMPUSTEKNOLOGIAI</span>
                    <span>•</span>
                    <span>#UCICGROW</span>
                </div>

                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-display leading-tight">
                    Universitas Catur Insan Cendekia (UCIC)
                </h2>
                <div class="inline-block px-3 py-1 rounded-lg bg-[#0b3d82] text-white font-bold text-xs uppercase tracking-wider">
                    Pilihan Tepat Untuk Masa Depan
                </div>

                <p class="text-sm sm:text-base text-slate-600 leading-relaxed max-w-2xl">
                    Kampus Teknologi AI yang Berdampak dan Mencetak Entrepreneur Unggul. Belajar langsung dari dunia industri, UCIC jadi pilihan tepat untuk masa depanmu!
                </p>

                <!-- Informasi Gelombang Aktif & Pendaftaran -->
                <div class="pt-2 flex flex-wrap items-center gap-4 text-xs text-slate-600">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <strong class="text-slate-800">Pendaftaran PMB 2027/2028 Dibuka</strong>
                    </div>
                    <span class="text-slate-300">|</span>
                    <span>Kuliah Reguler, Karyawan & Eksekutif</span>
                    <span class="text-slate-300">|</span>
                    <span class="text-[#0b3d82] font-semibold">Kampus Cirebon: Jl. Kesambi No. 202</span>
                </div>
            </div>

            <!-- Badge Akreditasi Emas matching Screenshot 1 -->
            <div class="mt-6 sm:mt-0 sm:absolute sm:top-8 sm:right-8 flex flex-col items-center">
                <div class="w-24 h-24 rounded-full border-4 border-[#ca8a04] bg-amber-50 text-[#854d0e] flex flex-col items-center justify-center text-center p-2 shadow-sm">
                    <span class="text-[10px] font-black uppercase tracking-wider leading-tight">BAIK SEKALI</span>
                    <span class="text-[8px] font-bold text-amber-700 mt-0.5">AKREDITASI</span>
                    <span class="text-[8px] font-semibold text-slate-500">BAN-PT</span>
                </div>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 3. KONTROL PENCARIAN & FILTER PROGRAM STUDI (Live Reactive)          -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-2xl sm:rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-900 font-display">
                    Cari dan Filter Program Studi
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Gunakan pencarian kata kunci atau pilih fakultas dan jenjang pendidikan.
                </p>
            </div>

            <!-- Tombol Reset Filter jika sedang aktif -->
            <button type="button"
                    x-show="search !== '' || selectedFakultas !== 'Semua' || selectedJenjang !== 'Semua'"
                    @click="resetFilters()"
                    x-cloak
                    class="text-xs font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1 self-start sm:self-auto cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span>Reset Semua Filter</span>
            </button>
        </div>

        <!-- Input Pencarian -->
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input type="text"
                   x-model="search"
                   placeholder="Cari program studi (contoh: Informatika, Manajemen, DKV, S1, S2)..."
                   class="w-full pl-10 pr-10 py-3 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#0b3d82] focus:border-[#0b3d82] bg-slate-50/50 text-slate-800 transition">
            <button type="button"
                    x-show="search.length > 0"
                    @click="search = ''"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Filter Baris 1: Fakultas -->
        <div class="space-y-2">
            <span class="text-xs font-bold text-slate-700 block">Pilih Fakultas:</span>
            <div class="flex flex-wrap gap-2">
                <button type="button"
                        @click="selectedFakultas = 'Semua'"
                        :class="selectedFakultas === 'Semua' ? 'bg-[#0b3d82] text-white font-bold' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                        class="px-3.5 py-1.5 rounded-xl text-xs transition cursor-pointer">
                    Semua Fakultas ({{ count($prodis) }})
                </button>
                @foreach($fakultasList as $fak)
                    <button type="button"
                            @click="selectedFakultas = '{{ $fak }}'"
                            :class="selectedFakultas === '{{ $fak }}' ? 'bg-[#0b3d82] text-white font-bold' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                            class="px-3.5 py-1.5 rounded-xl text-xs transition cursor-pointer">
                        {{ $fak }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Filter Baris 2: Jenjang Pendidikan -->
        <div class="space-y-2 pt-1 border-t border-slate-100">
            <span class="text-xs font-bold text-slate-700 block">Jenjang Pendidikan:</span>
            <div class="flex flex-wrap gap-2">
                <button type="button"
                        @click="selectedJenjang = 'Semua'"
                        :class="selectedJenjang === 'Semua' ? 'bg-[#0b3d82] text-white font-bold' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                        class="px-3.5 py-1.5 rounded-xl text-xs transition cursor-pointer">
                    Semua Jenjang
                </button>
                @foreach($jenjangList as $jjg)
                    <button type="button"
                            @click="selectedJenjang = '{{ $jjg }}'"
                            :class="selectedJenjang === '{{ $jjg }}' ? 'bg-[#0b3d82] text-white font-bold' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                            class="px-3.5 py-1.5 rounded-xl text-xs transition cursor-pointer">
                        {{ $jjg }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 4. DAFTAR CARD PROGRAM STUDI                                        -->
    <!-- =================================================================== -->
    <div class="space-y-4">
        <div class="flex items-center justify-between text-xs text-slate-500 px-1">
            <span>Daftar Jurusan & Program Studi Unggulan UCIC:</span>
            <span class="font-semibold text-slate-700">
                Menampilkan <span x-text="visibleCount"></span> dari {{ count($prodis) }} program studi
            </span>
        </div>

        <!-- Grid Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($prodis as $prodi)
                @php
                    $isUserChoice = !empty($prospek?->prodi_id) && $prospek->prodi_id == $prodi->id;
                    $jenjangColor = match($prodi->jenjang) {
                        'S1' => 'bg-blue-100 text-blue-800 border-blue-200',
                        'D3' => 'bg-amber-100 text-amber-800 border-amber-200',
                        'S2' => 'bg-purple-100 text-purple-800 border-purple-200',
                        default => 'bg-slate-100 text-slate-800 border-slate-200',
                    };
                @endphp

                <div class="prodi-card bg-white rounded-2xl border {{ $isUserChoice ? 'border-emerald-400 ring-2 ring-emerald-100' : 'border-slate-200/90' }} shadow-xs hover:shadow-md hover:border-[#0b3d82] transition flex flex-col justify-between p-6 space-y-4"
                     x-show="isMatch(
                         '{{ addslashes($prodi->nama) }}',
                         '{{ addslashes($prodi->kode) }}',
                         '{{ addslashes($prodi->fakultas) }}',
                         '{{ addslashes($prodi->jenjang) }}',
                         '{{ addslashes($prodi->deskripsi) }}',
                         '{{ addslashes(implode(' ', $prodi->karir ?? [])) }}'
                     )">

                    <div class="space-y-3">
                        <!-- Card Header: Badges -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold border {{ $jenjangColor }}">
                                    {{ $prodi->jenjang }}
                                </span>
                                <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                                    {{ $prodi->kode }}
                                </span>
                            </div>

                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                {{ $prodi->akreditasi }}
                            </span>
                        </div>

                        <!-- Fakultas -->
                        <div class="text-[11px] font-semibold text-[#0b3d82] flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span>{{ $prodi->fakultas }}</span>
                        </div>

                        <!-- Judul Nama Prodi -->
                        <h4 class="text-lg font-bold text-slate-900 font-display leading-snug">
                            {{ $prodi->nama }}
                        </h4>

                        <!-- Deskripsi -->
                        <p class="text-xs text-slate-600 leading-relaxed line-clamp-3">
                            {{ $prodi->deskripsi }}
                        </p>

                        <!-- Box Info Tambahan: Kuota, Masa Studi & Gelar -->
                        <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-1.5 text-xs text-slate-700">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Gelar Kelulusan:</span>
                                <strong class="text-slate-900 font-bold">{{ $prodi->gelar }}</strong>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Masa Studi:</span>
                                <span class="font-medium text-slate-800">{{ $prodi->masa_studi }}</span>
                            </div>
                            @if(!empty($prodi->kuota))
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">Kuota Penerimaan:</span>
                                    <span class="font-medium text-slate-800">{{ $prodi->kuota }} Kursi</span>
                                </div>
                            @endif
                        </div>

                        <!-- Prospek Karir Lulusan -->
                        @if(!empty($prodi->karir))
                            <div class="space-y-1.5 pt-1">
                                <span class="text-[11px] font-bold text-slate-700 block">Prospek Karir Utama:</span>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach(array_slice($prodi->karir, 0, 3) as $k)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600">
                                            {{ $k }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Card Footer: Aksi & Indikator Pilihan -->
                    <div class="pt-3 border-t border-slate-100 space-y-2">
                        @if($isUserChoice)
                            <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold text-center flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Jurusan Pilihan Anda Saat Ini</span>
                            </div>
                        @else
                            <a href="{{ route('pmb.akun.profil') }}"
                               class="w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-blue-50 text-[#0b3d82] hover:bg-[#0b3d82] hover:text-white border border-blue-200 hover:border-[#0b3d82] transition flex items-center justify-center gap-1.5">
                                <span>Pilih di Form Pendaftaran</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        @endif
                    </div>

                </div>
            @endforeach
        </div>

        <!-- Empty State ketika filter tidak menghasilkan apapun -->
        <div x-show="visibleCount === 0" x-cloak class="bg-white rounded-2xl p-10 border border-slate-200 text-center space-y-3">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h4 class="text-base font-bold text-slate-800 font-display">
                Program Studi Tidak Ditemukan
            </h4>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">
                Tidak ada program studi yang cocok dengan kata kunci atau kombinasi filter fakultas & jenjang yang dipilih.
            </p>
            <button type="button"
                    @click="resetFilters()"
                    class="px-4 py-2 rounded-xl text-xs font-bold bg-[#0b3d82] text-white hover:bg-[#072b5c] transition cursor-pointer">
                Reset Filter Pencarian
            </button>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 5. NAVIGASI BAWAH: KEMBALI KE DASHBOARD & FORM PENDAFTARAN          -->
    <!-- =================================================================== -->
    <div class="pt-6 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
        <a href="{{ route('pmb.akun.dashboard') }}"
           class="w-full sm:w-auto px-6 py-3 rounded-xl font-bold text-xs sm:text-sm text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition text-center shadow-2xs">
            &larr; Kembali ke Dashboard
        </a>

        <div class="flex items-center gap-3 w-full sm:w-auto">
            <a href="{{ route('pmb.akun.upload-dokumen') }}"
               class="w-full sm:w-auto px-6 py-3 rounded-xl font-bold text-xs sm:text-sm text-[#0b3d82] bg-blue-50 border border-blue-200 hover:bg-blue-100 transition text-center">
                Upload Dokumen &rarr;
            </a>

            <a href="{{ route('pmb.akun.profil') }}"
               class="w-full sm:w-auto px-6 py-3 rounded-xl font-bold text-xs sm:text-sm text-white bg-[#0b3d82] hover:bg-[#072b5c] transition text-center shadow-xs">
                Lanjut ke Form Pendaftaran &rarr;
            </a>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 6. FLOATING SCROLL TO TOP BUTTON                                    -->
    <!-- =================================================================== -->
    <button type="button"
            onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
            class="fixed bottom-6 right-6 w-10 h-10 rounded-lg bg-[#0b3d82] hover:bg-[#072b5c] text-white flex items-center justify-center shadow-lg transition z-40 cursor-pointer"
            title="Kembali ke atas">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
        </svg>
    </button>

</div>
@endsection

