@php
    $pageTitle = 'Tambah Prospek Tim (SPV)';
    $pageSubtitle = 'Input Prospek Baru & Penugasan Handler (Sales / CS / Mandiri)';
@endphp

<x-app-layout :title="'Tambah Prospek - Supervisor CRM'">

    <div class="max-w-4xl mx-auto space-y-6" x-data="{
        assignType: 'sales',
        prospectType: 'Individu',
        selectedSekolahId: '',
        selectedPerusahaanId: '',
        prospectName: '',
        selectedSource: 'Teman/Keluarga/Saudara',
        noteText: '',
        get wordCount() {
            if (!this.noteText.trim()) return 0;
            return this.noteText.trim().split(/\s+/).length;
        }
    }">

        <!-- Back Button & Breadcrumb -->
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <a href="{{ route('spv.prospek.index') }}" class="hover:text-blue-600 flex items-center gap-1 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                Kembali ke Daftar Prospek Tim
            </a>
        </div>

        <!-- Alert Validation Errors -->
        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-2xl text-xs space-y-1 shadow-xs">
                <div class="flex items-center gap-2 font-bold text-rose-900">
                    <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Gagal Menyimpan Prospek:</span>
                </div>
                <ul class="list-disc pl-5 space-y-0.5 mt-1 font-medium">
                    @foreach ($errors->all() as $error)
                        <li class="whitespace-pre-line">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form Card -->
        <div class="crm-card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="pb-5 border-b border-slate-100 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Tambah Data Prospek Baru</h2>
                    <p class="text-xs text-slate-500 mt-1">Formulir pendaftaran prospek terpadu (Field Prodi & Kelas Wajib, Validasi Duplikat, & Handler Assignment).</p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0 self-start">
                    SPV Entry Form
                </span>
            </div>

            <form action="{{ route('spv.prospek.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- SECTION 1: PENUGASAN HANDLER (Sales / CS / SPV Mandiri) -->
                <div class="bg-indigo-50/50 p-4 sm:p-5 rounded-2xl border border-indigo-100 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-extrabold text-indigo-950 uppercase tracking-wider">
                            1. Penugasan Pemilik Lead & Handler <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[10px] text-indigo-600 font-semibold">Alokasi Tim</span>
                    </div>

                    <!-- Radio Toggle Assign Type -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer transition text-xs font-semibold"
                            :class="assignType === 'sales' ? 'bg-white border-indigo-600 text-indigo-900 ring-2 ring-indigo-500/20 shadow-xs' : 'bg-white/60 border-slate-200 text-slate-700 hover:bg-white'">
                            <input type="radio" name="assign_type" value="sales" x-model="assignType" class="text-indigo-600">
                            <div>
                                <span class="font-bold block">Tugaskan ke Sales</span>
                                <span class="text-[10px] text-slate-400 font-normal">Tim lapangan / B2B</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer transition text-xs font-semibold"
                            :class="assignType === 'cs' ? 'bg-white border-indigo-600 text-indigo-900 ring-2 ring-indigo-500/20 shadow-xs' : 'bg-white/60 border-slate-200 text-slate-700 hover:bg-white'">
                            <input type="radio" name="assign_type" value="cs" x-model="assignType" class="text-indigo-600">
                            <div>
                                <span class="font-bold block">Tugaskan ke CS</span>
                                <span class="text-[10px] text-slate-400 font-normal">Walk-in / WA PMB</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer transition text-xs font-semibold"
                            :class="assignType === 'self' ? 'bg-white border-indigo-600 text-indigo-900 ring-2 ring-indigo-500/20 shadow-xs' : 'bg-white/60 border-slate-200 text-slate-700 hover:bg-white'">
                            <input type="radio" name="assign_type" value="self" x-model="assignType" class="text-indigo-600">
                            <div>
                                <span class="font-bold block">Pegang Sendiri (SPV)</span>
                                <span class="text-[10px] text-slate-400 font-normal">Komunitas / Sapu Bersih</span>
                            </div>
                        </label>
                    </div>

                    <!-- Dropdown Sales (jika assignType === 'sales') -->
                    <div x-show="assignType === 'sales'" class="space-y-1 pt-1">
                        <label class="block text-[11px] font-bold text-slate-700">Pilih Anggota Tim Sales:</label>
                        <select name="sales_id" :required="assignType === 'sales'" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-800 font-semibold focus:ring-2 focus:ring-indigo-500/20">
                            <option value="">-- Pilih Sales Handler (Wajib) --</option>
                            @foreach($teamSales as $s)
                                <option value="{{ $s->id }}" {{ old('sales_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }} ({{ $s->wilayah?->nama ?? 'Wilayah SPV' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Dropdown CS (jika assignType === 'cs') -->
                    <div x-show="assignType === 'cs'" x-cloak class="space-y-1 pt-1">
                        <label class="block text-[11px] font-bold text-slate-700">Pilih Petugas Customer Service (CS):</label>
                        <select name="cs_id" :required="assignType === 'cs'" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-800 font-semibold focus:ring-2 focus:ring-indigo-500/20">
                            <option value="">-- Pilih Petugas CS (Wajib) --</option>
                            @foreach($teamCs as $c)
                                <option value="{{ $c->id }}" {{ old('cs_id') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} (Customer Service PMB)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="assignType === 'self'" x-cloak class="text-[11px] text-slate-600 bg-white/80 p-2.5 rounded-xl border border-indigo-100">
                        ℹ️ Penanganan mandiri: SPV bertindak langsung sebagai pemegang sekaligus penutup lead.
                    </div>
                </div>

                <!-- SECTION 2: IDENTITAS PROSPEK & TANGGAL MASUK -->
                <div class="space-y-4">
                    <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider pb-1 border-b border-slate-100">
                        2. Identitas Prospek & Kontak
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Masuk</label>
                            <input type="date" name="tanggal_masuk" value="{{ old('tanggal_masuk', date('Y-m-d')) }}" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-medium focus:ring-2 focus:ring-blue-500/20 focus:bg-white">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Entitas Prospek <span class="text-rose-500">*</span></label>
                            <div class="grid grid-cols-3 gap-2">
                                <label class="flex items-center justify-center p-2.5 rounded-xl border cursor-pointer text-xs font-semibold text-center transition"
                                    :class="prospectType === 'Individu' ? 'bg-blue-50 border-blue-600 text-blue-800 ring-1 ring-blue-600/20' : 'bg-slate-50 border-slate-200 text-slate-600'">
                                    <input type="radio" name="type" value="Individu" x-model="prospectType" class="hidden">
                                    <span>👤 Siswa / Individu</span>
                                </label>
                                <label class="flex items-center justify-center p-2.5 rounded-xl border cursor-pointer text-xs font-semibold text-center transition"
                                    :class="prospectType === 'Sekolah' ? 'bg-blue-50 border-blue-600 text-blue-800 ring-1 ring-blue-600/20' : 'bg-slate-50 border-slate-200 text-slate-600'">
                                    <input type="radio" name="type" value="Sekolah" x-model="prospectType" class="hidden">
                                    <span>🏫 Sekolah (SMA)</span>
                                </label>
                                <label class="flex items-center justify-center p-2.5 rounded-xl border cursor-pointer text-xs font-semibold text-center transition"
                                    :class="prospectType === 'Corporate' ? 'bg-blue-50 border-blue-600 text-blue-800 ring-1 ring-blue-600/20' : 'bg-slate-50 border-slate-200 text-slate-600'">
                                    <input type="radio" name="type" value="Corporate" x-model="prospectType" class="hidden">
                                    <span>🏢 Perusahaan</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Sekolah Selector (jika Sekolah) -->
                    <div x-show="prospectType === 'Sekolah'" x-cloak class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-700">Pilih dari Master Data Sekolah (Opsional)</label>
                        <select name="sekolah_id" x-model="selectedSekolahId" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-medium focus:ring-2 focus:ring-blue-500/20 focus:bg-white">
                            <option value="">-- Pilih Sekolah Terdaftar atau Ketik Manual di Bawah --</option>
                            @foreach($sekolahs as $sekolah)
                                <option value="{{ $sekolah->id }}">{{ $sekolah->nama }} ({{ $sekolah->kota ?? 'Wilayah UCIC' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Perusahaan Selector (jika Corporate) -->
                    <div x-show="prospectType === 'Corporate'" x-cloak class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-700">Pilih dari Master Data Perusahaan (Opsional)</label>
                        <select name="perusahaan_id" x-model="selectedPerusahaanId" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-medium focus:ring-2 focus:ring-blue-500/20 focus:bg-white">
                            <option value="">-- Pilih Perusahaan Terdaftar atau Ketik Manual di Bawah --</option>
                            @foreach($perusahaans as $perusahaan)
                                <option value="{{ $perusahaan->id }}">{{ $perusahaan->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Nama Prospek -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-700">Nama Calon Mahasiswa / Nama Prospek <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="prospectName" value="{{ old('name') }}" placeholder="Contoh: Muhammad Rizky atau SMAN 1 Cirebon" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-medium focus:ring-2 focus:ring-blue-500/20 focus:bg-white" required>
                    </div>

                    <!-- PIC & WhatsApp Contact -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-700">Nama PIC / Kontak yang Dihubungi <span class="text-rose-500">*</span></label>
                            <input type="text" name="pic" value="{{ old('pic') }}" placeholder="Contoh: Muhammad Rizky / Ibu Siti (Guru BK)" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:bg-white" required>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-700">Nomor WhatsApp Aktif <span class="text-rose-500">*</span></label>
                            <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="Contoh: 081234567890" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:ring-2 focus:ring-blue-500/20 focus:bg-white" required>
                            <p class="text-[10px] text-slate-400">Sistem otomatis memvalidasi duplikasi nomor HP & nama.</p>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: AKADEMIK (PRODI & KELAS) -->
                <div class="space-y-4 pt-2">
                    <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider pb-1 border-b border-slate-100 flex items-center justify-between">
                        <span>3. Akademik (Prodi & Kelas)</span>
                        <span class="text-[10px] text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">Field Wajib</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Prodi Diminati -->
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-700">
                                Program Studi Diminati <span class="text-rose-500">*</span>
                            </label>
                            <select name="prodi_id" required class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-bold focus:ring-2 focus:ring-blue-500/20 focus:bg-white">
                                <option value="">-- Pilih Program Studi --</option>
                                @foreach($prodis as $prodi)
                                    <option value="{{ $prodi->id }}" {{ old('prodi_id') == $prodi->id ? 'selected' : '' }}>
                                        {{ $prodi->nama }} ({{ $prodi->jenjang }} • {{ $prodi->fakultas }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-slate-400">Diambil langsung dari Master Data Program Studi Admin.</p>
                        </div>

                        <!-- Kelas (Reguler / Karyawan) -->
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-700">
                                Pilihan Kelas Kuliah <span class="text-rose-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2 pt-0.5">
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer text-xs font-semibold transition bg-slate-50 border-slate-200 hover:bg-slate-100 has-checked:bg-blue-50 has-checked:border-blue-600 has-checked:text-blue-900">
                                    <input type="radio" name="kelas" value="Reguler" checked class="text-blue-600">
                                    <div>
                                        <span class="block font-bold">Reguler</span>
                                        <span class="text-[10px] text-slate-400 font-normal">Kelas Pagi / Siang</span>
                                    </div>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer text-xs font-semibold transition bg-slate-50 border-slate-200 hover:bg-slate-100 has-checked:bg-purple-50 has-checked:border-purple-600 has-checked:text-purple-900">
                                    <input type="radio" name="kelas" value="Karyawan" {{ old('kelas') === 'Karyawan' ? 'checked' : '' }} class="text-purple-600">
                                    <div>
                                        <span class="block font-bold">Karyawan</span>
                                        <span class="text-[10px] text-slate-400 font-normal">Kelas Sore / Malam</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 4: SUMBER INFORMASI & PIPELINE STATUS -->
                <div class="space-y-4 pt-2">
                    <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider pb-1 border-b border-slate-100">
                        4. Sumber Informasi & Status Pipeline
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Sumber Informasi 10 Opsi Baku -->
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-700">
                                Sumber Informasi <span class="text-rose-500">*</span>
                            </label>
                            <select name="source" x-model="selectedSource" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:ring-2 focus:ring-blue-500/20 focus:bg-white" required>
                                @foreach($sources as $src)
                                    <option value="{{ $src }}" {{ old('source') == $src ? 'selected' : '' }}>
                                        {{ $src }}
                                    </option>
                                @endforeach
                            </select>

                            <!-- Dynamic Custom Source if 'Lainnya' -->
                            <div x-show="selectedSource === 'Lainnya'" x-cloak class="mt-2">
                                <input type="text" name="custom_source" value="{{ old('custom_source') }}" placeholder="Tuliskan sumber spesifik..." class="w-full text-xs px-3.5 py-2 rounded-xl bg-white border border-amber-300 text-slate-800 focus:ring-2 focus:ring-amber-500/20">
                            </div>
                        </div>

                        <!-- Status Awal Pipeline -->
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-700">
                                Status Awal Pipeline <span class="text-rose-500">*</span>
                            </label>
                            <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-bold focus:ring-2 focus:ring-blue-500/20 focus:bg-white" required>
                                @foreach($statuses as $st)
                                    <option value="{{ $st }}" {{ old('status', '01 BARU') == $st ? 'selected' : '' }}>
                                        {{ $st }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-slate-400">Default: 01 BARU (Wajib kontak pertama ≤30 menit).</p>
                        </div>
                    </div>

                    <!-- Wilayah Scoping SPV -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-700">Wilayah / Kota Prospek</label>
                        <select name="wilayah_id" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-medium focus:ring-2 focus:ring-blue-500/20 focus:bg-white">
                            <option value="">-- Gunakan Wilayah SPV (Default) --</option>
                            @foreach($wilayahs as $w)
                                <option value="{{ $w->id }}" {{ (old('wilayah_id', auth()->user()->wilayah_id) == $w->id) ? 'selected' : '' }}>
                                    {{ $w->nama }} ({{ $w->level }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Catatan Khusus (Maksimal 10 kata) -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold text-slate-700">Catatan Khusus (Instruksi Tindak Lanjut)</label>
                            <span class="text-[10px] font-bold" :class="wordCount > 10 ? 'text-rose-600' : 'text-slate-400'">
                                <span x-text="wordCount"></span> / 10 kata (Maksimal)
                            </span>
                        </div>
                        <textarea 
                            name="notes" 
                            x-model="noteText" 
                            rows="2" 
                            placeholder="Maksimal 10 kata, contoh: Minat beasiswa prestasi, follow up via WhatsApp sore hari." 
                            class="w-full text-xs p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:bg-white"
                        >{{ old('notes') }}</textarea>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="{{ route('spv.prospek.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Simpan & Tugaskan Prospek</span>
                    </button>
                </div>

            </form>
        </div>

    </div>

</x-app-layout>
