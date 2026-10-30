@extends('pmb.portal.layout')

@section('title', 'Detail Biaya Kuliah - Portal PMB UCIC')

@push('styles')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<style>
    /* Fullscreen Mode Styling */
    #dokumen-biaya:fullscreen {
        background: #f8fafc;
        padding: 24px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }
    #dokumen-biaya:-webkit-full-screen {
        background: #f8fafc;
        padding: 24px;
        overflow-y: auto;
    }
</style>
@endpush

@section('content')
<div class="space-y-8">

    @php
        $isPaid = ($transaksiDaftar?->payment_status ?? '') === \App\Models\Transaksi::STATUS_VERIFIED;
    @endphp

    <!-- Top Blue Hero Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-[#0b3d82] text-white py-12 px-6 sm:px-12 text-center shadow-lg">
        <!-- Background decorative translucent circles -->
        <div class="absolute -top-12 -left-12 w-48 h-48 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute top-1/2 -right-8 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute bottom-2 left-1/3 w-32 h-32 rounded-full bg-white/5 pointer-events-none"></div>

        <div class="relative z-10 max-w-2xl mx-auto space-y-3">
            <span class="inline-flex items-center gap-1 px-3.5 py-1 rounded-full text-xs font-semibold bg-white/15 text-white">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Informasi Pembayaran</span>
            </span>

            <h1 class="text-3xl sm:text-4xl font-black font-display tracking-tight text-white">
                Detail Biaya Kuliah
            </h1>

            <p class="text-xs sm:text-sm text-blue-100 max-w-xl mx-auto">
                Informasi lengkap mengenai biaya pendidikan dan cara pembayaran di Universitas Catur Insan Cendekia
            </p>

            <div class="pt-4 flex flex-wrap items-center justify-center gap-3">
                <a href="#dokumen-biaya"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-xs bg-white text-[#0b3d82] hover:bg-slate-100 shadow-sm transition">
                    <svg class="w-4 h-4 text-[#0b3d82]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Lihat Dokumen Resmi</span>
                </a>

                <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=Halo%20Admin%20PMB%20UCIC%2C%20saya%20ingin%20konsultasi%20mengenai%20rincian%20biaya%20kuliah"
                   target="_blank"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-xs bg-transparent border border-white text-white hover:bg-white/10 transition">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <span>Konsultasi CS</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 3 Highlight Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- 1. Biaya Transparan -->
        <div class="bg-white rounded-3xl p-8 border border-slate-200/90 shadow-sm text-center flex flex-col items-center">
            <div class="w-16 h-16 rounded-full bg-blue-500 text-white flex items-center justify-center mb-5 shadow-xs">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 font-display">Biaya Transparan</h3>
            <p class="text-xs text-slate-500 mt-2 max-w-xs leading-relaxed">
                Tanpa biaya tersembunyi, seluruh rincian komponen biaya tertera jelas dan resmi.
            </p>
        </div>

        <!-- 2. Program Beasiswa -->
        <div class="bg-white rounded-3xl p-8 border border-slate-200/90 shadow-sm text-center flex flex-col items-center">
            <div class="w-16 h-16 rounded-full bg-blue-500 text-white flex items-center justify-center mb-5 shadow-xs">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 font-display">Program Beasiswa</h3>
            <p class="text-xs text-slate-500 mt-2 max-w-xs leading-relaxed">
                Tersedia Beasiswa Rektor, KIP Kuliah, dan Beasiswa Prestasi bagi calon mahasiswa unggul.
            </p>
        </div>

        <!-- 3. Konsultasi 24/7 -->
        <div class="bg-white rounded-3xl p-8 border border-slate-200/90 shadow-sm text-center flex flex-col items-center">
            <div class="w-16 h-16 rounded-full bg-blue-500 text-white flex items-center justify-center mb-5 shadow-xs">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 font-display">Konsultasi 24/7</h3>
            <p class="text-xs text-slate-500 mt-2 max-w-xs leading-relaxed">
                Tim panitia PMB UCIC siap melayani konsultasi pemilihan prodi dan skema angsuran.
            </p>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- DOKUMEN BIAYA KULIAH (RESMI PDF DOCUMENT VIEWER)                    -->
    <!-- Menggantikan Google Sheets embed dengan Document Viewer Resmi       -->
    <!-- =================================================================== -->
    <div id="dokumen-biaya"
         class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-sm space-y-6"
         x-data="{
             pdfUrl: '{{ asset('documents/rincian-biaya-pmb-ucic-2027.pdf') }}',
             pageNum: 1,
             numPages: 1,
             scale: 1.3,
             isLoading: true,
             useCanvas: true,
             isFullscreen: false,
             pdfDoc: null,
             initViewer() {
                 const self = this;
                 if (typeof pdfjsLib !== 'undefined') {
                     pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                     pdfjsLib.getDocument(self.pdfUrl).promise.then(function(pdf) {
                         self.pdfDoc = pdf;
                         self.numPages = pdf.numPages;
                         self.isLoading = false;
                         self.renderPage(self.pageNum);
                     }).catch(function(err) {
                         console.warn('PDF.js canvas fallback to native frame:', err);
                         self.useCanvas = false;
                         self.isLoading = false;
                     });
                 } else {
                     self.useCanvas = false;
                     self.isLoading = false;
                 }
             },
             renderPage(num) {
                 if (!this.pdfDoc) return;
                 const self = this;
                 const canvas = document.getElementById('pdf-canvas');
                 if (!canvas) return;
                 const ctx = canvas.getContext('2d');

                 this.pdfDoc.getPage(num).then(function(page) {
                     const viewport = page.getViewport({ scale: self.scale });
                     canvas.height = viewport.height;
                     canvas.width = viewport.width;

                     const renderContext = {
                         canvasContext: ctx,
                         viewport: viewport
                     };
                     page.render(renderContext);
                 });
             },
             zoomIn() {
                 if (this.scale < 2.5) {
                     this.scale = Math.round((this.scale + 0.2) * 10) / 10;
                     this.renderPage(this.pageNum);
                 }
             },
             zoomOut() {
                 if (this.scale > 0.6) {
                     this.scale = Math.round((this.scale - 0.2) * 10) / 10;
                     this.renderPage(this.pageNum);
                 }
             },
             resetZoom() {
                 this.scale = 1.3;
                 this.renderPage(this.pageNum);
             },
             prevPage() {
                 if (this.pageNum > 1) {
                     this.pageNum--;
                     this.renderPage(this.pageNum);
                 }
             },
             nextPage() {
                 if (this.pageNum < this.numPages) {
                     this.pageNum++;
                     this.renderPage(this.pageNum);
                 }
             },
             toggleFullscreen() {
                 const elem = document.getElementById('dokumen-biaya');
                 if (!document.fullscreenElement) {
                     if (elem.requestFullscreen) {
                         elem.requestFullscreen();
                         this.isFullscreen = true;
                     }
                 } else {
                     if (document.exitFullscreen) {
                         document.exitFullscreen();
                         this.isFullscreen = false;
                     }
                 }
             }
         }"
         x-init="initViewer()">

        <!-- Header Dokumen Biaya Kuliah & Tombol Aksi Utama -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    <h2 class="text-xl font-extrabold text-[#0b3d82] font-display">
                        Dokumen Biaya Kuliah
                    </h2>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Informasi lengkap biaya kuliah seluruh program studi UCIC
                </p>
            </div>

            <!-- Tombol Dokumen Wajib: Unduh PDF, Buka Tab Baru, Perbesar/Layar Penuh -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- 1. Unduh Dokumen PDF -->
                <a href="{{ asset('documents/rincian-biaya-pmb-ucic-2027.pdf') }}"
                   download="Rincian Biaya PMB UCIC 2027.pdf"
                   class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold bg-[#0b3d82] text-white hover:bg-[#072b5c] transition shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Unduh Dokumen PDF</span>
                </a>

                <!-- 2. Buka Dokumen di Tab Baru -->
                <a href="{{ asset('documents/rincian-biaya-pmb-ucic-2027.pdf') }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition shadow-2xs">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    <span>Buka Dokumen di Tab Baru</span>
                </a>

                <!-- 3. Perbesar / Layar Penuh -->
                <button type="button"
                        @click="toggleFullscreen()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition shadow-2xs cursor-pointer">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                    </svg>
                    <span x-text="isFullscreen ? 'Kecilkan' : 'Perbesar / Layar Penuh'"></span>
                </button>
            </div>
        </div>

        <!-- Toolbar Kontrol PDF: Navigasi Halaman & Kontrol Zoom -->
        <div class="bg-slate-100/90 rounded-2xl p-3 px-4 border border-slate-200/80 flex flex-wrap items-center justify-between gap-3 text-xs">
            <!-- Navigasi Halaman -->
            <div class="flex items-center gap-2">
                <button type="button"
                        @click="prevPage()"
                        :disabled="pageNum <= 1"
                        :class="pageNum <= 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-200 cursor-pointer'"
                        class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-300 text-slate-700 font-bold transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Hal Sebelumnya</span>
                </button>

                <span class="px-2 font-semibold text-slate-700">
                    Halaman <span x-text="pageNum" class="font-bold text-[#0b3d82]">1</span> dari <span x-text="numPages" class="font-bold text-slate-900">1</span>
                </span>

                <button type="button"
                        @click="nextPage()"
                        :disabled="pageNum >= numPages"
                        :class="pageNum >= numPages ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-200 cursor-pointer'"
                        class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-300 text-slate-700 font-bold transition flex items-center gap-1">
                    <span>Hal Berikutnya</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

            <!-- Kontrol Zoom & Ukuran Nyaman Dibaca -->
            <div class="flex items-center gap-2">
                <button type="button"
                        @click="zoomOut()"
                        title="Perkecil Tampilan"
                        class="w-8 h-8 rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-200 font-bold transition flex items-center justify-center cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                </button>

                <span class="font-mono text-xs font-bold text-slate-800 px-1 min-w-[45px] text-center"
                      x-text="Math.round(scale * 100) + '%'">
                    130%
                </span>

                <button type="button"
                        @click="zoomIn()"
                        title="Perbesar Tampilan"
                        class="w-8 h-8 rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-200 font-bold transition flex items-center justify-center cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>

                <button type="button"
                        @click="resetZoom()"
                        class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-200 font-semibold transition cursor-pointer">
                    Reset Zoom
                </button>
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- CANVAS / PDF DOCUMENT VIEWPORT (LATAR BELAKANG PUTIH RESMI)     -->
        <!-- =============================================================== -->
        <div id="pdf-viewport-container"
             class="bg-slate-100 rounded-2xl p-3 sm:p-6 overflow-auto border border-slate-200 flex flex-col items-center justify-start min-h-[550px] max-h-[850px] shadow-inner relative">

            <!-- Loading Spinner State -->
            <div x-show="isLoading" class="py-24 text-center space-y-3">
                <div class="w-10 h-10 border-4 border-[#0b3d82] border-t-transparent rounded-full animate-spin mx-auto"></div>
                <p class="text-xs font-semibold text-slate-600">Memuat Dokumen Biaya Kuliah Resmi UCIC...</p>
            </div>

            <!-- Canvas Viewport (PDF.js Engine) -->
            <div x-show="!isLoading && useCanvas" class="w-full flex justify-center overflow-x-auto">
                <canvas id="pdf-canvas" class="bg-white shadow-lg rounded-lg border border-slate-200/90 max-w-full h-auto"></canvas>
            </div>

            <!-- Fallback Native Embed if PDF.js is unavailable -->
            <div x-show="!isLoading && !useCanvas" class="w-full h-[650px]">
                <iframe src="{{ asset('documents/rincian-biaya-pmb-ucic-2027.pdf') }}#toolbar=0"
                        title="Dokumen Biaya Kuliah UCIC"
                        class="w-full h-full rounded-xl border border-slate-300 bg-white"></iframe>
            </div>

        </div>

        <!-- =============================================================== -->
        <!-- TABEL RINCIAN LENGKAP BIAYA KULIAH (MEMPERTAHANKAN NOMINAL ASLI)-->
        <!-- =============================================================== -->
        <div class="space-y-4 pt-2">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 font-display">
                    Ringkasan Biaya Kuliah per Semester (SPP / UKT)
                </h3>
                <span class="text-xs text-slate-500">Tahun Akademik 2027/2028</span>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#0b3d82] text-white font-semibold">
                        <tr>
                            <th class="px-4 py-3.5">Fakultas / Program Studi</th>
                            <th class="px-4 py-3.5 text-center">Jenjang</th>
                            <th class="px-4 py-3.5">Kelas</th>
                            <th class="px-4 py-3.5">Biaya Pendaftaran</th>
                            <th class="px-4 py-3.5">Estimasi Biaya / Semester</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-semibold text-slate-900">S1 Teknik Informatika</td>
                            <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                            <td class="px-4 py-3">Reguler Pagi</td>
                            <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                            <td class="px-4 py-3 font-bold text-slate-900">Rp 3.500.000</td>
                        </tr>
                        <tr class="hover:bg-slate-50 transition bg-amber-50/20">
                            <td class="px-4 py-3 font-semibold text-slate-900">S1 Teknik Informatika (Kelas Sore/Karyawan)</td>
                            <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                            <td class="px-4 py-3">Kelas Sore</td>
                            <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                            <td class="px-4 py-3 font-bold text-slate-900">Rp 3.575.000</td>
                        </tr>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-semibold text-slate-900">S1 Sistem Informasi</td>
                            <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                            <td class="px-4 py-3">Reguler Pagi</td>
                            <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                            <td class="px-4 py-3 font-bold text-slate-900">Rp 3.200.000</td>
                        </tr>
                        <tr class="hover:bg-slate-50 transition bg-amber-50/20">
                            <td class="px-4 py-3 font-semibold text-slate-900">S1 Sistem Informasi (Kelas Sore/Karyawan)</td>
                            <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                            <td class="px-4 py-3">Kelas Sore</td>
                            <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                            <td class="px-4 py-3 font-bold text-slate-900">Rp 3.350.000</td>
                        </tr>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-semibold text-slate-900">S1 Desain Komunikasi Visual (DKV)</td>
                            <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                            <td class="px-4 py-3">Reguler Pagi</td>
                            <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                            <td class="px-4 py-3 font-bold text-slate-900">Rp 3.400.000</td>
                        </tr>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-semibold text-slate-900">S1 Bisnis Digital</td>
                            <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                            <td class="px-4 py-3">Reguler / Sore</td>
                            <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                            <td class="px-4 py-3 font-bold text-slate-900">Rp 3.250.000</td>
                        </tr>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-semibold text-slate-900">S1 Manajemen Bisnis</td>
                            <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                            <td class="px-4 py-3">Reguler Pagi / Sore</td>
                            <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                            <td class="px-4 py-3 font-bold text-slate-900">Rp 3.000.000</td>
                        </tr>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-semibold text-slate-900">S1 Akuntansi</td>
                            <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                            <td class="px-4 py-3">Reguler Pagi / Sore</td>
                            <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                            <td class="px-4 py-3 font-bold text-slate-900">Rp 3.000.000</td>
                        </tr>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-semibold text-slate-900">S1 Pendidikan Kepelatihan Olahraga (PKOR)</td>
                            <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                            <td class="px-4 py-3">Reguler Pagi</td>
                            <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                            <td class="px-4 py-3 font-bold text-slate-900">Rp 2.850.000</td>
                        </tr>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-semibold text-slate-900">S1 Pendidikan Matematika</td>
                            <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                            <td class="px-4 py-3">Reguler Pagi</td>
                            <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                            <td class="px-4 py-3 font-bold text-slate-900">Rp 2.850.000</td>
                        </tr>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-semibold text-slate-900">D3 Manajemen Bisnis</td>
                            <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 font-bold">D3</span></td>
                            <td class="px-4 py-3">Reguler Pagi</td>
                            <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                            <td class="px-4 py-3 font-bold text-slate-900">Rp 2.500.000</td>
                        </tr>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-semibold text-slate-900">S2 Magister Manajemen (Tanpa Tesis)</td>
                            <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded bg-purple-50 text-purple-700 font-bold">S2</span></td>
                            <td class="px-4 py-3">Eksekutif / Weekend</td>
                            <td class="px-4 py-3 font-semibold text-emerald-700">Rp 500.000</td>
                            <td class="px-4 py-3 font-bold text-slate-900">Rp 6.000.000</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Info Card: Pembayaran Biaya Pendaftaran -->
        <div class="p-5 rounded-2xl bg-blue-50 border border-blue-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h4 class="text-sm font-bold text-blue-900">Lanjutkan Pembayaran Biaya Formulir (Rp 300.000)</h4>
                <p class="text-xs text-blue-700 mt-0.5">Biaya formulir berlaku untuk seluruh pilihan prodi dan diproses aman via Midtrans Snap.</p>
            </div>
            @if($isPaid)
                <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white">
                    ✓ Sudah Lunas
                </span>
            @else
                <a href="{{ route('pmb.akun.bayar') }}"
                   class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-xs font-bold bg-[#0b3d82] hover:bg-[#082a5c] text-white shadow-xs transition shrink-0">
                    Bayar Sekarang &rarr;
                </a>
            @endif
        </div>
    </div>

</div>
@endsection
