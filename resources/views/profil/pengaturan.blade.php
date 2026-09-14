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

    </div>

</x-app-layout>
