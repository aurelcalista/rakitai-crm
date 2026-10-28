@extends('pmb.portal.layout')

@section('title', 'Upload Dokumen Persyaratan - Portal PMB UCIC')

@section('content')
<div class="max-w-4xl mx-auto space-y-8 pb-16">

    <!-- =================================================================== -->
    <!-- 1. HEADER: UPLOAD DOKUMEN PERSYARATAN (Matching Screenshot 1)        -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200/90 shadow-sm text-center">
        <!-- Cloud Upload Icon matching Screenshot 1 -->
        <div class="w-14 h-14 mx-auto rounded-2xl bg-blue-50 text-[#0b3d82] flex items-center justify-center mb-4 shadow-2xs">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
        </div>

        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0b3d82] font-display tracking-tight">
            Upload Dokumen Persyaratan
        </h1>
        <p class="text-xs sm:text-sm text-slate-600 mt-2 max-w-xl mx-auto leading-relaxed">
            Lengkapi semua dokumen yang diperlukan untuk melanjutkan proses pendaftaran
        </p>

        <!-- Identitas Akun Pendaftar -->
        <div class="mt-4 flex flex-wrap items-center justify-center gap-2 text-xs">
            <span class="text-slate-500 font-medium">Calon Mahasiswa:</span>
            <strong class="text-slate-900 font-bold">{{ $prospek->name ?? $user->name }}</strong>
            @if($prospek?->no_registrasi)
                <span class="text-slate-300">|</span>
                <span class="font-mono text-slate-700 bg-slate-100 px-2.5 py-0.5 rounded-full font-semibold">
                    No. Reg: {{ $prospek->no_registrasi }}
                </span>
            @endif
        </div>

        <div class="w-32 h-1 bg-[#0b3d82] mx-auto rounded-full mt-5"></div>
    </div>

    <!-- Alert Notifikasi Sukses -->
    @if(session('success'))
        <div class="p-4 sm:p-5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center justify-between text-xs sm:text-sm shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
            <a href="{{ route('pmb.akun.dashboard') }}" class="font-bold text-emerald-800 hover:underline shrink-0 ml-3">
                Ke Dashboard &rarr;
            </a>
        </div>
    @endif

    <!-- Alert Notifikasi Error -->
    @if(session('error') || $errors->any())
        <div class="p-4 sm:p-5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs shadow-xs">
            <div class="flex items-center gap-2 font-bold mb-1.5 text-sm text-rose-800">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>{{ session('error') ?? 'Gagal mengunggah dokumen persyaratan:' }}</span>
            </div>
            @if($errors->any())
                <ul class="list-disc list-inside space-y-0.5 text-rose-700 ml-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <!-- =================================================================== -->
    <!-- 2. CARD: PROGRESS UPLOAD DOKUMEN (Matching Screenshot 1)            -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-sm text-center space-y-4">
        <div class="flex items-center justify-center gap-2 text-base font-bold text-slate-800 font-display">
            <svg class="w-5 h-5 text-[#0b3d82]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span>Progress Upload Dokumen</span>
        </div>

        <!-- Progress Bar matching Screenshot 1 -->
        <div class="w-full max-w-2xl mx-auto bg-slate-100 rounded-full h-3.5 overflow-hidden border border-slate-200/80">
            <div class="bg-[#0b3d82] h-full rounded-full transition-all duration-500"
                 style="width: {{ $progressPercentage }}%;"></div>
        </div>

        <p class="text-xs sm:text-sm text-slate-600 font-medium">
            {{ $uploadedCount }} dari {{ $totalDokumen }} dokumen telah berhasil diupload ({{ $progressPercentage }}%)
        </p>
    </div>

    <!-- =================================================================== -->
    <!-- 3. CARD: INFORMASI PENTING (Matching Screenshot 1)                  -->
    <!-- =================================================================== -->
    <div class="bg-[#e0f2fe] rounded-3xl p-6 sm:p-8 border border-[#bae6fd] shadow-2xs space-y-3.5">
        <div class="flex items-center gap-2.5 text-[#0b3d82] font-bold text-base font-display">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Informasi Penting</span>
        </div>

        <ul class="space-y-2 text-xs sm:text-sm text-slate-700">
            <li class="flex items-start gap-2.5">
                <svg class="w-4 h-4 text-[#0b3d82] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Semua dokumen wajib diunggah sebelum melanjutkan ke tahap seleksi berikutnya</span>
            </li>
            <li class="flex items-start gap-2.5">
                <svg class="w-4 h-4 text-[#0b3d82] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Format file yang diperbolehkan: <strong class="text-slate-900">JPG/PNG</strong> untuk foto, <strong class="text-slate-900">PDF</strong> untuk berkas dokumen</span>
            </li>
            <li class="flex items-start gap-2.5">
                <svg class="w-4 h-4 text-[#0b3d82] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Ukuran maksimal setiap file: <strong class="text-slate-900">2MB per file</strong></span>
            </li>
            <li class="flex items-start gap-2.5">
                <svg class="w-4 h-4 text-[#0b3d82] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Pastikan dokumen hasil scan jelas, tidak buram, dan mudah dibaca oleh panitia verifikator</span>
            </li>
            <li class="flex items-start gap-2.5">
                <svg class="w-4 h-4 text-[#0b3d82] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Ikuti format penamaan file yang telah ditentukan pada masing-masing persyaratan</span>
            </li>
        </ul>
    </div>

    <!-- =================================================================== -->
    <!-- 4. DAFTAR KARTU DOKUMEN (Matching Screenshots 2, 3, 4, 5)           -->
    <!-- =================================================================== -->
    <div class="space-y-6">
        @foreach($dokumenList as $key => $doc)
            @php
                $isUploaded = !empty($berkasData[$key]['file_path']);
                $docItem = $berkasData[$key] ?? null;
                $docStatus = $docItem['status'] ?? 'Belum Diunggah';
            @endphp

            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-sm space-y-5 transition hover:border-slate-300"
                 x-data="{
                     fileName: '',
                     fileSelected: false,
                     isChanging: false,
                     triggerFile() {
                         this.$refs.fileInput.click();
                     },
                     handleFileChange(e) {
                         if (e.target.files && e.target.files.length > 0) {
                             this.fileName = e.target.files[0].name;
                             this.fileSelected = true;
                         }
                     }
                 }">

                <!-- Header Kartu Dokumen: Icon & Nama Dokumen -->
                <div class="text-center space-y-2">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-50 border border-slate-200/90 flex items-center justify-center text-slate-700 shadow-2xs">
                        @if($doc['icon'] === 'user')
                            <!-- Pas Foto Icon matching Screenshot 3 -->
                            <svg class="w-7 h-7 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        @elseif($doc['icon'] === 'id-card')
                            <!-- KTP Icon matching Screenshot 2 -->
                            <svg class="w-7 h-7 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        @elseif($doc['icon'] === 'users')
                            <!-- KK Icon matching Screenshot 4 -->
                            <svg class="w-7 h-7 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        @elseif($doc['icon'] === 'document')
                            <!-- Akta Kelahiran Icon matching Screenshot 5 -->
                            <svg class="w-7 h-7 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        @elseif($doc['icon'] === 'academic')
                            <!-- Ijazah / SKL Icon -->
                            <svg class="w-7 h-7 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M12 14l9-5-9-5-9 5 9 5z"/>
                                <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-5.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                            </svg>
                        @elseif($doc['icon'] === 'clipboard-list')
                            <!-- Transkrip Nilai Icon -->
                            <svg class="w-7 h-7 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        @else
                            <!-- Surat Sehat / Pendukung Icon -->
                            <svg class="w-7 h-7 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        @endif
                    </div>

                    <h3 class="text-base sm:text-lg font-bold text-slate-900 font-display">
                        {{ $doc['title'] }}
                    </h3>
                    <p class="text-xs text-slate-500">
                        {{ $doc['subtitle'] }}
                    </p>

                    <!-- Status Dokumen Badge -->
                    <div class="pt-1">
                        @if(!$isUploaded)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                Belum Diunggah
                            </span>
                        @elseif($docStatus === 'Disetujui')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Disetujui
                            </span>
                        @elseif($docStatus === 'Ditolak')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                Ditolak
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Menunggu Verifikasi
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Informasi Penolakan Dokumen jika ditolak -->
                @if($docStatus === 'Ditolak' && !empty($docItem['catatan']))
                    <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-left text-xs text-rose-800">
                        <strong class="font-bold block mb-0.5">Alasan Penolakan:</strong>
                        <span>{{ $docItem['catatan'] }}</span>
                        <p class="mt-1 text-[11px] text-rose-600 font-medium">Silakan unggah kembali dokumen pengganti yang telah disesuaikan.</p>
                    </div>
                @endif

                <!-- Persyaratan Box matching Screenshots 2, 3, 4, 5 -->
                <div class="bg-slate-50/80 rounded-2xl p-4 sm:p-5 border border-slate-100 text-left space-y-1.5 text-xs text-slate-700">
                    <h4 class="font-bold text-[#0b3d82] text-xs">Persyaratan:</h4>
                    <ul class="space-y-1 pl-1">
                        @foreach($doc['requirements'] as $req)
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 shrink-0"></span>
                                <span>{{ $req }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Tampilan Dokumen yang Sudah Diunggah -->
                @if($isUploaded)
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/90 text-left flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 text-[#0b3d82] flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="truncate">
                                <span class="font-bold text-slate-900 block truncate" title="{{ $docItem['original_name'] ?? $docItem['file_name'] }}">
                                    {{ $docItem['original_name'] ?? $docItem['file_name'] }}
                                </span>
                                <span class="text-[11px] text-slate-500 block mt-0.5">
                                    Diunggah: {{ \Carbon\Carbon::parse($docItem['uploaded_at'])->format('d M Y, H:i') }} | {{ number_format(($docItem['file_size'] ?? 0) / 1024, 0) }} KB
                                </span>
                            </div>
                        </div>

                        <!-- Tombol Aksi Dokumen Terunggah -->
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('pmb.akun.dokumen.view', $key) }}"
                               target="_blank"
                               class="px-3 py-2 rounded-xl text-xs font-bold bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 transition flex items-center gap-1.5 shadow-2xs">
                                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <span>Lihat</span>
                            </a>

                            <a href="{{ route('pmb.akun.dokumen.view', [$key, 'download' => 1]) }}"
                               class="px-3 py-2 rounded-xl text-xs font-bold bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 transition flex items-center gap-1.5 shadow-2xs"
                               title="Unduh Dokumen">
                                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </a>

                            @if($docStatus !== 'Disetujui')
                                <button type="button"
                                        @click="isChanging = !isChanging"
                                        class="px-3 py-2 rounded-xl text-xs font-bold bg-blue-50 border border-blue-200 hover:bg-blue-100 text-[#0b3d82] transition flex items-center gap-1">
                                    <span x-text="isChanging ? 'Batal' : 'Ganti'"></span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Form Upload / Re-upload Dokumen -->
                <div x-show="!{{ $isUploaded ? 'true' : 'false' }} || isChanging" x-cloak>
                    <form action="{{ route('pmb.akun.upload-dokumen.store') }}"
                          method="POST"
                          enctype="multipart/form-data"
                          class="space-y-3">
                        @csrf
                        <input type="hidden" name="dokumen_type" value="{{ $key }}">
                        <input type="file"
                               name="dokumen_file"
                               x-ref="fileInput"
                               @change="handleFileChange($event)"
                               accept="{{ $doc['mimes'] === 'jpg,jpeg,png' ? '.jpg,.jpeg,.png,image/jpeg,image/png' : '.jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf' }}"
                               class="hidden">

                        <!-- Tombol Pilih File matching Screenshot 2, 3, 4, 5 -->
                        <div x-show="!fileSelected">
                            <button type="button"
                                    @click="triggerFile()"
                                    class="w-full py-3.5 px-6 rounded-2xl font-bold text-sm bg-[#0b3d82] hover:bg-[#072b5c] text-white flex items-center justify-center gap-2.5 shadow-sm transition active:scale-[0.99] cursor-pointer">
                                <svg class="w-5 h-5 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <span>{{ $doc['button_text'] }}</span>
                            </button>
                        </div>

                        <!-- Tampilan setelah file dipilih: Konfirmasi Upload -->
                        <div x-show="fileSelected" class="space-y-3 bg-blue-50/60 p-4 rounded-2xl border border-blue-200 text-left">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-600">File dipilih:</span>
                                <button type="button" @click="fileSelected = false; fileName = ''; $refs.fileInput.value = ''" class="text-rose-600 hover:underline font-bold">
                                    Batalkan
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-[#0b3d82] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="font-bold text-slate-900 text-xs truncate" x-text="fileName"></span>
                            </div>
                            <button type="submit"
                                    class="w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-emerald-600 hover:bg-emerald-700 text-white flex items-center justify-center gap-2 transition shadow-xs cursor-pointer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Unggah Sekarang</span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        @endforeach
    </div>

    <!-- =================================================================== -->
    <!-- 5. NAVIGASI BAWAH: KEMBALI KE FORM / DASHBOARD                      -->
    <!-- =================================================================== -->
    <div class="pt-6 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
        <a href="{{ route('pmb.akun.dashboard') }}"
           class="w-full sm:w-auto px-6 py-3 rounded-2xl font-bold text-xs sm:text-sm text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition text-center shadow-2xs">
            &larr; Kembali ke Dashboard
        </a>

        <div class="flex items-center gap-3 w-full sm:w-auto">
            <a href="{{ route('pmb.akun.profil') }}"
               class="w-full sm:w-auto px-6 py-3 rounded-2xl font-bold text-xs sm:text-sm text-[#0b3d82] bg-blue-50 border border-blue-200 hover:bg-blue-100 transition text-center">
                Lihat Form Pendaftaran &rarr;
            </a>

            <a href="{{ route('pmb.akun.status') }}"
               class="w-full sm:w-auto px-6 py-3 rounded-2xl font-bold text-xs sm:text-sm text-white bg-[#0b3d82] hover:bg-[#072b5c] transition text-center shadow-xs">
                Cek Status Kelulusan
            </a>
        </div>
    </div>

</div>
@endsection
