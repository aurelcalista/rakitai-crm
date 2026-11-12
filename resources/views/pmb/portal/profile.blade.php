@extends('pmb.portal.layout')

@section('title', 'Profil Akun Saya - Portal PMB UCIC')

@section('content')
<div class="w-full max-w-4xl mx-auto space-y-6 pb-12">
    <!-- Header Hero Profil -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-xs relative overflow-hidden">
        <div class="absolute -top-16 -right-16 w-56 h-56 bg-blue-100/40 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col sm:flex-row items-center sm:items-start gap-5">
            <!-- Avatar Bulat -->
            <div class="relative shrink-0">
                <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl sm:rounded-3xl bg-gradient-to-br from-[#0b3d82] to-blue-700 text-white font-black text-2xl sm:text-3xl flex items-center justify-center shadow-md shadow-blue-900/20 border-2 border-white">
                    @if(!empty($user->avatar))
                        <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-full h-full object-cover rounded-2xl sm:rounded-3xl">
                    @else
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    @endif
                </div>
                <span class="absolute -bottom-1 -right-1 w-5 h-5 bg-emerald-500 border-2 border-white rounded-full" title="Akun Aktif"></span>
            </div>

            <!-- Identitas Ringkas -->
            <div class="flex-1 text-center sm:text-left min-w-0">
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-display truncate">
                        {{ $user->name }}
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                        CAMABA
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    {{ $user->email }}
                </p>

                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3 mt-3 text-xs text-slate-600">
                    <div class="flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span>{{ $user->phone ?: '-' }}</span>
                    </div>

                    @if($prospek?->no_registrasi)
                        <div class="flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                            <span class="text-slate-400 font-bold text-[10px]">NO REG:</span>
                            <span class="font-mono font-bold text-slate-800">{{ $prospek->no_registrasi }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Pintasan ke Form Pendaftaran -->
            <div class="shrink-0 flex flex-col gap-2 w-full sm:w-auto">
                <a href="{{ route('pmb.akun.form-pendaftaran') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-[#0b3d82] hover:bg-blue-900 text-white shadow-xs transition active:scale-95">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Form Pendaftaran PMB</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Grid 2 Kolom: Data Akun & Ganti Password -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 1. Form Edit Data Profil Akun -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0b3d82] flex items-center justify-center shrink-0 border border-blue-100">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 font-display">Informasi Akun</h2>
                        <p class="text-[11px] text-slate-500">Perbarui identitas dasar dan nomor kontak aktif Anda</p>
                    </div>
                </div>

                <form action="{{ route('pmb.akun.profil.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                               class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50/70 text-slate-800 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                        @error('name')
                            <p class="text-[11px] text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Akun (Tetap)</label>
                        <input type="email" value="{{ $user->email }}" disabled
                               class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-100/90 text-slate-500 cursor-not-allowed">
                        <p class="text-[10px] text-slate-400 mt-1">Alamat email digunakan sebagai ID login utama Anda.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor WhatsApp / HP</label>
                        <input type="text" name="whatsapp" value="{{ old('whatsapp', $user->phone) }}" required placeholder="+6281234567890"
                               class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50/70 text-slate-800 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                        <p class="text-[10px] text-slate-400 mt-1">Gunakan format internasional: +628xxxxxxxx</p>
                        @error('whatsapp')
                            <p class="text-[11px] text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Foto Profil / Avatar (Opsional)</label>
                        <input type="file" name="avatar" accept="image/png,image/jpeg,image/jpg"
                               class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-[#0b3d82] hover:file:bg-blue-100 cursor-pointer">
                        @error('avatar')
                            <p class="text-[11px] text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full py-2.5 rounded-xl text-xs font-bold bg-[#0b3d82] hover:bg-blue-900 text-white shadow-xs transition cursor-pointer">
                            Simpan Perubahan Profil
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Form Ganti Password -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 font-display">Ganti Password</h2>
                        <p class="text-[11px] text-slate-500">Perbarui kata sandi untuk menjaga keamanan akun Anda</p>
                    </div>
                </div>

                <form action="{{ route('pmb.akun.profil.password') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Password Saat Ini</label>
                        <input type="password" name="current_password" required placeholder="Masukkan password saat ini"
                               class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50/70 text-slate-800 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                        @error('current_password')
                            <p class="text-[11px] text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Password Baru</label>
                        <input type="password" name="password" required placeholder="Minimal 8 karakter"
                               class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50/70 text-slate-800 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                        @error('password')
                            <p class="text-[11px] text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" required placeholder="Ulangi password baru"
                               class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50/70 text-slate-800 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full py-2.5 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-xs transition cursor-pointer">
                            Perbarui Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Status Pendaftaran PMB Card -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-xs">
        <div class="flex items-center justify-between gap-4 flex-wrap pb-4 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-black text-slate-900 font-display">Status Pendaftaran PMB</h3>
                <p class="text-xs text-slate-500">Ringkasan status pendaftaran dan kelengkapan formulir Anda</p>
            </div>
            <a href="{{ route('pmb.akun.dashboard') }}" class="text-xs text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1">
                <span>Lihat Alur Pendaftaran di Dashboard</span>
                <span>→</span>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-[11px] font-bold text-slate-400 block mb-1">Status Formulir</span>
                @if(($prospek?->status_formulir ?? '') === 'Sudah Isi')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Sudah Diisi Lengkap</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span>Belum Mengisi Formulir</span>
                    </span>
                @endif
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-[11px] font-bold text-slate-400 block mb-1">Program Studi Pilihan</span>
                <span class="text-xs font-extrabold text-slate-800 block truncate">
                    {{ $prospek?->prodi?->nama ?: 'Belum Memilih Jurusan' }}
                </span>
                <span class="text-[10px] text-slate-500 mt-0.5 block">
                    Kelas: {{ $prospek?->kelas ?: 'Reguler' }}
                </span>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-[11px] font-bold text-slate-400 block mb-1">Status Pembayaran</span>
                @if(($prospek?->status ?? '') === 'LUNAS')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span>Lunas</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-300">
                        <span>{{ $prospek?->status ?? 'BARU' }}</span>
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
