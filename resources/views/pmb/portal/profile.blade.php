@extends('pmb.portal.layout')

@section('title', 'Form Pendaftaran Online - Portal PMB UCIC')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-12">

    <!-- Header Form (Matching Screenshot 4) -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-sm text-center">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-blue-50 text-[#0b3d82] border border-blue-200 mb-3">
            <svg class="w-4 h-4 text-[#0b3d82]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>PMB Online Universitas Catur Insan Cendekia {{ date('Y') }}/{{ date('Y') + 1 }}</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-[#0b3d82] tracking-tight font-display">
            FORM PENDAFTARAN ONLINE UCIC
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-2 max-w-xl mx-auto">
            Silakan lengkapi form pendaftaran online dibawah ini dengan data yang sebenarnya untuk keperluan seleksi dan administrasi akademik.
        </p>

        <!-- Status Badge -->
        <div class="mt-4 flex flex-wrap items-center justify-center gap-2 text-xs">
            <span class="text-slate-500 font-medium">Status Pengisian:</span>
            @if(($prospek?->status_formulir ?? '') === 'Sudah Isi')
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    Sudah Disubmit
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-bold bg-amber-50 text-amber-700 border border-amber-200">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Belum Lengkap (Silakan Isi)
                </span>
            @endif

            @if($prospek?->no_registrasi)
                <span class="font-mono text-slate-600 bg-slate-100 px-3 py-1 rounded-full font-semibold">
                    No. Reg: {{ $prospek->no_registrasi }}
                </span>
            @endif

            <a href="{{ route('pmb.akun.upload-dokumen') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full font-bold bg-[#0b3d82] text-white hover:bg-[#072b5c] transition shadow-2xs">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <span>Upload Dokumen Persyaratan &rarr;</span>
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi Sukses -->
    @if(session('success'))
        <div class="p-4 sm:p-5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center justify-between text-xs sm:text-sm shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
            <a href="{{ route('pmb.akun.dashboard') }}" class="font-bold text-emerald-800 hover:underline shrink-0 ml-3">
                Ke Dashboard &rarr;
            </a>
        </div>
    @endif

    <!-- Alert Notifikasi Validasi Gagal -->
    @if($errors->any())
        <div class="p-4 sm:p-5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs shadow-xs">
            <div class="flex items-center gap-2 font-bold mb-2 text-sm text-rose-800">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Terdapat kesalahan pengisian data pendaftaran:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 text-rose-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $formData = is_array($prospek?->form_data) ? $prospek->form_data : [];
        $schoolName = $prospek->sekolah?->nama ?? $formData['nama_sekolah'] ?? '';
        if (empty($schoolName) && !empty($prospek->notes) && preg_match('/Asal sekolah:\s*([^|]+)/', $prospek->notes, $m)) {
            $schoolName = trim($m[1]);
        }
    @endphp

    <!-- Main Form Container -->
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-6 sm:p-10">
        <form action="{{ route('pmb.akun.profil.update') }}" method="POST" x-data="{ loading: false }" @submit="loading = true" class="space-y-8">
            @csrf
            @method('PUT')

            <!-- =================================================================== -->
            <!-- 1. SECTION: DATA CALON MAHASISWA & DATA PRIBADI                     -->
            <!-- =================================================================== -->
            <div class="space-y-5">
                <!-- Section Banner (Matching Screenshot 4) -->
                <div class="bg-[#0b3d82] text-white py-3 px-5 rounded-xl font-bold text-sm sm:text-base flex items-center justify-center gap-2.5 shadow-xs font-display">
                    <svg class="w-5 h-5 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>Data Calon Mahasiswa</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-left">
                    <!-- Nama Lengkap -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Nama Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name', $prospek->name ?? $user->name) }}"
                               required
                               placeholder="Contoh: Abdul Mughni Nugraha"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition placeholder:text-slate-400 text-slate-800">
                        <span class="text-[11px] text-slate-400 mt-1 block">Maksimal 50 karakter sesuai identitas</span>
                        @error('name')
                            <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Email Aktif -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Email Aktif <span class="text-rose-500">*</span>
                        </label>
                        <input type="email"
                               id="email"
                               value="{{ $user->email }}"
                               disabled
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 text-sm cursor-not-allowed font-medium">
                        <span class="text-[11px] text-slate-400 mt-1 block">Terkunci ke akun login portal PMB</span>
                    </div>

                    <!-- Nomor HP Aktif -->
                    <div>
                        <label for="whatsapp" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Nomor HP / WhatsApp Aktif <span class="text-rose-500">*</span>
                        </label>
                        <input type="tel"
                               id="whatsapp"
                               name="whatsapp"
                               value="{{ old('whatsapp', $prospek->whatsapp ?? $user->phone) }}"
                               required
                               placeholder="Contoh: 085383222286"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition placeholder:text-slate-400 text-slate-800">
                        @error('whatsapp')
                            <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Asal Sekolah -->
                    <div>
                        <label for="asal_sekolah" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Asal Sekolah <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               id="asal_sekolah"
                               name="asal_sekolah"
                               value="{{ old('asal_sekolah', $schoolName) }}"
                               placeholder="Contoh: SMKN 1 Cirebon"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition placeholder:text-slate-400 text-slate-800">
                        @error('asal_sekolah')
                            <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Sub-banner: Data Mahasiswa / Identitas Pribadi -->
                <div class="pt-3">
                    <div class="bg-[#0b3d82] text-white py-2.5 px-5 rounded-xl font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-xs font-display mb-5">
                        <svg class="w-4 h-4 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                        </svg>
                        <span>Data Mahasiswa</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-left">
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
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition placeholder:text-slate-400 text-slate-800">
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
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition text-slate-800">
                        </div>

                        <!-- NIK (Nomor Induk Kependudukan) -->
                        <div class="sm:col-span-2">
                            <label for="nik" class="block text-xs font-bold text-slate-700 mb-1.5">
                                NIK (Nomor Induk Kependudukan) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text"
                                   id="nik"
                                   name="nik"
                                   maxlength="16"
                                   value="{{ old('nik', $formData['nik'] ?? '') }}"
                                   placeholder="16 digit angka sesuai KTP / Kartu Keluarga"
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition placeholder:text-slate-400 text-slate-800">
                            <span class="text-[11px] text-slate-400 mt-1 block">Wajib diisi 16 digit untuk verifikasi PDDikti</span>
                        </div>

                        <!-- Jenis Kelamin -->
                        <div>
                            <label for="jenis_kelamin" class="block text-xs font-bold text-slate-700 mb-1.5">
                                Jenis Kelamin <span class="text-rose-500">*</span>
                            </label>
                            <select id="jenis_kelamin"
                                    name="jenis_kelamin"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition bg-white text-slate-800">
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
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition bg-white text-slate-800">
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
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition bg-white text-slate-800">
                                <option value="">-- Pilih Status --</option>
                                @foreach(['Milik Sendiri', 'Milik Orangtua', 'Sewa / Kontrak', 'Kost', 'Asrama', 'Lainnya'] as $stt)
                                    <option value="{{ $stt }}" {{ old('status_tempat_tinggal', $formData['status_tempat_tinggal'] ?? '') === $stt ? 'selected' : '' }}>{{ $stt }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Bekerja -->
                        <div>
                            <label for="status_bekerja" class="block text-xs font-bold text-slate-700 mb-1.5">
                                Status Pekerjaan
                            </label>
                            <select id="status_bekerja"
                                    name="status_bekerja"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition bg-white text-slate-800">
                                <option value="">-- Pilih Status Bekerja --</option>
                                @foreach(['Belum Bekerja', 'Bekerja Penuh Waktu', 'Bekerja Paruh Waktu', 'Wirausaha'] as $sb)
                                    <option value="{{ $sb }}" {{ old('status_bekerja', $formData['status_bekerja'] ?? '') === $sb ? 'selected' : '' }}>{{ $sb }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =================================================================== -->
            <!-- 2. SECTION: DATA KONTAK DAN ALAMAT                                  -->
            <!-- =================================================================== -->
            <div class="space-y-5 pt-4">
                <div class="bg-[#0b3d82] text-white py-3 px-5 rounded-xl font-bold text-sm sm:text-base flex items-center justify-center gap-2.5 shadow-xs font-display">
                    <svg class="w-5 h-5 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Data Kontak dan Alamat</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-left">
                    <!-- Alamat Lengkap -->
                    <div class="sm:col-span-2">
                        <label for="alamat" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Alamat Lengkap (Jalan, RT/RW, No. Rumah) <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="alamat"
                                  name="alamat"
                                  rows="2"
                                  placeholder="Contoh: RT.03/RW.02, Jl. Kesambi Dalam No. 45"
                                  class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition placeholder:text-slate-400 text-slate-800">{{ old('alamat', $formData['alamat'] ?? '') }}</textarea>
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
                               placeholder="Contoh: Kelurahan Sunyaragi, Kec. Kesambi"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition placeholder:text-slate-400 text-slate-800">
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
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition placeholder:text-slate-400 text-slate-800">
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
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition placeholder:text-slate-400 text-slate-800">
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
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition placeholder:text-slate-400 text-slate-800">
                    </div>
                </div>
            </div>

            <!-- =================================================================== -->
            <!-- 3. SECTION: DATA PENDIDIKAN SEBELUMNYA                              -->
            <!-- =================================================================== -->
            <div class="space-y-5 pt-4">
                <div class="bg-[#0b3d82] text-white py-3 px-5 rounded-xl font-bold text-sm sm:text-base flex items-center justify-center gap-2.5 shadow-xs font-display">
                    <svg class="w-5 h-5 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M12 14l9-5-9-5-9 5 9 5z"/>
                        <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/>
                    </svg>
                    <span>Data Asal Pendidikan</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-left">
                    <!-- Jenis Sekolah -->
                    <div>
                        <label for="jenis_sekolah" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Jenis Sekolah
                        </label>
                        <select id="jenis_sekolah"
                                name="jenis_sekolah"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition bg-white text-slate-800">
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
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition bg-white text-slate-800">
                            <option value="">-- Pilih Jurusan --</option>
                            @foreach(['IPA', 'IPS', 'Kejuruan', 'Bahasa', 'Agama', 'Umum', 'Lainnya'] as $jurs)
                                <option value="{{ $jurs }}" {{ old('jurusan_sekolah', $formData['jurusan_sekolah'] ?? '') === $jurs ? 'selected' : '' }}>{{ $jurs }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Keterangan Kejuruan / Peminatan -->
                    <div>
                        <label for="keterangan_kejuruan" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Keterangan Kejuruan / Peminatan Khusus
                        </label>
                        <input type="text"
                               id="keterangan_kejuruan"
                               name="keterangan_kejuruan"
                               value="{{ old('keterangan_kejuruan', $formData['keterangan_kejuruan'] ?? '') }}"
                               placeholder="Contoh: Rekayasa Perangkat Lunak / Akuntansi"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition placeholder:text-slate-400 text-slate-800">
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
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition placeholder:text-slate-400 text-slate-800">
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
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition text-slate-800">
                    </div>
                </div>
            </div>

            <!-- =================================================================== -->
            <!-- 4. SECTION: DATA ORANG TUA / WALI                                   -->
            <!-- =================================================================== -->
            <div class="space-y-5 pt-4">
                <div class="bg-[#0b3d82] text-white py-3 px-5 rounded-xl font-bold text-sm sm:text-base flex items-center justify-center gap-2.5 shadow-xs font-display">
                    <svg class="w-5 h-5 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span>Data Orang Tua / Wali</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-left">
                    <!-- Nama Ibu Kandung -->
                    <div>
                        <label for="nama_ibu_kandung" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Nama Ibu Kandung <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               id="nama_ibu_kandung"
                               name="nama_ibu_kandung"
                               value="{{ old('nama_ibu_kandung', $formData['nama_ibu_kandung'] ?? '') }}"
                               placeholder="Nama lengkap ibu kandung"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition placeholder:text-slate-400 text-slate-800">
                        <span class="text-[11px] text-slate-400 mt-1 block">Wajib untuk sinkronisasi data ijazah nasional</span>
                    </div>

                    <!-- Pekerjaan Orang Tua -->
                    <div>
                        <label for="pekerjaan_orang_tua" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Pekerjaan Orang Tua / Wali
                        </label>
                        <select id="pekerjaan_orang_tua"
                                name="pekerjaan_orang_tua"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition bg-white text-slate-800">
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
                        <input type="tel"
                               id="wa_ortu"
                               name="wa_ortu"
                               value="{{ old('wa_ortu', $prospek->wa_ortu ?? $formData['wa_ortu'] ?? '') }}"
                               placeholder="Contoh: 081234567890"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition placeholder:text-slate-400 text-slate-800">
                    </div>

                    <!-- Alamat Orang Tua -->
                    <div>
                        <label for="alamat_orang_tua" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Alamat Orang Tua (Jika berbeda domisili)
                        </label>
                        <input type="text"
                               id="alamat_orang_tua"
                               name="alamat_orang_tua"
                               value="{{ old('alamat_orang_tua', $formData['alamat_orang_tua'] ?? '') }}"
                               placeholder="Alamat domisili orang tua"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition placeholder:text-slate-400 text-slate-800">
                    </div>
                </div>
            </div>

            <!-- =================================================================== -->
            <!-- 5. SECTION: PILIHAN PROGRAM STUDI & INFORMASI TAMBAHAN              -->
            <!-- =================================================================== -->
            <div class="space-y-5 pt-4">
                <div class="bg-[#0b3d82] text-white py-3 px-5 rounded-xl font-bold text-sm sm:text-base flex items-center justify-center gap-2.5 shadow-xs font-display">
                    <svg class="w-5 h-5 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span>Pilihan Program Studi & Informasi</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-left">
                    <!-- Prodi 1 -->
                    <div>
                        <label for="prodi_id" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Program Studi Pilihan 1 (Utama) <span class="text-rose-500">*</span>
                        </label>
                        <select id="prodi_id"
                                name="prodi_id"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition bg-white text-slate-800">
                            <option value="">-- Pilih Program Studi Utama --</option>
                            @foreach($prodis as $p)
                                <option value="{{ $p->id }}" {{ (old('prodi_id', $prospek->prodi_id ?? '') == $p->id) ? 'selected' : '' }}>
                                    {{ $p->jenjang ?? 'S1' }} {{ $p->nama }} ({{ $p->kode }})
                                </option>
                            @endforeach
                        </select>
                        @error('prodi_id')
                            <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Prodi 2 -->
                    <div>
                        <label for="prodi_pilihan_2_id" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Program Studi Pilihan 2 (Alternatif)
                        </label>
                        <select id="prodi_pilihan_2_id"
                                name="prodi_pilihan_2_id"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition bg-white text-slate-800">
                            <option value="">-- Pilih Alternatif (Opsional) --</option>
                            @foreach($prodis as $p)
                                <option value="{{ $p->id }}" {{ (old('prodi_pilihan_2_id', $prospek->prodi_pilihan_2_id ?? '') == $p->id) ? 'selected' : '' }}>
                                    {{ $p->jenjang ?? 'S1' }} {{ $p->nama }} ({{ $p->kode }})
                                </option>
                            @endforeach
                        </select>
                        @error('prodi_pilihan_2_id')
                            <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Tipe Kelas -->
                    <div>
                        <label for="tipe_kelas" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Tipe Kelas Kuliah
                        </label>
                        <select id="tipe_kelas"
                                name="tipe_kelas"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition bg-white text-slate-800">
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
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition bg-white text-slate-800">
                            <option value="">-- Darimana Anda Mengetahui UCIC? --</option>
                            @foreach(['Instagram / Media Sosial', 'Teman / Keluarga', 'Guru BK / Sekolah Asal', 'Kunjungan Tim Kampus', 'Website / Google', 'Pameran Pendidikan / Expo', 'Brosur / Spanduk', 'Lainnya'] as $info)
                                <option value="{{ $info }}" {{ old('informasi_pendaftaran', $prospek->source ?? $formData['informasi_pendaftaran'] ?? '') === $info ? 'selected' : '' }}>{{ $info }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Kode Referal -->
                    <div class="sm:col-span-2">
                        <label for="kode_referal" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Kode Referral / Rekomendasi (Opsional)
                        </label>
                        <input type="text"
                               id="kode_referal"
                               name="kode_referal"
                               value="{{ old('kode_referal', $formData['kode_referal'] ?? '') }}"
                               placeholder="Contoh: CIC-AGEN-123 atau Nama Pengajar/Staf"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#0b3d82] focus:ring-1 focus:ring-[#0b3d82] transition placeholder:text-slate-400 text-slate-800">
                    </div>
                </div>

                <!-- Checkbox Minat -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3 mt-4 text-xs">
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
                        <span><strong>Berminat mengikuti MiniClass AI & Bootcamp</strong> (Pelatihan AI praktis dan sertifikasi gratis).</span>
                    </label>
                </div>
            </div>

            <!-- =================================================================== -->
            <!-- ACTION SUBMIT BUTTON AREA                                           -->
            <!-- =================================================================== -->
            <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-xs text-slate-500 text-center sm:text-left">
                    <p>Perubahan data akan langsung tersimpan dan tercatat di dashboard panitia PMB.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                    <a href="{{ route('pmb.akun.dashboard') }}"
                       class="flex-1 sm:flex-initial px-4 py-3 rounded-xl font-bold text-xs text-slate-700 border border-slate-200 hover:bg-slate-50 transition text-center">
                        Dashboard
                    </a>
                    <a href="{{ route('pmb.akun.upload-dokumen') }}"
                       class="flex-1 sm:flex-initial px-4 py-3 rounded-xl font-bold text-xs text-[#0b3d82] bg-blue-50 border border-blue-200 hover:bg-blue-100 transition text-center">
                        Upload Dokumen &rarr;
                    </a>
                    <button type="submit"
                            :disabled="loading"
                            class="w-full sm:w-auto px-7 py-3 rounded-xl font-bold text-xs text-white bg-[#0b3d82] hover:bg-[#072b5c] shadow-md shadow-blue-900/20 transition active:scale-95 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-75">
                        <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span x-text="loading ? 'Menyimpan...' : 'Simpan Data Pendaftaran'"></span>
                    </button>
                </div>
            </div>

        </form>
    </div>

</div>
@endsection
