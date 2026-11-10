@extends('pmb.portal.layout')

@section('title', 'Form Pendaftaran Online - Portal PMB UCIC')

@php
    $formData = is_array($prospek?->form_data) ? $prospek->form_data : [];
    $schoolName = $prospek->sekolah?->nama ?? $formData['nama_sekolah'] ?? '';
    if (empty($schoolName) && !empty($prospek->notes) && preg_match('/Asal sekolah:\s*([^|]+)/', $prospek->notes, $m)) {
        $schoolName = trim($m[1]);
    }
    $isFormSubmitted = ($prospek?->status_formulir ?? '') === 'Sudah Isi';

    // JSON Map untuk live preview rincian prodi yang dipilih
    $prodiMap = $prodis->mapWithKeys(function ($p) {
        return [$p->id => [
            'id'                 => $p->id,
            'nama'               => $p->nama,
            'kode'               => $p->kode,
            'jenjang'            => $p->jenjang ?? 'S1',
            'biaya_pendaftaran'  => (int) ($p->biaya_pendaftaran ?? 300000),
            'biaya_perlengkapan' => (int) ($p->biaya_perlengkapan ?? 600000),
            'biaya_dpp'          => (int) ($p->biaya_dpp ?? 500000),
            'biaya_uang_gedung'  => (int) ($p->biaya_uang_gedung ?? 0),
        ]];
    });
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-16"
     x-data="{
         loading: false,
         selectedProdiId: '{{ old('prodi_id', $prospek->prodi_id ?? '') }}',
         prodiCatalog: {{ json_encode($prodiMap) }},
         get selectedProdi() {
             return this.prodiCatalog[this.selectedProdiId] || null;
         },
         formatRupiah(num) {
             num = Number(num) || 0;
             if (num === 0) return 'Rp 0 (Gratis)';
             return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
         }
     }">

    <!-- =================================================================== -->
    <!-- HEADER HERO FORM PENDAFTARAN                                        -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-xs relative overflow-hidden">
        <!-- Background Accent Glow -->
        <div class="absolute -top-16 -right-16 w-48 h-48 bg-blue-100/50 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-[#0b3d82] text-white flex items-center justify-center shadow-md shadow-blue-900/20 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <div class="inline-flex items-center gap-1.5 text-[11px] font-bold text-[#0b3d82] uppercase tracking-wider mb-0.5">
                        <span>Penerimaan Mahasiswa Baru</span>
                        <span>•</span>
                        <span>Tahun Akademik {{ date('Y') }}/{{ date('Y') + 1 }}</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-display">
                        Form Pendaftaran Online UCIC
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                @if($isFormSubmitted)
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Formulir Terisi Lengkap</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span>Wajib Diisi Sebelum Bayar</span>
                    </span>
                @endif

                @if($prospek?->no_registrasi)
                    <span class="font-mono text-xs text-slate-700 bg-slate-100 px-3 py-1.5 rounded-xl font-bold border border-slate-200 shadow-2xs">
                        {{ $prospek->no_registrasi }}
                    </span>
                @endif
            </div>
        </div>

        <p class="text-xs sm:text-sm text-slate-600 mt-3 leading-relaxed">
            Silakan lengkapi formulir pendaftaran di bawah ini dengan data yang valid dan benar. Program studi yang Anda pilih akan menentukan rincian komponen biaya perkuliahan serta skema pembayaran Anda.
        </p>

        <!-- Stepper Visual Progress -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 mt-5 pt-4 border-t border-slate-100">
            <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-blue-50/70 border border-blue-200/80">
                <div class="w-6 h-6 rounded-lg bg-[#0b3d82] text-white text-xs font-bold flex items-center justify-center shrink-0">1</div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-[#0b3d82] truncate">Langkah 1</p>
                    <p class="text-[10px] text-slate-600 truncate">Isi Form & Pilih Prodi</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 p-2.5 rounded-xl {{ $isFormSubmitted ? 'bg-emerald-50/70 border border-emerald-200/80' : 'bg-slate-50 border border-slate-200/60' }}">
                <div class="w-6 h-6 rounded-lg {{ $isFormSubmitted ? 'bg-emerald-600 text-white' : 'bg-slate-300 text-slate-600' }} text-xs font-bold flex items-center justify-center shrink-0">2</div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold {{ $isFormSubmitted ? 'text-emerald-700' : 'text-slate-700' }} truncate">Langkah 2</p>
                    <p class="text-[10px] text-slate-500 truncate">Pembayaran PMB</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200/60">
                <div class="w-6 h-6 rounded-lg bg-slate-300 text-slate-600 text-xs font-bold flex items-center justify-center shrink-0">3</div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-slate-700 truncate">Langkah 3</p>
                    <p class="text-[10px] text-slate-500 truncate">Ujian Seleksi & Berkas</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @if(session('success'))
        <div class="p-4 sm:p-5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center justify-between text-xs sm:text-sm shadow-xs animate-fade-in">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <span class="font-bold">{{ session('success') }}</span>
            </div>
            <a href="{{ route('pmb.akun.bayar') }}" class="inline-flex items-center gap-1 font-bold text-emerald-800 hover:text-emerald-950 px-3 py-1.5 rounded-lg bg-emerald-100 hover:bg-emerald-200 transition shrink-0 ml-3 text-xs">
                <span>Lanjut Pembayaran &rarr;</span>
            </a>
        </div>
    @endif

    @if(session('warning'))
        <div class="p-4 sm:p-5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-center gap-3 text-xs sm:text-sm shadow-xs animate-fade-in">
            <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <span class="font-bold">{{ session('warning') }}</span>
        </div>
    @endif

    @if(session('info'))
        <div class="p-4 sm:p-5 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900 flex items-center gap-3 text-xs sm:text-sm shadow-xs animate-fade-in">
            <div class="w-8 h-8 rounded-xl bg-[#0b3d82] text-white flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="font-bold">{{ session('info') }}</span>
        </div>
    @endif

    <!-- Alert Validasi Gagal -->
    @if(isset($errors) && $errors->any())
        <div class="p-4 sm:p-5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs shadow-xs animate-fade-in">
            <div class="flex items-center gap-2 font-bold mb-2 text-sm text-rose-800">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Terdapat {{ $errors->count() }} kolom yang perlu diperbaiki:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 text-rose-700 pl-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- =================================================================== -->
    <!-- FORMULIR PENDAFTARAN UTAMA                                          -->
    <!-- =================================================================== -->
    <form action="{{ route('pmb.akun.profil.update') }}" method="POST" @submit="loading = true" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- =============================================================== -->
        <!-- BAGIAN 1: PILIHAN PROGRAM STUDI & PERKULIAHAN (UTAMA!)          -->
        <!-- =============================================================== -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs p-6 sm:p-8 space-y-6">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0b3d82] flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M12 14l9-5-9-5-9 5 9 5z"/>
                        <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-blue-100 text-[#0b3d82]">Langkah Utama</span>
                        <h2 class="text-base sm:text-lg font-black text-slate-900 font-display">1. Pilihan Program Studi & Perkuliahan</h2>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Pilih program studi yang Anda tuju. Biaya kuliah akan menyesuaikan pilihan ini.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Program Studi Pilihan 1 (Utama - Wajib) -->
                <div>
                    <label for="prodi_id" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Program Studi Pilihan 1 (Utama) <span class="text-rose-500">*</span>
                    </label>
                    <select id="prodi_id"
                            name="prodi_id"
                            x-model="selectedProdiId"
                            required
                            class="w-full px-4 py-2.5 rounded-xl border {{ (isset($errors) && $errors->has('prodi_id')) ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-white' }} text-sm font-semibold text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                        <option value="">-- Pilih Program Studi Utama --</option>
                        @foreach($prodis as $p)
                            <option value="{{ $p->id }}" {{ (old('prodi_id', $prospek->prodi_id ?? '') == $p->id) ? 'selected' : '' }}>
                                {{ $p->jenjang ?? 'S1' }} {{ $p->nama }} ({{ $p->kode }})
                            </option>
                        @endforeach
                    </select>
                    @error('prodi_id')
                        <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Program Studi Pilihan 2 (Alternatif - Opsional) -->
                <div>
                    <label for="prodi_pilihan_2_id" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Program Studi Pilihan 2 (Alternatif)
                    </label>
                    <select id="prodi_pilihan_2_id"
                            name="prodi_pilihan_2_id"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                        <option value="">-- Pilih Alternatif (Opsional) --</option>
                        @foreach($prodis as $p)
                            <option value="{{ $p->id }}" {{ (old('prodi_pilihan_2_id', $prospek->prodi_pilihan_2_id ?? '') == $p->id) ? 'selected' : '' }}>
                                {{ $p->jenjang ?? 'S1' }} {{ $p->nama }} ({{ $p->kode }})
                            </option>
                        @endforeach
                    </select>
                    @error('prodi_pilihan_2_id')
                        <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Tipe Kelas Kuliah -->
                <div>
                    <label for="tipe_kelas" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Tipe Kelas Kuliah <span class="text-rose-500">*</span>
                    </label>
                    <select id="tipe_kelas"
                            name="tipe_kelas"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                        <option value="Reguler" {{ old('tipe_kelas', $prospek->kelas ?? $formData['tipe_kelas'] ?? 'Reguler') === 'Reguler' ? 'selected' : '' }}>Kelas Reguler (Pagi/Siang)</option>
                        <option value="Karyawan" {{ old('tipe_kelas', $prospek->kelas ?? $formData['tipe_kelas'] ?? '') === 'Karyawan' ? 'selected' : '' }}>Kelas Karyawan (Sore/Malam & Weekend)</option>
                        <option value="Eksekutif" {{ old('tipe_kelas', $prospek->kelas ?? $formData['tipe_kelas'] ?? '') === 'Eksekutif' ? 'selected' : '' }}>Kelas Eksekutif / Hybrid</option>
                    </select>
                </div>

                <!-- Sumber Informasi Pendaftaran -->
                <div>
                    <label for="informasi_pendaftaran" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Sumber Informasi Pendaftaran
                    </label>
                    <select id="informasi_pendaftaran"
                            name="informasi_pendaftaran"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                        <option value="">-- Darimana Anda Mengetahui UCIC? --</option>
                        @foreach(['Instagram / Media Sosial', 'Teman / Keluarga', 'Guru BK / Sekolah Asal', 'Kunjungan Tim Kampus', 'Website / Google', 'Pameran Pendidikan / Expo', 'Brosur / Spanduk', 'Lainnya'] as $info)
                            <option value="{{ $info }}" {{ old('informasi_pendaftaran', $prospek->source ?? $formData['informasi_pendaftaran'] ?? '') === $info ? 'selected' : '' }}>{{ $info }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Kode Referral -->
                <div class="sm:col-span-2">
                    <label for="kode_referal" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Kode Referral / Rekomendasi (Opsional)
                    </label>
                    <input type="text"
                           id="kode_referal"
                           name="kode_referal"
                           value="{{ old('kode_referal', $formData['kode_referal'] ?? '') }}"
                           placeholder="Contoh: CIC-REF-123 atau Nama Guru / Rekan"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400 shadow-2xs">
                </div>
            </div>

            <!-- Live Card Info Rincian Biaya Prodi yang Dipilih -->
            <template x-if="selectedProdi">
                <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-blue-50/80 via-white to-sky-50/60 border border-blue-200 shadow-2xs transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-xs font-extrabold text-slate-900 font-display">Estimasi Komponen Biaya untuk Prodi Terpilih:</span>
                        </div>
                        <span class="text-xs font-black text-[#0b3d82] bg-blue-100/70 px-2.5 py-0.5 rounded-full" x-text="selectedProdi.jenjang + ' ' + selectedProdi.nama"></span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                        <div class="p-2.5 rounded-xl bg-white border border-slate-200/80">
                            <span class="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Pendaftaran</span>
                            <span class="text-xs font-extrabold text-slate-900" x-text="formatRupiah(selectedProdi.biaya_pendaftaran)"></span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-white border border-slate-200/80">
                            <span class="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Perlengkapan</span>
                            <span class="text-xs font-extrabold text-slate-900" x-text="formatRupiah(selectedProdi.biaya_perlengkapan)"></span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-white border border-slate-200/80">
                            <span class="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">DPP</span>
                            <span class="text-xs font-extrabold text-slate-900" x-text="formatRupiah(selectedProdi.biaya_dpp)"></span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-white border border-slate-200/80">
                            <span class="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Uang Gedung</span>
                            <span class="text-xs font-extrabold text-slate-900" x-text="formatRupiah(selectedProdi.biaya_uang_gedung)"></span>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Checkbox Minat Program Tambahan -->
            <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 space-y-2.5 text-xs">
                <label class="flex items-start gap-3 cursor-pointer text-slate-700 select-none">
                    <input type="checkbox"
                           name="minat_kuliah"
                           value="ya"
                           {{ old('minat_kuliah', $prospek->minat_kuliah ?? 'ya') === 'ya' ? 'checked' : '' }}
                           class="w-4 h-4 mt-0.5 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82]">
                    <span><strong>Berminat melanjutkan Kuliah</strong> di Universitas Catur Insan Cendekia (UCIC) Cirebon.</span>
                </label>

                <label class="flex items-start gap-3 cursor-pointer text-slate-700 select-none">
                    <input type="checkbox"
                           name="minat_miniclass"
                           value="ya"
                           {{ old('minat_miniclass', $prospek->minat_miniclass ?? 'tidak') === 'ya' ? 'checked' : '' }}
                           class="w-4 h-4 mt-0.5 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82]">
                    <span><strong>Berminat mengikuti MiniClass AI & Workshop</strong> (Pelatihan AI praktis dan sertifikasi gratis).</span>
                </label>
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- BAGIAN 2: DATA DIRI & IDENTITAS PRIBADI                          -->
        <!-- =============================================================== -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs p-6 sm:p-8 space-y-6">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0b3d82] flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-slate-900 font-display">2. Data Diri & Identitas Pribadi</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Identitas resmi calon mahasiswa sesuai kartu tanda penduduk / akta kelahiran.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Nama Lengkap -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Nama Lengkap Sesuai KTP/Ijazah <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $prospek->name ?? $user->name) }}"
                           required
                           placeholder="Contoh: Abdul Mughni Nugraha"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400 shadow-2xs">
                    @error('name')
                        <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- NIK -->
                <div>
                    <label for="nik" class="block text-xs font-bold text-slate-700 mb-1.5">
                        NIK (Nomor Induk Kependudukan) <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           id="nik"
                           name="nik"
                           maxlength="16"
                           value="{{ old('nik', $formData['nik'] ?? '') }}"
                           placeholder="16 digit angka KTP / KK"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400 shadow-2xs">
                    <span class="text-[11px] text-slate-400 mt-1 block">Wajib 16 digit untuk verifikasi PDDikti Kemdikbud</span>
                </div>

                <!-- Email (Terkunci) -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Email Terdaftar (Akun Login)
                    </label>
                    <div class="relative">
                        <input type="email"
                               id="email"
                               value="{{ $user->email }}"
                               disabled
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 text-sm cursor-not-allowed font-medium shadow-2xs pr-10">
                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Nomor WhatsApp -->
                <div>
                    <label for="whatsapp" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Nomor HP / WhatsApp Aktif <span class="text-rose-500">*</span>
                    </label>
                    <x-input-whatsapp
                        name="whatsapp"
                        id="whatsapp"
                        :value="old('whatsapp', $prospek->whatsapp ?? $user->phone)"
                        required
                        placeholder="81234567890"
                        class="bg-white border-slate-200"
                    />
                    @error('whatsapp')
                        <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Tempat Lahir -->
                <div>
                    <label for="tempat_lahir" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Tempat Lahir <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           id="tempat_lahir"
                           name="tempat_lahir"
                           value="{{ old('tempat_lahir', $formData['tempat_lahir'] ?? '') }}"
                           placeholder="Contoh: Cirebon"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400 shadow-2xs">
                </div>

                <!-- Tanggal Lahir -->
                <div>
                    <label for="tanggal_lahir" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Tanggal Lahir <span class="text-rose-500">*</span>
                    </label>
                    <input type="date"
                           id="tanggal_lahir"
                           name="tanggal_lahir"
                           value="{{ old('tanggal_lahir', $formData['tanggal_lahir'] ?? '') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                </div>

                <!-- Jenis Kelamin -->
                <div>
                    <label for="jenis_kelamin" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Jenis Kelamin <span class="text-rose-500">*</span>
                    </label>
                    <select id="jenis_kelamin"
                            name="jenis_kelamin"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="Laki-Laki" {{ old('jenis_kelamin', $formData['jenis_kelamin'] ?? '') === 'Laki-Laki' ? 'selected' : '' }}>Laki-Laki</option>
                        <option value="Perempuan" {{ old('jenis_kelamin', $formData['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>

                <!-- Agama -->
                <div>
                    <label for="agama" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Agama <span class="text-rose-500">*</span>
                    </label>
                    <select id="agama"
                            name="agama"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                        <option value="">-- Pilih Agama --</option>
                        @foreach(['Islam', 'Kristen Protestan', 'Kristen Katolik', 'Hindu', 'Buddha', 'Khonghucu'] as $ag)
                            <option value="{{ $ag }}" {{ old('agama', $formData['agama'] ?? '') === $ag ? 'selected' : '' }}>{{ $ag }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Tempat Tinggal -->
                <div>
                    <label for="status_tempat_tinggal" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Status Tempat Tinggal
                    </label>
                    <select id="status_tempat_tinggal"
                            name="status_tempat_tinggal"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                        <option value="">-- Pilih Status --</option>
                        @foreach(['Milik Sendiri', 'Milik Orangtua', 'Sewa / Kontrak', 'Kost', 'Asrama', 'Lainnya'] as $stt)
                            <option value="{{ $stt }}" {{ old('status_tempat_tinggal', $formData['status_tempat_tinggal'] ?? '') === $stt ? 'selected' : '' }}>{{ $stt }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Pekerjaan -->
                <div>
                    <label for="status_bekerja" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Status Pekerjaan
                    </label>
                    <select id="status_bekerja"
                            name="status_bekerja"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                        <option value="">-- Pilih Status Pekerjaan --</option>
                        @foreach(['Belum Bekerja', 'Bekerja Penuh Waktu', 'Bekerja Paruh Waktu', 'Wirausaha'] as $sb)
                            <option value="{{ $sb }}" {{ old('status_bekerja', $formData['status_bekerja'] ?? '') === $sb ? 'selected' : '' }}>{{ $sb }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- BAGIAN 3: ALAMAT DOMISILI & KONTAK                              -->
        <!-- =============================================================== -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs p-6 sm:p-8 space-y-6">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0b3d82] flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-slate-900 font-display">3. Alamat Domisili & Kontak</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Alamat lengkap tempat tinggal saat ini untuk korespondensi resmi.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Alamat Lengkap -->
                <div class="sm:col-span-2">
                    <label for="alamat" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Alamat Lengkap (Jalan, RT/RW, No. Rumah) <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="alamat"
                              name="alamat"
                              rows="2"
                              placeholder="Contoh: Jl. Kesambi No. 202, RT.02/RW.05"
                              class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400 shadow-2xs">{{ old('alamat', $formData['alamat'] ?? '') }}</textarea>
                </div>

                <!-- Kelurahan & Kecamatan -->
                <div>
                    <label for="kelurahan_kecamatan" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Kelurahan / Desa & Kecamatan
                    </label>
                    <input type="text"
                           id="kelurahan_kecamatan"
                           name="kelurahan_kecamatan"
                           value="{{ old('kelurahan_kecamatan', $formData['kelurahan_kecamatan'] ?? '') }}"
                           placeholder="Contoh: Sunyaragi, Kesambi"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400 shadow-2xs">
                </div>

                <!-- Kota & Provinsi -->
                <div>
                    <label for="kota_provinsi" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Kota / Kabupaten & Provinsi
                    </label>
                    <input type="text"
                           id="kota_provinsi"
                           name="kota_provinsi"
                           value="{{ old('kota_provinsi', $formData['kota_provinsi'] ?? '') }}"
                           placeholder="Contoh: Kota Cirebon, Jawa Barat"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400 shadow-2xs">
                </div>

                <!-- Kode Pos -->
                <div>
                    <label for="kode_pos" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Kode Pos
                    </label>
                    <input type="text"
                           id="kode_pos"
                           name="kode_pos"
                           value="{{ old('kode_pos', $formData['kode_pos'] ?? '') }}"
                           placeholder="Contoh: 45133"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400 shadow-2xs">
                </div>

                <!-- Telepon Rumah -->
                <div>
                    <label for="telp_rumah" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Telepon Rumah (Opsional)
                    </label>
                    <input type="text"
                           id="telp_rumah"
                           name="telp_rumah"
                           value="{{ old('telp_rumah', $formData['telp_rumah'] ?? '') }}"
                           placeholder="Contoh: (0231) 200418"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400 shadow-2xs">
                </div>
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- BAGIAN 4: RIWAYAT ASAL PENDIDIKAN                               -->
        <!-- =============================================================== -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs p-6 sm:p-8 space-y-6">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0b3d82] flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-slate-900 font-display">4. Riwayat Asal Pendidikan</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Informasi sekolah asal jenjang SMA/SMK/MA/sederajat.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Asal Sekolah -->
                <div>
                    <label for="asal_sekolah" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Nama Asal Sekolah <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           id="asal_sekolah"
                           name="asal_sekolah"
                           value="{{ old('asal_sekolah', $schoolName) }}"
                           placeholder="Contoh: SMKN 1 Cirebon"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400 shadow-2xs">
                    @error('asal_sekolah')
                        <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Jenis Sekolah -->
                <div>
                    <label for="jenis_sekolah" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Jenis Sekolah
                    </label>
                    <select id="jenis_sekolah"
                            name="jenis_sekolah"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                        <option value="">-- Pilih Jenis Sekolah --</option>
                        @foreach(['SMA', 'SMK', 'MA', 'Pondok Pesantren', 'Paket C', 'Lainnya'] as $js)
                            <option value="{{ $js }}" {{ old('jenis_sekolah', $formData['jenis_sekolah'] ?? '') === $js ? 'selected' : '' }}>{{ $js }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Jurusan Sekolah -->
                <div>
                    <label for="jurusan_sekolah" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Jurusan Sekolah
                    </label>
                    <select id="jurusan_sekolah"
                            name="jurusan_sekolah"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                        <option value="">-- Pilih Jurusan --</option>
                        @foreach(['IPA', 'IPS', 'Kejuruan (Teknologi/Komputer)', 'Kejuruan (Bisnis/Manajemen)', 'Bahasa', 'Agama', 'Umum', 'Lainnya'] as $jurs)
                            <option value="{{ $jurs }}" {{ old('jurusan_sekolah', $formData['jurusan_sekolah'] ?? '') === $jurs ? 'selected' : '' }}>{{ $jurs }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Keterangan Kejuruan / Peminatan -->
                <div>
                    <label for="keterangan_kejuruan" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Keterangan Peminatan / Jurusan Spesifik
                    </label>
                    <input type="text"
                           id="keterangan_kejuruan"
                           name="keterangan_kejuruan"
                           value="{{ old('keterangan_kejuruan', $formData['keterangan_kejuruan'] ?? '') }}"
                           placeholder="Contoh: Rekayasa Perangkat Lunak / TKJ"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400 shadow-2xs">
                </div>

                <!-- NISN -->
                <div>
                    <label for="nisn" class="block text-xs font-bold text-slate-700 mb-1.5">
                        NISN (Nomor Induk Siswa Nasional)
                    </label>
                    <input type="text"
                           id="nisn"
                           name="nisn"
                           maxlength="20"
                           value="{{ old('nisn', $formData['nisn'] ?? '') }}"
                           placeholder="10 digit NISN resmi"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400 shadow-2xs">
                </div>

                <!-- Tahun Lulus -->
                <div>
                    <label for="tahun_lulus" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Tahun Kelulusan
                    </label>
                    <input type="number"
                           id="tahun_lulus"
                           name="tahun_lulus"
                           min="2010"
                           max="{{ date('Y') + 1 }}"
                           value="{{ old('tahun_lulus', $formData['tahun_lulus'] ?? date('Y')) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                </div>
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- BAGIAN 5: DATA ORANG TUA / WALI                                 -->
        <!-- =============================================================== -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs p-6 sm:p-8 space-y-6">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0b3d82] flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-slate-900 font-display">5. Data Orang Tua / Wali</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Informasi kontak orang tua atau wali untuk administrasi akademik mahasiswa.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Nama Ibu Kandung -->
                <div>
                    <label for="nama_ibu_kandung" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Nama Ibu Kandung <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           id="nama_ibu_kandung"
                           name="nama_ibu_kandung"
                           value="{{ old('nama_ibu_kandung', $formData['nama_ibu_kandung'] ?? '') }}"
                           placeholder="Nama lengkap ibu kandung sesuai akta"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400 shadow-2xs">
                    <span class="text-[11px] text-slate-400 mt-1 block">Wajib untuk validasi data kelulusan nasional PDDikti</span>
                </div>

                <!-- Pekerjaan Orang Tua -->
                <div>
                    <label for="pekerjaan_orang_tua" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Pekerjaan Orang Tua / Wali
                    </label>
                    <select id="pekerjaan_orang_tua"
                            name="pekerjaan_orang_tua"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                        <option value="">-- Pilih Pekerjaan --</option>
                        @foreach(['PNS / TNI / Polri', 'Karyawan Swasta', 'Wirausaha / Pedagang', 'Petani / Peternak', 'Nelayan', 'Buruh Harian', 'Pensiunan', 'Ibu Rumah Tangga', 'Lainnya'] as $pok)
                            <option value="{{ $pok }}" {{ old('pekerjaan_orang_tua', $formData['pekerjaan_orang_tua'] ?? '') === $pok ? 'selected' : '' }}>{{ $pok }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Nomor WhatsApp Orang Tua -->
                <div>
                    <label for="wa_ortu" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Nomor WhatsApp Orang Tua / Wali
                    </label>
                    <x-input-whatsapp
                        name="wa_ortu"
                        id="wa_ortu"
                        :value="old('wa_ortu', $prospek->wa_ortu ?? $formData['wa_ortu'] ?? '')"
                        placeholder="81234567890"
                        class="bg-white border-slate-200"
                    />
                </div>

                <!-- Alamat Orang Tua -->
                <div>
                    <label for="alamat_orang_tua" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Alamat Orang Tua (Jika berbeda dengan calon mahasiswa)
                    </label>
                    <input type="text"
                           id="alamat_orang_tua"
                           name="alamat_orang_tua"
                           value="{{ old('alamat_orang_tua', $formData['alamat_orang_tua'] ?? '') }}"
                           placeholder="Alamat domisili orang tua"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition placeholder:text-slate-400 shadow-2xs">
                </div>
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- STICKY ACTION SUBMIT BAR                                        -->
        <!-- =============================================================== -->
        <div class="sticky bottom-4 z-20 bg-white/95 backdrop-blur-md rounded-2xl p-4 sm:p-5 border border-slate-200/90 shadow-lg flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-slate-500 text-center sm:text-left flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Pastikan data sudah benar sebelum melanjutkan ke pembayaran.</span>
            </div>

            <div class="flex items-center gap-2.5 w-full sm:w-auto">
                <a href="{{ route('pmb.akun.dashboard') }}"
                   class="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl font-bold text-xs text-slate-700 bg-slate-100 hover:bg-slate-200 transition text-center cursor-pointer">
                    Dashboard
                </a>

                <button type="submit"
                        :disabled="loading"
                        class="flex-1 sm:flex-initial px-6 py-2.5 rounded-xl font-extrabold text-xs text-white bg-[#0b3d82] hover:bg-[#072b5c] shadow-md shadow-blue-900/20 active:scale-95 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-75">
                    <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-text="loading ? 'Menyimpan & Menghitung Biaya...' : 'Simpan & Lanjut ke Pembayaran &rarr;'"></span>
                </button>
            </div>
        </div>

    </form>
</div>
@endsection
