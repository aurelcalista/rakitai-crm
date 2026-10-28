@extends('pmb.portal.layout')

@section('title', 'Tes Potensi Akademik (TPA) - Portal PMB UCIC')

@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-12"
     x-data="{
         agree1: false,
         agree2: false,
         get allChecked() { return this.agree1 && this.agree2; },
         checkAll() {
             let state = !this.allChecked;
             this.agree1 = state;
             this.agree2 = state;
         },
         startExamModal: false
     }">

    <!-- =================================================================== -->
    <!-- HERO HEADER: INSTRUKSI UJIAN ONLINE CBT (Matching Screenshot 1 & 3)-->
    <!-- =================================================================== -->
    <div class="bg-[#0b3d82] text-white rounded-3xl p-8 sm:p-12 text-center shadow-md relative overflow-hidden">
        <!-- Subtle Ambient Lighting -->
        <div class="absolute -top-20 -right-20 w-64 h-64 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Circular White Info Badge with 'i' (Matching Screenshot) -->
        <div class="w-16 h-16 rounded-full bg-white text-[#0b3d82] mx-auto flex items-center justify-center font-bold text-2xl shadow-md mb-4">
            <span class="font-serif italic font-bold">i</span>
        </div>

        <h1 class="text-2xl sm:text-4xl font-black font-display tracking-tight text-white">
            Instruksi Ujian Online CBT
        </h1>
        <p class="text-blue-100 text-sm sm:text-base mt-2 font-medium">
            Tes Potensi Akademik
        </p>

        @if(($prospek?->status_ujian ?? '') === 'Sudah Mengerjakan')
            <div class="mt-4 inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-200 border border-emerald-400/40">
                <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Anda Telah Menyelesaikan Ujian Ini (Nilai: {{ $prospek->nilai_ujian !== null ? number_format($prospek->nilai_ujian, 2) : 'Menunggu Penilaian' }})</span>
            </div>
        @endif
    </div>

    <!-- =================================================================== -->
    <!-- 1. INFORMASI UJIAN (Matching Screenshot 1 & 3)                      -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/90 shadow-sm">
        <div class="flex items-center gap-2 text-slate-800 font-bold text-sm sm:text-base font-display mb-6">
            <svg class="w-5 h-5 text-[#0b3d82]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span>Informasi Ujian</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <!-- Waktu -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs text-center flex flex-col items-center justify-center">
                <div class="w-12 h-12 rounded-full bg-blue-50 text-[#0b3d82] flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs text-slate-500 font-medium">Waktu</span>
                <span class="text-base sm:text-lg font-bold text-slate-900 mt-0.5">45 Menit</span>
            </div>

            <!-- Jumlah Soal -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs text-center flex flex-col items-center justify-center">
                <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </div>
                <span class="text-xs text-slate-500 font-medium">Jumlah Soal</span>
                <span class="text-base sm:text-lg font-bold text-slate-900 mt-0.5">15 Soal</span>
            </div>

            <!-- Kesempatan -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs text-center flex flex-col items-center justify-center">
                <div class="w-12 h-12 rounded-full bg-cyan-50 text-cyan-600 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <span class="text-xs text-slate-500 font-medium">Kesempatan</span>
                <span class="text-base sm:text-lg font-bold text-slate-900 mt-0.5">1 Kali</span>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 2. JENIS TES YANG AKAN DIUJIKAN (Matching Screenshot 1 & 3)         -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/90 shadow-sm">
        <div class="flex items-center gap-2 text-slate-800 font-bold text-sm sm:text-base font-display mb-6">
            <svg class="w-5 h-5 text-[#0b3d82]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
            </svg>
            <span>Jenis Tes yang Akan Diujikan</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- 1. Ekonomi & Sosial -->
            <div class="bg-slate-50/70 rounded-2xl p-6 border border-slate-200/70 text-center flex flex-col items-center justify-between">
                <div class="w-14 h-14 rounded-full bg-[#0284c7] text-white flex items-center justify-center mb-4 shadow-xs">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 font-display">Ekonomi & Sosial</h3>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Konsep ekonomi dasar dan pengetahuan sosial
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-200/70 w-full text-xs font-semibold text-slate-600">
                    3 Soal
                </div>
            </div>

            <!-- 2. Bahasa Inggris -->
            <div class="bg-slate-50/70 rounded-2xl p-6 border border-slate-200/70 text-center flex flex-col items-center justify-between">
                <div class="w-14 h-14 rounded-full bg-[#10b981] text-white flex items-center justify-center mb-4 shadow-xs">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 font-display">Bahasa Inggris</h3>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Grammar dan struktur kalimat bahasa Inggris
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-200/70 w-full text-xs font-semibold text-slate-600">
                    3 Soal
                </div>
            </div>

            <!-- 3. Matematika -->
            <div class="bg-slate-50/70 rounded-2xl p-6 border border-slate-200/70 text-center flex flex-col items-center justify-between">
                <div class="w-14 h-14 rounded-full bg-[#f59e0b] text-white flex items-center justify-center mb-4 shadow-xs">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 font-display">Matematika</h3>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Logika angka, deret, dan aljabar dasar
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-200/70 w-full text-xs font-semibold text-slate-600">
                    3 Soal
                </div>
            </div>

            <!-- 4. Pengetahuan Umum -->
            <div class="bg-slate-50/70 rounded-2xl p-6 border border-slate-200/70 text-center flex flex-col items-center justify-between">
                <div class="w-14 h-14 rounded-full bg-[#06b6d4] text-white flex items-center justify-center mb-4 shadow-xs">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 font-display">Pengetahuan Umum</h3>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Sejarah, geografi, hukum, dan penalaran logika
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-200/70 w-full text-xs font-semibold text-slate-600">
                    6 Soal
                </div>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 3. PETUNJUK PENGERJAAN (Matching Screenshot 2)                      -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/90 shadow-sm">
        <div class="flex items-center gap-2 text-slate-800 font-bold text-sm sm:text-base font-display mb-6">
            <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>Petunjuk Pengerjaan</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Aturan Umum (Left Column) -->
            <div class="space-y-4">
                <div class="flex items-center gap-2 text-xs sm:text-sm font-bold text-slate-800 pb-2 border-b border-slate-100">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span>Aturan Umum:</span>
                </div>

                <ul class="space-y-3 text-xs sm:text-sm text-slate-600">
                    <li class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span>Pastikan koneksi internet stabil selama proses ujian berlangsung</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span>Gunakan browser terbaru (Google Chrome atau Mozilla Firefox)</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span>Tutup tab dan aplikasi lain yang tidak diperlukan</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span>Siapkan kertas dan alat tulis untuk coret-coretan berhitung</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span>Pastikan tempat pengerjaan ujian tenang dan bebas gangguan</span>
                    </li>
                </ul>
            </div>

            <!-- Hal yang Tidak Diperbolehkan (Right Column) -->
            <div class="space-y-4">
                <div class="flex items-center gap-2 text-xs sm:text-sm font-bold text-amber-700 pb-2 border-b border-slate-100">
                    <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Hal yang Tidak Diperbolehkan:</span>
                </div>

                <ul class="space-y-3 text-xs sm:text-sm text-slate-600">
                    <li class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <span>Membuka tab atau aplikasi lain selama ujian berlangsung</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <span>Mencari kunci jawaban atau bantuan melalui internet / AI</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <span>Bertanya atau meminta bantuan jawaban kepada orang lain</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <span>Menggunakan kalkulator atau aplikasi alat bantu hitung digital</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <span>Meninggalkan halaman ujian sebelum waktu pengerjaan berakhir</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 4. CARA MENGERJAKAN (Matching Screenshot 2)                         -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/90 shadow-sm">
        <div class="flex items-center gap-2 text-slate-800 font-bold text-sm sm:text-base font-display mb-6">
            <svg class="w-5 h-5 text-[#0b3d82]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Cara Mengerjakan</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs sm:text-sm">
            <!-- Step 1 -->
            <div class="flex items-start gap-3.5">
                <div class="w-7 h-7 rounded-full bg-[#0b3d82] text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                    1
                </div>
                <div>
                    <h4 class="font-bold text-slate-800">Baca Soal dengan Teliti</h4>
                    <p class="text-slate-500 mt-0.5">Pahami maksud dan instruksi soal sebelum menentukan jawaban yang benar.</p>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="flex items-start gap-3.5">
                <div class="w-7 h-7 rounded-full bg-[#10b981] text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                    4
                </div>
                <div>
                    <h4 class="font-bold text-slate-800">Pantau Waktu Pengerjaan</h4>
                    <p class="text-slate-500 mt-0.5">Timer akan terus menghitung mundur sisa waktu pengerjaan 45 menit.</p>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="flex items-start gap-3.5">
                <div class="w-7 h-7 rounded-full bg-[#0b3d82] text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                    2
                </div>
                <div>
                    <h4 class="font-bold text-slate-800">Pilih Jawaban yang Tepat</h4>
                    <p class="text-slate-500 mt-0.5">Klik salah satu opsi jawaban A, B, C, D, atau E yang paling sesuai.</p>
                </div>
            </div>

            <!-- Step 5 -->
            <div class="flex items-start gap-3.5">
                <div class="w-7 h-7 rounded-full bg-[#f59e0b] text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                    5
                </div>
                <div>
                    <h4 class="font-bold text-slate-800">Periksa Kembali Jawaban</h4>
                    <p class="text-slate-500 mt-0.5">Pastikan tidak ada nomor soal yang terlewat sebelum mengumpulkan hasil.</p>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="flex items-start gap-3.5">
                <div class="w-7 h-7 rounded-full bg-[#0b3d82] text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                    3
                </div>
                <div>
                    <h4 class="font-bold text-slate-800">Navigasi Soal Mudah</h4>
                    <p class="text-slate-500 mt-0.5">Gunakan tombol "Sebelumnya / Selanjutnya" atau klik nomor soal langsung.</p>
                </div>
            </div>

            <!-- Step 6 -->
            <div class="flex items-start gap-3.5">
                <div class="w-7 h-7 rounded-full bg-[#0b3d82] text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                    6
                </div>
                <div>
                    <h4 class="font-bold text-slate-800">Selesaikan Ujian</h4>
                    <p class="text-slate-500 mt-0.5">Klik tombol "Selesai" untuk mengakhiri dan mengirimkan jawaban Anda.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 5. CHECKBOX PERSETUJUAN & TOMBOL AKSI (Matching Screenshot 5)       -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/90 shadow-sm text-center space-y-6">
        <!-- Checkboxes -->
        <div class="max-w-xl mx-auto space-y-3 text-left">
            <label class="flex items-start gap-3 cursor-pointer text-xs sm:text-sm text-slate-700 select-none">
                <input type="checkbox"
                       x-model="agree1"
                       class="w-4 h-4 mt-0.5 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82]">
                <span>Saya berjanji akan mengerjakan ujian dengan jujur dan tidak menyontek.</span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer text-xs sm:text-sm text-slate-700 select-none">
                <input type="checkbox"
                       x-model="agree2"
                       class="w-4 h-4 mt-0.5 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82]">
                <span>Saya siap memulai ujian dan sudah berdoa.</span>
            </label>
        </div>

        <!-- Tombol Centang Semua (Matching Screenshot 5) -->
        <div>
            <button type="button"
                    @click="checkAll()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-[#0b3d82] bg-blue-50 hover:bg-blue-100 border border-blue-200 transition active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span x-text="allChecked ? 'Batal Centang' : 'Centang Semua'">Centang Semua</span>
            </button>
        </div>

        <!-- Action Buttons (Kembali & Mulai Ujian) -->
        <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('pmb.akun.dashboard') }}"
               class="inline-flex items-center justify-center gap-1.5 px-6 py-3 rounded-xl font-bold text-xs text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 transition w-full sm:w-auto">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Kembali</span>
            </a>

            <button type="button"
                    @click="startExamModal = true"
                    :disabled="!allChecked"
                    class="inline-flex items-center justify-center gap-2 px-8 py-3 rounded-xl font-bold text-xs text-white bg-[#10b981] hover:bg-[#059669] shadow-md shadow-emerald-500/20 transition active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed w-full sm:w-auto cursor-pointer">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                </svg>
                <span>Mulai Ujian Sekarang</span>
            </button>
        </div>

        <p class="text-xs text-slate-400">
            Ujian ini akan dimulai segera setelah Anda klik tombol &quot;Mulai Ujian&quot;
        </p>

        <!-- Divider Atau -->
        <div class="pt-3">
            <span class="text-xs text-slate-400 font-medium block mb-3">Atau</span>

            <!-- Tombol Mulai Uji Coba Ujian Dulu (Matching Screenshot 5) -->
            <button type="button"
                    @click="alert('Fitur Simulasi / Uji Coba Soal Ujian Online akan segera dibuka sesuai jadwal gelombang PMB UCIC.')"
                    class="px-8 py-3 rounded-xl font-bold text-xs text-white bg-[#004b99] hover:bg-[#003d7c] shadow-md transition active:scale-95 cursor-pointer">
                Mulai Uji Coba Ujian Dulu!
            </button>
        </div>
    </div>

    <!-- Modal Konfirmasi Mulai Ujian -->
    <div x-show="startExamModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-200 text-center space-y-4"
             @click.away="startExamModal = false">
            <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <h3 class="text-lg font-bold text-slate-900 font-display">Ruang Ujian CBT Siap</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
                Anda terdaftar sebagai peserta Tes Potensi Akademik PMB UCIC. Sesi ujian berlangsung selama <strong>45 menit (15 soal)</strong>. Pastikan Anda tidak menutup halaman ini selama ujian.
            </p>

            <div class="p-3 rounded-xl bg-blue-50 border border-blue-200 text-xs text-[#0b3d82]">
                Peserta: <strong>{{ $user->name }}</strong> (No. Reg: {{ $prospek->no_registrasi ?? '-' }})
            </div>

            <div class="pt-2 flex items-center justify-center gap-3">
                <button type="button"
                        @click="startExamModal = false"
                        class="px-5 py-2.5 rounded-xl font-bold text-xs text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    Tutup
                </button>
                <button type="button"
                        @click="startExamModal = false; alert('Sesi CBT terhubung. Panitia PMB akan mengumumkan pembukaan sesi pengerjaan soal secara terjadwal.')"
                        class="px-6 py-2.5 rounded-xl font-bold text-xs text-white bg-[#10b981] hover:bg-[#059669] transition">
                    Lanjut Masuk Ruang Ujian
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
