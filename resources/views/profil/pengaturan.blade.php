@php
    $pageTitle = 'Pengaturan CRM';
    $pageSubtitle = 'Preferensi Notifikasi & Keamanan';
@endphp

<x-app-layout :title="'Pengaturan - CRM UCIC'">

    <div class="space-y-6 max-w-4xl mx-auto">

        <!-- Header -->
        <div class="crm-card bg-white p-6">
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Pengaturan Akun & Sistem</h2>
            <p class="text-xs text-slate-500 mt-1">Sesuaikan preferensi notifikasi follow up dan keamanan akun Anda.</p>
        </div>

        <!-- Notification Settings -->
        <div class="crm-card bg-white p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">Preferensi Notifikasi</h3>

            <div class="space-y-3 text-xs">
                <label class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 transition cursor-pointer">
                    <div>
                        <div class="font-bold text-slate-800">Notifikasi Jadwal Follow-up Harian</div>
                        <div class="text-slate-500 text-[11px]">Kirim reminder WhatsApp dan pop-up saat ada jadwal follow-up hari ini</div>
                    </div>
                    <input type="checkbox" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                </label>

                <label class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 transition cursor-pointer">
                    <div>
                        <div class="font-bold text-slate-800">Alert Takeover Prospek Baru</div>
                        <div class="text-slate-500 text-[11px]">Beri tahu segera ketika CS atau SPV melimpahkan prospek baru ke saya</div>
                    </div>
                    <input type="checkbox" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                </label>

                <label class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 transition cursor-pointer">
                    <div>
                        <div class="font-bold text-slate-800">Notifikasi Target & Closing</div>
                        <div class="text-slate-500 text-[11px]">Pemberitahuan real-time setiap kali pembayaran termin 1 berhasil divalidasi</div>
                    </div>
                    <input type="checkbox" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                </label>
            </div>
        </div>

        <!-- Security & Password -->
        <div class="crm-card bg-white p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">Keamanan & Password</h3>

            <form action="{{ route('profil.password.update') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Password Saat Ini *</label>
                        <input type="password" name="current_password" required placeholder="••••••••••••" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Password Baru *</label>
                        <input type="password" name="password" required placeholder="Minimal 8 karakter" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Password Baru *</label>
                        <input type="password" name="password_confirmation" required placeholder="Ulangi password baru" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 outline-none">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                        Update Password
                    </button>
                </div>
            </form>
        </div>

        @if(auth()->check() && strtolower(auth()->user()->role) === 'admin')
        <!-- PENGATURAN HALAMAN TERIMA KASIH (Khusus Admin) -->
        <div class="crm-card bg-white p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">Pengaturan Halaman Terima Kasih <span class="bg-red-100 text-red-600 px-2 py-0.5 rounded text-[10px] ml-2">Admin Only</span></h3>
            
            <form action="{{ route('pengaturan.update-terima-kasih') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Halaman *</label>
                    <input type="text" name="title" value="{{ $tkTitle ? $tkTitle->nama : 'Terima Kasih!' }}" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sub Judul / Pesan (Opsional)</label>
                    <textarea name="subtitle" rows="3" placeholder="Data kamu berhasil dikirim. Tim kami akan menghubungi kamu melalui WhatsApp untuk informasi selanjutnya." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">{{ $tkSubtitle ? $tkSubtitle->nama : '' }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Foto Background / Banner (Opsional)</label>
                    @if(isset($tkImage) && $tkImage->nama)
                        <div class="mb-3 p-3 bg-slate-50 border border-slate-200 rounded-xl inline-block w-full">
                            <img src="{{ asset('storage/' . $tkImage->nama) }}" alt="Terima Kasih Image" class="h-32 object-contain rounded-lg border border-slate-200 bg-white mb-3">
                            <label class="flex items-center gap-2 cursor-pointer text-red-600 font-semibold hover:text-red-700 w-max">
                                <input type="checkbox" name="delete_image" value="1" class="rounded border-red-300 text-red-600 focus:ring-red-500 w-4 h-4">
                                Hapus foto ini
                            </label>
                        </div>
                    @endif
                    <input type="file" name="image" accept="image/*" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                    <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
        @endif

    </div>

</x-app-layout>
