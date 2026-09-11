@php
    $pageTitle = 'Pengaturan Sistem & Integrasi';
    $pageSubtitle = 'Konfigurasi WhatsApp Gateway, Server Backup & API Key UCIC';
@endphp

<x-app-layout :title="'Pengaturan Sistem - CRM UCIC'">

    <div class="space-y-6 max-w-4xl mx-auto">

        <!-- Header -->
        <div class="crm-card bg-white p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Konfigurasi Gateway & Server</h2>
                <p class="text-xs text-slate-500 mt-1">Integrasi WhatsApp API outbound follow-up dan backup data kampus.</p>
            </div>
            <div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Semua Layanan Aktif
                </span>
            </div>
        </div>

        <!-- WHATSAPP API GATEWAY CONFIGURATION -->
        <div class="crm-card bg-white p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">WhatsApp API Gateway</h3>
                        <p class="text-xs text-slate-500">Koneksi pengiriman template pesan follow-up otomatis</p>
                    </div>
                </div>

                <form @submit.prevent="$store.crm.showToast('Pengaturan WhatsApp Gateway berhasil disimpan!')" class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Pengirim Resmi UCIC *</label>
                            <input type="text" value="+6281223349988" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">API Key Gateway *</label>
                            <input type="password" value="ucic_live_api_99882211aabbcc" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Webhook URL Follow-Up</label>
                            <input type="text" value="https://crm.cic.ac.id/api/v1/webhook/whatsapp" readonly class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-500">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <button 
                            type="button" 
                            @click="$store.crm.showToast('Pesan uji coba berhasil terkirim ke WhatsApp Admin!')"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 font-semibold text-xs hover:bg-slate-50 transition cursor-pointer"
                        >
                            Kirim Uji Coba Pesan
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                            Simpan Gateway
                        </button>
                    </div>
                </form>
            </div>

            <!-- BACKUP DATABASE & SYSTEM MAINTENANCE -->
            <div class="crm-card bg-white p-6 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">Backup Data & Pemeliharaan</h3>

                <div class="space-y-4 text-xs">
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="font-bold text-slate-900">Database Snapshot & Backup Harian</div>
                            <div class="text-slate-500 text-[11px]">Unduh dump file seluruh data prospek, kunjungan, dan follow-up</div>
                        </div>
                        <button 
                            type="button" 
                            @click="$store.crm.showToast('Snapshot backup database MySQL berhasil diunduh!')"
                            class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition cursor-pointer"
                        >
                            Unduh SQL Backup
                        </button>
                    </div>

                    <div class="p-4 rounded-xl bg-purple-50/60 border border-purple-200 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-purple-900">Mode Sinkronisasi Realtime</div>
                            <div class="text-purple-700 text-[11px]">Koneksi otomatis antara pendaftaran website UCIC dengan CRM</div>
                        </div>
                        <input type="checkbox" checked class="rounded border-purple-300 text-purple-600 focus:ring-purple-500 w-4 h-4">
                    </div>
                </div>
            </div>

    </div>

</x-app-layout>
