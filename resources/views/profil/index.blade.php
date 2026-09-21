@php
    $user = $currentUser ?? \App\Http\Controllers\CrmController::getCurrentUser(request());
    $pageTitle = 'Profil & Akun Pengguna';
    $pageSubtitle = 'Pengaturan Akun & Preferensi Sistem';
@endphp

<x-app-layout :title="'Profil & Akun - CRM UCIC'">

    <div class="space-y-6 max-w-4xl mx-auto">

        <!-- Mobile App-Like Header & Profile Card -->
        <div class="crm-card bg-white p-6 flex flex-col sm:flex-row items-center gap-5 text-center sm:text-left shadow-xs rounded-2xl border border-slate-200/80">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-purple-600 via-blue-600 to-indigo-600 text-white font-bold text-2xl flex items-center justify-center shadow-md shrink-0">
                {{ $user['avatar'] }}
            </div>
            <div class="space-y-1 flex-1">
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                    <h2 class="text-xl font-bold text-slate-900">{{ $user['name'] }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $user['role'] === 'Admin' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                        {{ $user['role_label'] }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 font-medium">{{ $user['email'] }} &bull; NIK: {{ $user['nik'] }}</p>
                <p class="text-xs text-slate-600 pt-1">{{ $user['division'] }}</p>
            </div>
            <div>
                <button type="button" onclick="confirmLogout()" class="px-4 py-2 rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 font-semibold text-xs border border-rose-200 transition inline-flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    <span>Logout</span>
                </button>
            </div>
        </div>

        <!-- Mobile Navigation Menu Cards (Accessible on mobile) -->
        <div class="crm-card bg-white divide-y divide-slate-100 overflow-hidden shadow-xs rounded-2xl border border-slate-200/80">
            <div class="p-4 bg-slate-50/50">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Akses Cepat & Menu Aplikasi</span>
            </div>

            @if($user['role'] === 'Admin')
                <!-- Link: Kelola User -->
                <a href="{{ route('admin.users.index') }}" class="flex items-center justify-between p-4 hover:bg-slate-50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </div>
                        <div>
                            <div class="text-xs sm:text-sm font-bold text-slate-800">Kelola Pengguna CRM</div>
                            <div class="text-[11px] text-slate-500">Manajemen akun user, role, dan hak akses</div>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>

                <!-- Link: Master Data -->
                <a href="{{ route('admin.master-data.index') }}" class="flex items-center justify-between p-4 hover:bg-slate-50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        </div>
                        <div>
                            <div class="text-xs sm:text-sm font-bold text-slate-800">Kelola Master Data</div>
                            <div class="text-[11px] text-slate-500">Data Sekolah, Perusahaan, Prodi, Wilayah, & Kategori</div>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>
            @else
                <!-- Link: Kunjungan -->
                <a href="{{ route((strtolower(auth()->user()->role ?? '') === 'spv' ? 'spv.' : '') . 'kunjungan.index') }}" class="flex items-center justify-between p-4 hover:bg-slate-50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                        <div>
                            <div class="text-xs sm:text-sm font-bold text-slate-800">Laporan Kunjungan Lapangan</div>
                            <div class="text-[11px] text-slate-500">Dokumentasi foto audiensi sekolah & corporate</div>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>

                <!-- Link: Laporan -->
                <a href="{{ route((strtolower(auth()->user()->role ?? '') === 'spv' ? 'spv.' : '') . 'laporan.index') }}" class="flex items-center justify-between p-4 hover:bg-slate-50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <div>
                            <div class="text-xs sm:text-sm font-bold text-slate-800">Laporan Rekapitulasi & Export</div>
                            <div class="text-[11px] text-slate-500">Rekap data pendaftaran dan pencapaian target</div>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>
            @endif

            <!-- Link: Pengaturan -->
            <a href="{{ route('pengaturan.index') }}" class="flex items-center justify-between p-4 hover:bg-slate-50 transition">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center border border-slate-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /></svg>
                    </div>
                    <div>
                        <div class="text-xs sm:text-sm font-bold text-slate-800">Pengaturan Sistem & Notifikasi</div>
                        <div class="text-[11px] text-slate-500">Konfigurasi akun dan preferensi alert WhatsApp</div>
                    </div>
                </div>
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </a>
        </div>

        <!-- Personal Details Form -->
        <div class="crm-card bg-white p-6 space-y-4 shadow-xs rounded-2xl border border-slate-200/80">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">Informasi Pribadi</h3>
            
            <form action="{{ route('profil.update') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                        <input type="text" name="name" value="{{ old('name', $user['name']) }}" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 focus:border-purple-400 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Email UCIC</label>
                        <input type="email" value="{{ $user['email'] }}" readonly class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp Pribadi</label>
                        <input type="tel" name="phone" value="{{ old('phone', $user['phone'] ?? '') }}" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 focus:border-purple-400 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Role / Jabatan</label>
                        <input type="text" value="{{ $user['role_label'] }}" readonly class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 cursor-not-allowed">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

    </div>

</x-app-layout>
