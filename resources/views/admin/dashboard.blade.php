@php
    $pageTitle = 'Dashboard Administrator';
    $pageSubtitle = 'Status Server, Pengguna Sistem & Audit Integrasi CRM';
@endphp

<x-app-layout :title="'Dashboard Admin - CRM UCIC'">

    <!-- State Simulation Wrappers -->
    <div x-show="$store.crm.activeState === 'loading'" x-cloak>
        <x-loading-skeleton type="cards" />
        <div class="mt-6">
            <x-loading-skeleton type="table" />
        </div>
    </div>

    <div x-show="$store.crm.activeState === 'empty'" x-cloak>
        <x-empty-state 
            title="Sistem belum memiliki log aktivitas" 
            description="Aktivitas audit log dan metrik server akan muncul seiring dengan penggunaan aplikasi."
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak>
        <x-error-state />
    </div>

    <!-- NORMAL DATA STATE -->
    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6" x-data="{ modalTambahUser: false }">

        <!-- Header Greeting & Quick Admin Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Admin System Control Panel 🛡️</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">Super Administrator</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola perizinan akun tim, integrasi WhatsApp Gateway, dan master data penerimaan UCIC.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-2">
                <a 
                    href="{{ route('admin.master-data.index') }}"
                    class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition flex items-center gap-1.5 bg-white"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    <span>Master Data</span>
                </a>
                <a 
                    href="{{ route('admin.users.index') }}"
                    class="px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                    <span>+ Kelola Pengguna</span>
                </a>
            </div>
        </div>

        <!-- System Status & KPI Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
            <x-stat-card 
                title="Total User Tim" 
                :value="$systemStats['total_users'] . ' Akun'" 
                subtitle="Sales, CS, SPV, HM" 
                color="blue"
            />
            <x-stat-card 
                title="Sesi Aktif" 
                :value="$systemStats['active_sessions'] . ' Online'" 
                subtitle="Login Hari Ini" 
                color="emerald"
            />
            <x-stat-card 
                title="Total Prospek" 
                :value="$systemStats['total_prospects']" 
                subtitle="Database Inbound" 
                color="indigo"
            />
            <x-stat-card 
                title="WhatsApp Gateway" 
                value="Online" 
                subtitle="API Node-01 Active" 
                color="emerald"
            />
            <x-stat-card 
                title="Media Storage" 
                :value="$systemStats['storage_used']" 
                subtitle="Foto Kunjungan" 
                color="amber"
            />
            <x-stat-card 
                title="System Uptime" 
                :value="$systemStats['api_uptime']" 
                subtitle="Cloud Server" 
                color="purple"
            />
        </div>

        <!-- TWO COLUMN LAYOUT: WHATSAPP GATEWAY & MASTER DATA SHORTCUTS -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- WhatsApp Gateway Status Box -->
            <div class="crm-card bg-white p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">WhatsApp API Gateway</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded-md text-[10px] bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">Connected</span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Nomor Pengirim:</span>
                        <span class="font-bold text-slate-800">+62 812-2334-9988</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Pesan Terkirim Hari Ini:</span>
                        <span class="font-bold text-slate-800">148 Pesan</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Latency API:</span>
                        <span class="font-semibold text-emerald-600">120 ms</span>
                    </div>
                </div>

                <div class="pt-2 flex items-center gap-2">
                    <button 
                        type="button" 
                        @click="$store.crm.showToast('Koneksi WhatsApp Gateway berhasil di-test & aktif!')"
                        class="flex-1 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition cursor-pointer"
                    >
                        Test Koneksi
                    </button>
                    <a 
                        href="{{ route('admin.settings.index') }}"
                        class="px-3.5 py-2 rounded-xl bg-purple-50 text-purple-700 hover:bg-purple-100 font-semibold text-xs border border-purple-200 transition"
                    >
                        Konfigurasi
                    </a>
                </div>
            </div>

            <!-- User Roles Distribution -->
            <div class="crm-card bg-white p-6 lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Distribusi Hak Akses & Personil Tim</h3>
                        <p class="text-xs text-slate-500">Jumlah user aktif dan alokasi peran operasional CRM UCIC</p>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                        Kelola Semua Akun &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="p-3.5 rounded-xl border border-slate-200/80 bg-blue-50/40 space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700">Tim Sales</span>
                        <div class="text-2xl font-extrabold text-slate-900">4 User</div>
                        <p class="text-[11px] text-slate-500">Kunjungan & Closing</p>
                    </div>
                    <div class="p-3.5 rounded-xl border border-slate-200/80 bg-teal-50/40 space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-teal-800">Customer Service</span>
                        <div class="text-2xl font-extrabold text-slate-900">2 User</div>
                        <p class="text-[11px] text-slate-500">Follow-up & Inbound</p>
                    </div>
                    <div class="p-3.5 rounded-xl border border-slate-200/80 bg-indigo-50/40 space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700">Supervisor</span>
                        <div class="text-2xl font-extrabold text-slate-900">1 User</div>
                        <p class="text-[11px] text-slate-500">Evaluasi Performa</p>
                    </div>
                    <div class="p-3.5 rounded-xl border border-slate-200/80 bg-purple-50/40 space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700">Head Marketing</span>
                        <div class="text-2xl font-extrabold text-slate-900">1 User</div>
                        <p class="text-[11px] text-slate-500">Executive Strategy</p>
                    </div>
                </div>

                <!-- Database Backup Quick Info -->
                <div class="mt-4 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7M4 7c0-2 1.5-3 3.5-3h9c2 0 3.5 1 3.5 3M4 7h16m-5 4v6m-4-6v6" /></svg>
                        <span class="text-slate-600">Backup Otomatis Terakhir: <strong>Hari ini, 04:00 WIB (Berhasil)</strong></span>
                    </div>
                    <button @click="$store.crm.showToast('Snapshot backup database berhasil dibuat!')" class="text-xs font-bold text-blue-600 hover:text-blue-700 cursor-pointer">
                        Backup Sekarang
                    </button>
                </div>
            </div>

        </div>

        <!-- RECENT AUDIT TRAIL LOGS -->
        <div class="crm-card bg-white p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Audit Trail Log Terbaru</h3>
                    <p class="text-xs text-slate-500">Aktivitas real-time seluruh user dalam sistem CRM</p>
                </div>
                <a href="{{ route('admin.audit-logs.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                    Buka Log Lengkap &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3 px-4">Pengguna</th>
                            <th class="py-3 px-3">Role</th>
                            <th class="py-3 px-3">Jenis Aktivitas</th>
                            <th class="py-3 px-3">Target / Detail</th>
                            <th class="py-3 px-3">IP Address</th>
                            <th class="py-3 px-4 text-right">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($auditLogs as $log)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-bold text-slate-900">{{ $log['user'] }}</td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">{{ $log['role'] }}</span>
                                </td>
                                <td class="py-3.5 px-3 font-semibold text-blue-700">{{ $log['action'] }}</td>
                                <td class="py-3.5 px-3 text-slate-600">
                                    <div><strong class="text-slate-800">{{ $log['target'] }}</strong></div>
                                    <div class="text-[11px] text-slate-400">{{ $log['detail'] }}</div>
                                </td>
                                <td class="py-3.5 px-3 text-slate-400 text-[11px] font-mono">{{ $log['ip'] }}</td>
                                <td class="py-3.5 px-4 text-right text-slate-500 text-[11px]">{{ $log['time'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</x-app-layout>
