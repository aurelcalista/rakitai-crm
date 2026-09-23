@php
    $isEventMode  = isset($event) && $event !== null;
    $pageTitle    = $isEventMode ? 'Laporan Kunjungan Event' : 'Tambah Kunjungan Mandiri';
    $pageSubtitle = $isEventMode
        ? 'Form laporan kunjungan terikat dengan Event: ' . ($event->nama ?? $event->name)
        : 'Kunjungan tidak terkait dengan Jadwal Event';
@endphp

<x-app-layout :title="'Tambah Kunjungan - CRM UCIC'">
    <div class="space-y-6">

        {{-- ─── Page Header ─────────────────────────────────────────────────── --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center gap-3">
                <a href="{{ route('sales.kunjungan.index') }}" class="p-1.5 rounded-lg bg-slate-100 text-slate-500 hover:text-slate-700 hover:bg-slate-200 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ $pageTitle }}</h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">{{ $pageSubtitle }}</p>
                </div>
            </div>
            @if($isEventMode)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 text-xs font-bold">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Dari Jadwal Event
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 text-slate-600 border border-slate-200 text-xs font-bold">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Kunjungan Mandiri
                </span>
            @endif
        </div>

        {{-- Flash errors --}}
        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 text-sm text-rose-700">
                <p class="font-bold mb-1">Terdapat kesalahan input:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ─── MAIN FORM ───────────────────────────────────────────────────── --}}
        <form
            action="{{ route('sales.kunjungan.store') }}"
            method="POST"
            enctype="multipart/form-data"
            x-data="{
                jenisTab: '{{ $isEventMode ? ($event->jenis_institusi ?? 'Sekolah') : 'Sekolah' }}',
                fotoPreview: null,
                geoStatus: 'idle', /* idle | loading | ok | error */
                lat: '',
                lng: '',
                captureGeo() {
                    this.geoStatus = 'loading';
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.lat = pos.coords.latitude;
                            this.lng = pos.coords.longitude;
                            this.geoStatus = 'ok';
                        },
                        () => { this.geoStatus = 'error'; },
                        { enableHighAccuracy: true, timeout: 15000 }
                    );
                }
            }"
            @submit.prevent="if(!lat || !lng){ alert('Lokasi GPS belum ditangkap. Tekan tombol Tangkap GPS terlebih dahulu.'); return; } $el.submit();"
        >
            @csrf

            {{-- Hidden: event_id (hanya untuk Alur 1) --}}
            @if($isEventMode)
                <input type="hidden" name="event_id" value="{{ $event->id }}">
                <input type="hidden" name="jenis" value="{{ $event->jenis_institusi ?? 'Sekolah' }}">
            @endif

            <input type="hidden" name="lat" :value="lat">
            <input type="hidden" name="lng" :value="lng">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- ── KOLOM KIRI: Info Instansi + Data Laporan ──────────────────── --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- ─── BLOK A: INFORMASI INSTANSI / EVENT ─────────────────── --}}
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-4">

                        @if($isEventMode)
                            {{-- ════ ALUR 1: DATA DARI EVENT (READ-ONLY) ════ --}}
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Informasi Event / Instansi
                                </h3>
                                <p class="text-xs text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2 mb-4">
                                    Data instansi di bawah berasal dari Event yang sudah terdaftar. Anda tidak perlu mengisi ulang data ini.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Nama Event</p>
                                    <p class="text-sm font-bold text-slate-800">{{ $event->nama ?? $event->name }}</p>
                                </div>
                                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Jenis Institusi</p>
                                    <p class="text-sm font-bold text-slate-800">{{ $event->jenis_institusi ?? '-' }}</p>
                                </div>
                                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Nama Instansi</p>
                                    <p class="text-sm font-bold text-slate-800">
                                        {{ $event->nama_institusi ?: ($event->sekolah?->nama ?? $event->perusahaan?->nama ?? $event->lokasi ?? '-') }}
                                    </p>
                                </div>
                                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Lokasi / Tempat</p>
                                    <p class="text-sm font-bold text-slate-800">{{ $event->lokasi ?? '-' }}</p>
                                </div>
                                <div class="p-3.5 bg-emerald-50 rounded-xl border border-emerald-200">
                                    <p class="text-[10px] font-semibold text-emerald-600 uppercase tracking-wider mb-1">PIC / Guru BK</p>
                                    <p class="text-sm font-bold text-slate-800">{{ $event->pic_name ?: '-' }}</p>
                                </div>
                                <div class="p-3.5 bg-emerald-50 rounded-xl border border-emerald-200">
                                    <p class="text-[10px] font-semibold text-emerald-600 uppercase tracking-wider mb-1">Nomor WhatsApp PIC</p>
                                    <p class="text-sm font-bold text-slate-800">{{ $event->pic_whatsapp ?: '-' }}</p>
                                </div>
                            </div>

                        @else
                            {{-- ════ ALUR 2: KUNJUNGAN MANDIRI (INPUT MANUAL) ════ --}}
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 mb-4">
                                    Informasi Instansi
                                </h3>
                            </div>

                            {{-- Tab Sekolah / Perusahaan --}}
                            <div class="flex gap-2 mb-4">
                                <button type="button"
                                    @click="jenisTab = 'Sekolah'"
                                    :class="jenisTab === 'Sekolah' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    class="flex-1 px-4 py-2 rounded-xl text-sm font-semibold transition cursor-pointer">
                                    🏫 Sekolah (SMA/SMK)
                                </button>
                                <button type="button"
                                    @click="jenisTab = 'Perusahaan'"
                                    :class="jenisTab === 'Perusahaan' ? 'bg-purple-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    class="flex-1 px-4 py-2 rounded-xl text-sm font-semibold transition cursor-pointer">
                                    🏢 Corporate
                                </button>
                            </div>

                            <input type="hidden" name="jenis" :value="jenisTab">

                            {{-- Sekolah fields --}}
                            <div x-show="jenisTab === 'Sekolah'" x-cloak class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Pilih Sekolah <span class="text-slate-400 font-normal">(opsional, jika sudah terdaftar)</span></label>
                                    <select name="sekolah_id" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition">
                                        <option value="">-- Pilih Sekolah atau isi manual di bawah --</option>
                                        @foreach($sekolahs as $s)
                                            <option value="{{ $s->id }}" {{ old('sekolah_id') == $s->id ? 'selected' : '' }}>{{ $s->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nama Instansi (jika tidak ada di daftar)</label>
                                    <input type="text" name="nama_institusi_sekolah" value="{{ old('nama_institusi') }}"
                                        placeholder="Contoh: SMK Cinta Rakyat 3"
                                        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition">
                                </div>
                            </div>

                            {{-- Perusahaan fields --}}
                            <div x-show="jenisTab === 'Perusahaan'" x-cloak class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Pilih Perusahaan <span class="text-slate-400 font-normal">(opsional, jika sudah terdaftar)</span></label>
                                    <select name="perusahaan_id" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition">
                                        <option value="">-- Pilih Perusahaan atau isi manual di bawah --</option>
                                        @foreach($perusahaans as $p)
                                            <option value="{{ $p->id }}" {{ old('perusahaan_id') == $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nama Perusahaan (jika tidak ada di daftar)</label>
                                    <input type="text" name="nama_institusi_perusahaan" value="{{ old('nama_institusi') }}"
                                        placeholder="Contoh: PT Maju Bersama"
                                        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition">
                                </div>
                            </div>

                            {{-- Alamat --}}
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Alamat</label>
                                <input type="text" name="alamat" value="{{ old('alamat') }}"
                                    placeholder="Jl. contoh no. 1, Kota"
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition">
                            </div>

                            {{-- PIC --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nama PIC / Guru BK <span class="text-rose-500">*</span></label>
                                    <input type="text" name="pic_name" value="{{ old('pic_name') }}" required
                                        placeholder="Contoh: Budi Santoso"
                                        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition @error('pic_name') border-rose-300 @enderror">
                                    @error('pic_name')<p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">No. WhatsApp PIC <span class="text-rose-500">*</span></label>
                                    <input type="text" name="pic_whatsapp" value="{{ old('pic_whatsapp') }}" required
                                        placeholder="08123456789"
                                        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition @error('pic_whatsapp') border-rose-300 @enderror">
                                    @error('pic_whatsapp')<p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        @endif

                    </div>

                    {{-- ─── BLOK B: WAKTU & PRODI ───────────────────────────────── --}}
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">
                            Waktu & Program Studi
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tanggal Kunjungan <span class="text-rose-500">*</span></label>
                                <input type="date" name="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" required
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition @error('tanggal') border-rose-300 @enderror">
                                @error('tanggal')<p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Waktu Kunjungan <span class="text-rose-500">*</span></label>
                                <input type="time" name="waktu" value="{{ old('waktu', date('H:i')) }}" required
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition @error('waktu') border-rose-300 @enderror">
                                @error('waktu')<p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Program Studi <span class="text-rose-500">*</span></label>
                                <select name="prodi_id" required class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition @error('prodi_id') border-rose-300 @enderror">
                                    <option value="">Pilih Prodi</option>
                                    @foreach($prodis as $p)
                                        <option value="{{ $p->id }}" {{ old('prodi_id') == $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                                    @endforeach
                                </select>
                                @error('prodi_id')<p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- ─── BLOK C: HASIL KUNJUNGAN (SEKOLAH) ──────────────────── --}}
                    <div x-show="jenisTab === 'Sekolah'" class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">
                            Hasil Kunjungan — Sekolah
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Potensi Mahasiswa / Pendaftar Baru</label>
                                <input type="text" name="potensi_mahasiswa" value="{{ old('potensi_mahasiswa') }}"
                                    placeholder="Contoh: 30-50 siswa"
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 transition">
                            </div>
                            <div class="sm:col-span-1 flex items-center gap-3 pt-5">
                                <input type="checkbox" name="kesediaan_training_ai" id="training_ai" value="1"
                                    {{ old('kesediaan_training_ai') ? 'checked' : '' }}
                                    class="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500">
                                <label for="training_ai" class="text-sm font-semibold text-slate-700 cursor-pointer">Bersedia Workshop AI</label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Detail Potensi Mahasiswa</label>
                            <textarea name="detail_potensi_mahasiswa" rows="3"
                                placeholder="Deskripsikan potensi lebih detail: jurusan yang diminati, antusiasme siswa, dll."
                                class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 transition">{{ old('detail_potensi_mahasiswa') }}</textarea>
                        </div>
                    </div>

                    {{-- ─── BLOK D: HASIL KUNJUNGAN (CORPORATE) ────────────────── --}}
                    <div x-show="jenisTab === 'Perusahaan'" x-cloak class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">
                            Hasil Kunjungan — Corporate
                        </h3>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Bidang Usaha</label>
                            <input type="text" name="bidang_usaha" value="{{ old('bidang_usaha') }}"
                                placeholder="Contoh: Teknologi Informasi"
                                class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 transition">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Potensi S1</label>
                                <input type="text" name="potensi_s1" value="{{ old('potensi_s1') }}"
                                    placeholder="Jumlah / estimasi"
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Potensi S2</label>
                                <input type="text" name="potensi_s2" value="{{ old('potensi_s2') }}"
                                    placeholder="Jumlah / estimasi"
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Program CSR</label>
                                <input type="text" name="potensi_csr" value="{{ old('potensi_csr') }}"
                                    placeholder="Bentuk kerjasama"
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 transition">
                            </div>
                        </div>
                    </div>

                    {{-- ─── BLOK E: CATATAN / HASIL PEMBICARAAN ────────────────── --}}
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">
                            Catatan & Hasil Pembicaraan
                        </h3>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Catatan Kunjungan</label>
                            <textarea name="catatan" rows="5"
                                placeholder="Tuliskan hasil diskusi, kesepakatan, poin penting yang dibahas, dan tindak lanjut yang diperlukan..."
                                class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 transition @error('catatan') border-rose-300 @enderror">{{ old('catatan') }}</textarea>
                            @error('catatan')<p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Dosen / Pemateri (Opsional)</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <select name="dosen_id" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 transition">
                                    <option value="">-- Pilih Dosen (jika Training) --</option>
                                    @foreach($dosens as $d)
                                        <option value="{{ $d->id }}" {{ old('dosen_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="dosen_pemateri" value="{{ old('dosen_pemateri') }}"
                                    placeholder="Atau isi nama pemateri manual"
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 transition">
                            </div>
                        </div>
                    </div>

                </div>

                {{-- ── KOLOM KANAN: Foto + GPS + Submit ──────────────────────────── --}}
                <div class="space-y-6">

                    {{-- ─── BLOK F: FOTO DOKUMENTASI ───────────────────────────── --}}
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 mb-4">
                            Dokumentasi Foto <span class="text-rose-500">*</span>
                        </h3>

                        <div
                            @click="$refs.fotoInput.click()"
                            class="relative w-full h-48 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 overflow-hidden cursor-pointer hover:border-blue-400 hover:bg-blue-50/30 transition group"
                        >
                            <template x-if="!fotoPreview">
                                <div class="flex flex-col items-center justify-center h-full gap-2 text-slate-400 group-hover:text-blue-500 transition">
                                    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <p class="text-xs font-semibold">Klik untuk upload foto</p>
                                    <p class="text-[11px]">JPG, PNG, WEBP — Max 5MB</p>
                                </div>
                            </template>
                            <template x-if="fotoPreview">
                                <img :src="fotoPreview" class="w-full h-full object-cover">
                            </template>
                        </div>

                        <input
                            type="file"
                            name="foto"
                            accept="image/*"
                            x-ref="fotoInput"
                            class="hidden"
                            required
                            @change="
                                const file = $event.target.files[0];
                                if (file) {
                                    const reader = new FileReader();
                                    reader.onload = e => fotoPreview = e.target.result;
                                    reader.readAsDataURL(file);
                                }
                            "
                        >
                        @error('foto')<p class="text-rose-500 text-[11px] mt-2">{{ $message }}</p>@enderror
                    </div>

                    {{-- ─── BLOK G: LOKASI GPS ──────────────────────────────────── --}}
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 mb-4">
                            Verifikasi Lokasi GPS <span class="text-rose-500">*</span>
                        </h3>

                        <button
                            type="button"
                            @click="captureGeo()"
                            :disabled="geoStatus === 'loading'"
                            class="w-full px-4 py-2.5 rounded-xl text-sm font-semibold transition cursor-pointer flex items-center justify-center gap-2"
                            :class="{
                                'bg-blue-600 hover:bg-blue-700 text-white': geoStatus === 'idle',
                                'bg-slate-300 text-slate-500 cursor-not-allowed': geoStatus === 'loading',
                                'bg-emerald-600 hover:bg-emerald-700 text-white': geoStatus === 'ok',
                                'bg-rose-600 hover:bg-rose-700 text-white': geoStatus === 'error',
                            }"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span x-text="{
                                idle: 'Tangkap Lokasi GPS',
                                loading: 'Mendeteksi lokasi...',
                                ok: '✓ Lokasi Berhasil Ditangkap',
                                error: 'Gagal — Coba Lagi'
                            }[geoStatus]"></span>
                        </button>

                        <div x-show="geoStatus === 'ok'" x-cloak class="mt-3 p-3 bg-emerald-50 rounded-xl border border-emerald-100 text-[11px] text-emerald-700 space-y-1">
                            <p><span class="font-semibold">Lat:</span> <span x-text="lat"></span></p>
                            <p><span class="font-semibold">Lng:</span> <span x-text="lng"></span></p>
                        </div>

                        <div x-show="geoStatus === 'error'" x-cloak class="mt-3 p-3 bg-rose-50 rounded-xl border border-rose-100 text-[11px] text-rose-700">
                            Tidak dapat mendeteksi lokasi. Pastikan GPS aktif dan izin lokasi diizinkan di browser.
                        </div>

                        <p class="text-[11px] text-slate-400 mt-3">
                            Lokasi GPS digunakan untuk memverifikasi bahwa kunjungan dilakukan di lokasi yang benar.
                        </p>
                    </div>

                    {{-- ─── BLOK H: OPSI TAMBAHAN ───────────────────────────────── --}}
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 mb-4">
                            Opsi Tambahan
                        </h3>
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="checkbox" name="jadikan_prospek" value="1"
                                {{ old('jadikan_prospek') ? 'checked' : '' }}
                                class="mt-0.5 w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500">
                            <div>
                                <p class="text-sm font-semibold text-slate-700 group-hover:text-blue-700 transition">Jadikan Prospek Baru</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Instansi ini akan otomatis ditambahkan ke daftar Prospek Anda.</p>
                            </div>
                        </label>
                    </div>

                    {{-- ─── BLOK I: SUBMIT ──────────────────────────────────────── --}}
                    <div class="flex flex-col gap-3">
                        <button
                            type="submit"
                            class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold transition shadow-sm cursor-pointer flex items-center justify-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Simpan Laporan Kunjungan
                        </button>
                        <a href="{{ route('sales.kunjungan.index') }}"
                            class="w-full px-6 py-2.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-sm font-semibold transition text-center cursor-pointer">
                            Batal
                        </a>
                    </div>

                </div>
            </div>

        </form>

    </div>

    {{-- Alpine: sync nama_institusi dari dua input --}}
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            // Unify nama_institusi from Sekolah/Perusahaan free-text inputs
            const jenisInput = this.querySelector('input[name="jenis"]') || this.querySelector('select[name="jenis"]');
            const jenis = jenisInput ? jenisInput.value : '';

            let namaInput = null;
            if (jenis === 'Sekolah') {
                namaInput = this.querySelector('input[name="nama_institusi_sekolah"]');
            } else {
                namaInput = this.querySelector('input[name="nama_institusi_perusahaan"]');
            }

            if (namaInput && namaInput.value.trim()) {
                // Create hidden field to pass nama_institusi
                let hidden = this.querySelector('input[name="nama_institusi"]');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'nama_institusi';
                    this.appendChild(hidden);
                }
                hidden.value = namaInput.value.trim();
            }
        });
    </script>

</x-app-layout>
