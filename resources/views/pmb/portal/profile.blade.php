@extends('pmb.portal.layout')

@section('title', 'Profil Saya - Akun CAMABA')

@section('content')
<div class="space-y-6">

    <!-- Header Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
        <div>
            <h1 class="text-2xl font-black text-slate-900 font-display tracking-tight">Profil Calon Mahasiswa</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Lihat dan perbarui data diri serta program studi pilihan Anda</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                Akun Terverifikasi
            </span>
        </div>
    </div>

    <!-- Edit Profile Card -->
    <div class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-2xs">
        <form action="{{ route('pmb.akun.profil.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Section 1: Informasi Akun (Readonly) -->
            <div>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    <span>Informasi Akun & Registrasi</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-500 mb-1">Nomor Registrasi PMB</label>
                        <input type="text"
                               value="{{ $prospek->no_registrasi ?? '-' }}"
                               disabled
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 font-bold cursor-not-allowed">
                        <span class="text-[10px] text-slate-400 mt-1 block">Dibuat otomatis oleh sistem PMB</span>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-500 mb-1">Alamat Email (Akun)</label>
                        <input type="email"
                               value="{{ $user->email }}"
                               disabled
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 font-medium cursor-not-allowed">
                        <span class="text-[10px] text-slate-400 mt-1 block">Digunakan sebagai username login</span>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-500 mb-1">Gelombang Pendaftaran</label>
                        <input type="text"
                               value="{{ $prospek->gelombang ?? 'Gelombang 1' }}"
                               disabled
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 font-medium cursor-not-allowed">
                        <span class="text-[10px] text-slate-400 mt-1 block">Tahun Akademik {{ date('Y') }}/{{ date('Y') + 1 }}</span>
                    </div>
                </div>
            </div>

            <!-- Section 2: Data Diri yang Dapat Diperbarui -->
            <div>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    <span>Data Diri Calon Mahasiswa</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Nama Lengkap -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nama Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name', $prospek->name ?? $user->name) }}"
                               required
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition">
                        @error('name')
                            <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- WhatsApp / HP -->
                    <div>
                        <label for="whatsapp" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nomor WhatsApp / HP Aktif <span class="text-rose-500">*</span>
                        </label>
                        <input type="tel"
                               id="whatsapp"
                               name="whatsapp"
                               value="{{ old('whatsapp', $prospek->whatsapp ?? $user->phone) }}"
                               required
                               placeholder="081234567890"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition">
                        @error('whatsapp')
                            <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Asal Sekolah -->
                    <div class="sm:col-span-2">
                        <label for="asal_sekolah" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Asal Sekolah (SMA / SMK / MA)
                        </label>
                        <input type="text"
                               id="asal_sekolah"
                               name="asal_sekolah"
                               value="{{ old('asal_sekolah', $prospek->sekolah?->nama ?? ($prospek->notes ? (preg_match('/Asal sekolah:\s*([^|]+)/', $prospek->notes, $m) ? trim($m[1]) : '') : '')) }}"
                               placeholder="Nama sekolah asal Anda (contoh: SMAN 1 Cirebon)"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition">
                        @error('asal_sekolah')
                            <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 3: Pilihan Program Studi -->
            <div>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    <span>Pilihan Program Studi</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Prodi 1 -->
                    <div>
                        <label for="prodi_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Program Studi Pilihan 1 <span class="text-rose-500">*</span>
                        </label>
                        <select id="prodi_id"
                                name="prodi_id"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition bg-white">
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
                        <label for="prodi_pilihan_2_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Program Studi Pilihan 2 (Alternatif)
                        </label>
                        <select id="prodi_pilihan_2_id"
                                name="prodi_pilihan_2_id"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition bg-white">
                            <option value="">-- Pilih Program Studi Alternatif (Opsional) --</option>
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
                </div>
            </div>

            <!-- Section 4: Minat & Partisipasi Program -->
            <div>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    <span>Minat Perkuliahan & Kegiatan</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                        <label class="block font-bold text-slate-800 mb-2">Berminat Melanjutkan Kuliah?</label>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="minat_kuliah" value="ya" {{ old('minat_kuliah', $prospek->minat_kuliah ?? 'ya') === 'ya' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                                <span>Ya, Sangat Berminat</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="minat_kuliah" value="tidak" {{ old('minat_kuliah', $prospek->minat_kuliah ?? 'ya') === 'tidak' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                                <span>Masih Mempertimbangkan</span>
                            </label>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                        <label class="block font-bold text-slate-800 mb-2">Berminat Mengikuti MiniClass AI UCIC?</label>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="minat_miniclass" value="ya" {{ old('minat_miniclass', $prospek->minat_miniclass ?? 'tidak') === 'ya' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                                <span>Ya, Tertarik Ikut</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="minat_miniclass" value="tidak" {{ old('minat_miniclass', $prospek->minat_miniclass ?? 'tidak') === 'tidak' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                                <span>Tidak / Nanti Saja</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button Area -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-500">
                    Perubahan data diri akan tersinkronisasi ke data panitia PMB.
                </span>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl font-bold text-xs text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 transition active:scale-95">
                    Simpan Perubahan Profil
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
