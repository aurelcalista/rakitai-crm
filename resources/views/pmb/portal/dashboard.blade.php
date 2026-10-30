@extends('pmb.portal.layout')

@section('title', 'Dashboard - Portal PMB UCIC')

@section('content')
<div class="space-y-6">

    @php
        $isPaid = ($transaksiAktif?->payment_status ?? '') === \App\Models\Transaksi::STATUS_VERIFIED;
        $isFormFilled = !empty($prospek->prodi_id) || ($prospek->status_formulir ?? '') === 'Sudah Isi';
        $isUjianDone = ($prospek->status_ujian ?? '') === 'Sudah Mengerjakan';
        $berkasData = is_array($prospek?->berkas_data) ? $prospek->berkas_data : [];
        $uploadedDocsCount = count(array_filter($berkasData, fn($d) => !empty($d['file_path'])));
        $isBerkasUploaded = ($prospek?->status_berkas ?? '') === 'Sudah Upload' || $uploadedDocsCount >= 7;
    @endphp

    <!-- Card 1: Banner Sambutan (Matching Screenshot 1) -->
    <div class="bg-white rounded-3xl p-8 sm:p-10 border border-slate-200/90 shadow-sm text-center">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0b3d82] font-display">
            Selamat Datang di Portal PMB UCIC
        </h1>
        <p class="text-slate-600 text-sm mt-3 max-w-2xl mx-auto leading-relaxed">
            Halo <strong class="text-slate-900 font-bold">{{ $prospek->name ?? $user->name }}</strong>,
            pendaftaran Anda sudah berhasil disubmit! 📝 Silakan lanjutkan ke tahap pembayaran untuk menyelesaikan proses pendaftaran.
        </p>
    </div>

    <!-- Card 2: Gelombang Info Box with Green PMB BUKA Badge (Matching Screenshot 1) -->
    <div class="bg-white rounded-2xl p-6 sm:p-7 border-l-4 border-l-[#0b3d82] border-y border-r border-slate-200/90 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-base sm:text-lg font-bold text-[#0b3d82] font-display">
                {{ $prospek->gelombang ?? 'Gelombang 1 Beasiswa Rektor' }}
            </h2>
            <div class="text-xs text-slate-500 mt-1 space-y-0.5">
                <p>Tahun Akademik 2027/2028</p>
                <p>Periode: 01 Sep 2026 - 30 Oct 2026</p>
            </div>
        </div>

        <div class="flex flex-col sm:items-end gap-1">
            <span class="inline-flex items-center justify-center px-4 py-1.5 rounded-lg text-xs font-bold bg-[#15803d] text-white tracking-wide shadow-xs">
                PMB BUKA
            </span>
            <span class="text-[11px] text-[#ca8a04] font-semibold">
                7 hari tersisa
            </span>
        </div>
    </div>

    <!-- Card 3: 4 Action Grid Cards with Big Circular Icons (Matching Screenshot 1) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- 1. Form Pendaftaran -->
        <a href="{{ route('pmb.akun.profil') }}"
           class="group bg-white rounded-3xl p-7 border border-slate-200/90 shadow-sm hover:shadow-md transition text-center flex flex-col items-center justify-between">
            <div class="w-18 h-18 rounded-full bg-[#16a34a] text-white flex items-center justify-center mb-5 shadow-xs group-hover:scale-105 transition">
                <svg class="w-9 h-9" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.8" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900 group-hover:text-[#0b3d82] transition font-display">
                    Form Pendaftaran
                </h3>
                <span class="text-xs text-slate-500 mt-1 block">
                    {{ $isFormFilled ? 'Sudah Diselesaikan' : 'Lengkapi Sekarang' }}
                </span>
            </div>
        </a>

        <!-- 2. Tes Potensi Akademik -->
        <a href="{{ route('pmb.akun.ujian') }}"
           class="group bg-white rounded-3xl p-7 border border-slate-200/90 shadow-sm hover:shadow-md transition text-center flex flex-col items-center justify-between cursor-pointer">
            <div class="w-18 h-18 rounded-full {{ $isUjianDone ? 'bg-[#16a34a]' : 'bg-[#0b3d82]' }} text-white flex items-center justify-center mb-5 shadow-xs group-hover:scale-105 transition">
                <svg class="w-9 h-9" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900 group-hover:text-[#0b3d82] transition font-display">
                    Tes Potensi Akademik
                </h3>
                <span class="text-xs text-slate-500 mt-1 block">
                    {{ $isUjianDone ? 'Ujian Telah Selesai' : 'Silahkan Mengikuti Ujian' }}
                </span>
            </div>
        </a>

        <!-- 3. Pembayaran -->
        <a href="{{ route('pmb.akun.bayar') }}"
           class="group bg-white rounded-3xl p-7 border border-slate-200/90 shadow-sm hover:shadow-md transition text-center flex flex-col items-center justify-between">
            <div class="w-18 h-18 rounded-full {{ $isPaid ? 'bg-[#16a34a]' : 'bg-[#0b3d82]' }} text-white flex items-center justify-center mb-5 shadow-xs group-hover:scale-105 transition">
                <svg class="w-9 h-9" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900 group-hover:text-[#0b3d82] transition font-display">
                    Pembayaran
                </h3>
                <span class="text-xs text-slate-500 mt-1 block">
                    {{ $isPaid ? 'Lunas Terverifikasi' : 'Silahkan Lakukan Pembayaran' }}
                </span>
            </div>
        </a>

        <!-- 4. Upload Dokumen -->
        <a href="{{ route('pmb.akun.upload-dokumen') }}"
           class="group bg-white rounded-3xl p-7 border border-slate-200/90 shadow-sm hover:shadow-md transition text-center flex flex-col items-center justify-between cursor-pointer">
            <div class="w-18 h-18 rounded-full {{ $isBerkasUploaded ? 'bg-[#16a34a]' : 'bg-[#0b3d82]' }} text-white flex items-center justify-center mb-5 shadow-xs group-hover:scale-105 transition">
                <svg class="w-9 h-9" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900 group-hover:text-[#0b3d82] transition font-display">
                    Upload Dokumen
                </h3>
                <span class="text-xs text-slate-500 mt-1 block">
                    {{ $isBerkasUploaded ? 'Sudah Diupload' : ($uploadedDocsCount > 0 ? "{$uploadedDocsCount}/7 Diupload" : 'Silahkan Upload Dokumen') }}
                </span>
            </div>
        </a>
    </div>

    <!-- Section: Status Progress Pendaftaran (Matching Screenshot 2) -->
    <div class="mt-8 space-y-4">
        <h2 class="text-lg font-bold text-slate-800 font-display flex items-center gap-2">
            <span>Status Progress Pendaftaran</span>
        </h2>

        <div class="space-y-3">
            <!-- Step 1: Registrasi Akun (Done) -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border-l-4 border-l-[#16a34a] border-y border-r border-slate-200/90 shadow-2xs flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-[#16a34a] text-white flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.8" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 font-display">Registrasi Akun</h4>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Akun berhasil dibuat pada {{ $user->created_at ? $user->created_at->format('d M Y') : '05 Oct 2026' }}
                        </p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-md text-xs font-bold bg-[#dcfce7] text-[#15803d]">
                    Selesai
                </span>
            </div>

            <!-- Step 2: Form Pendaftaran (Done) -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border-l-4 border-l-[#16a34a] border-y border-r border-slate-200/90 shadow-2xs flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-[#16a34a] text-white flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.8" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 font-display">Form Pendaftaran</h4>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Data pendaftaran berhasil disubmit pada {{ ($prospek?->created_at ?? now())->format('d M Y H:i') }}
                        </p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-md text-xs font-bold bg-[#dcfce7] text-[#15803d]">
                    Selesai
                </span>
            </div>

            <!-- Step 3: Tes Potensi Akademik (Berlangsung / Selesai) -->
            <a href="{{ route('pmb.akun.ujian') }}"
               class="bg-white rounded-2xl p-4 sm:p-5 border-l-4 {{ $isUjianDone ? 'border-l-[#16a34a]' : 'border-l-[#0b3d82]' }} border-y border-r border-slate-200/90 shadow-2xs flex items-center justify-between gap-4 hover:bg-slate-50 transition cursor-pointer">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full {{ $isUjianDone ? 'bg-[#16a34a]' : 'bg-[#0b3d82]' }} text-white flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 font-display">Tes Potensi Akademik</h4>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Silakan ikuti Tes Potensi Akademik (15 soal, 45 menit)
                        </p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-md text-xs font-bold {{ $isUjianDone ? 'bg-[#dcfce7] text-[#15803d]' : 'bg-[#dbeafe] text-[#1d4ed8]' }}">
                    {{ $isUjianDone ? 'Selesai' : 'Berlangsung' }}
                </span>
            </a>

            <!-- Step 4: Upload Dokumen (Berlangsung / Selesai) -->
            <a href="{{ route('pmb.akun.upload-dokumen') }}"
               class="bg-white rounded-2xl p-4 sm:p-5 border-l-4 {{ $isBerkasUploaded ? 'border-l-[#16a34a]' : 'border-l-[#0b3d82]' }} border-y border-r border-slate-200/90 shadow-2xs flex items-center justify-between gap-4 hover:bg-slate-50 transition cursor-pointer">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full {{ $isBerkasUploaded ? 'bg-[#16a34a]' : 'bg-[#0b3d82]' }} text-white flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 font-display">Upload Dokumen</h4>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Silakan upload dokumen pendukung yang diperlukan ({{ $uploadedDocsCount }}/7 diupload)
                        </p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-md text-xs font-bold {{ $isBerkasUploaded ? 'bg-[#dcfce7] text-[#15803d]' : 'bg-[#dbeafe] text-[#1d4ed8]' }}">
                    {{ $isBerkasUploaded ? 'Selesai' : 'Berlangsung' }}
                </span>
            </a>
        </div>
    </div>

    <!-- Quick Access Card: Katalog Program Studi -->
    <div class="bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-slate-50 rounded-2xl p-5 border border-blue-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-[#0b3d82] text-white flex items-center justify-center shrink-0 shadow-xs">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-bold text-slate-900 font-display">Katalog Program Studi & Brosur UCIC</h4>
                <p class="text-xs text-slate-600 mt-0.5">Jelajahi fakultas, jenjang pendidikan (S1, D3, S2), dan prospek karir setiap jurusan di UCIC.</p>
            </div>
        </div>
        <a href="{{ route('pmb.akun.katalog') }}"
           class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold bg-[#0b3d82] text-white hover:bg-[#072b5c] shadow-xs transition shrink-0">
            <span>Buka Katalog</span>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    <!-- Quick Payment Link Card if unpaid -->
    @if(!$isPaid)
        <div class="bg-amber-50 rounded-2xl p-5 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="text-2xl">💳</span>
                <div>
                    <h4 class="text-sm font-bold text-amber-900">Pembayaran Biaya Formulir PMB (Rp 300.000)</h4>
                    <p class="text-xs text-amber-700 mt-0.5">Selesaikan pembayaran secara aman via Midtrans Snap (QRIS atau Virtual Account).</p>
                </div>
            </div>
            <a href="{{ route('pmb.akun.bayar') }}"
               class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-bold bg-[#0b3d82] text-white hover:bg-[#082a5c] shadow-xs transition shrink-0">
                Bayar Sekarang &rarr;
            </a>
        </div>
    @endif

</div>
@endsection
