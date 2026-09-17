<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 text-slate-900 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'CRM Marketing & Sales Inbound UCIC' }}</title>
    <meta name="description" content="Platform CRM Modern Marketing & Sales Inbound Universitas Catur Insan Cendekia (UCIC)">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 font-sans text-slate-800" x-data="{ 
    sidebarCollapsed: false, 
    activeMobileSection: null,
    modalTambahProspek: false,
    modalFollowUp: false,
    modalUpdateStatus: false,
    modalTakeover: false,
    modalTambahKunjungan: false,
    selectedProspect: { name: 'SMK Negeri 1 Cirebon', status: 'Interested', id: 1, owner: 'Aurel Calista' },
    notificationOpen: false
}
"
x-init="
    @if(session('success')) setTimeout(() => $store.crm.showToast('{{ session('success') }}', 'success'), 100); @endif
    @if(session('error')) setTimeout(() => $store.crm.showToast('{{ session('error') }}', 'error'), 100); @endif
    @if($errors->any())
        @foreach($errors->all() as $error)
            setTimeout(() => $store.crm.showToast('{{ $error }}', 'error'), 100);
        @endforeach
    @endif
">

    <!-- Toast Notifications Container -->
    <div class="fixed top-5 right-5 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-4 sm:px-0">
        <template x-for="toast in $store.crm.toasts" :key="toast.id">
            <div 
                x-show="true"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="pointer-events-auto flex items-center justify-between p-4 rounded-xl bg-white border border-slate-200 shadow-lg text-sm text-slate-800"
            >
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 border"
                          :class="toast.type === 'error' ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100'">
                        <svg x-show="toast.type !== 'error'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg x-show="toast.type === 'error'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </span>
                    <span class="font-medium text-xs sm:text-sm" x-text="toast.message"></span>
                </div>
                <button @click="$store.crm.removeToast(toast.id)" class="text-slate-400 hover:text-slate-600 ml-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </template>
    </div>

    <!-- Main Container -->
    <div class="min-h-full flex flex-col md:flex-row">

        <!-- ============================================================== -->
        <!-- DESKTOP SIDEBAR (FOKUS 1 ROLE)                                 -->
        <!-- ============================================================== -->
        <aside 
            :class="sidebarCollapsed ? 'w-20' : 'w-64'"
            class="hidden md:flex flex-col flex-shrink-0 bg-white border-r border-slate-200/80 transition-all duration-300 ease-in-out fixed inset-y-0 left-0 z-30 shadow-xs"
        >
            <!-- Brand Header -->
            <div class="h-18 sm:h-20 flex items-center border-b border-slate-100 transition-all duration-300" :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between px-4 sm:px-5'">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0" title="UCIC CRM - {{ $currentUser['role_label'] }}">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-gradient-to-tr from-blue-700 via-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-sm shrink-0 tracking-wider">
                        U
                    </div>
                    <div x-show="!sidebarCollapsed" class="transition-opacity duration-200 min-w-0">
                        <div class="text-sm font-bold text-slate-900 tracking-tight leading-tight">UCIC CRM</div>
                        <div class="mt-1">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-blue-50 text-blue-700 font-semibold border border-blue-200 leading-none">
                                {{ $currentUser['role_label'] }}
                            </span>
                        </div>
                    </div>
                </a>
                <button 
                    x-show="!sidebarCollapsed"
                    @click="sidebarCollapsed = true" 
                    class="p-1.5 sm:p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition cursor-pointer shrink-0 ml-1"
                    title="Tutup Sidebar"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>
            </div>

            <!-- Expand Toggle Bar when Collapsed -->
            <div x-show="sidebarCollapsed" class="py-2.5 flex justify-center border-b border-slate-100 bg-slate-50/50">
                <button 
                    @click="sidebarCollapsed = false" 
                    class="p-2 rounded-xl text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition cursor-pointer"
                    title="Buka Sidebar"
                >
                    <svg class="w-5 h-5 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 overflow-y-auto" :class="sidebarCollapsed ? 'px-2 py-3 space-y-3' : 'px-3 py-4 space-y-5'">

                <!-- Section: DASHBOARD (Single Dedicated Dashboard) -->
                <div>
                    <div x-show="!sidebarCollapsed" class="px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Menu Utama</div>
                    <div class="space-y-1">
                        <a 
                            href="{{ route('dashboard') }}" 
                            title="Dashboard"
                            class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('dashboard*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Dashboard</span>
                        </a>
                    </div>
                </div>

                @if($currentUser['role'] === 'Admin')
                    <div x-show="sidebarCollapsed" class="w-8 mx-auto border-t border-slate-100"></div>

                    <!-- Section: ADMIN PANEL (Hanya untuk Admin) -->
                    <div>
                        <div x-show="!sidebarCollapsed" class="px-3 mb-2 text-[11px] font-bold text-purple-700 uppercase tracking-wider">Admin Control</div>
                        <div class="space-y-1">
                            <a 
                                href="{{ route('admin.users.index') }}" 
                                title="Kelola Pengguna"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.users.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.users.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Kelola Pengguna</span>
                            </a>
                            <a 
                                href="{{ route('admin.kunjungan.index') }}" 
                                title="Kelola Kunjungan"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.kunjungan.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.kunjungan.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Kelola Kunjungan</span>
                            </a>
                            <a 
                                href="{{ route('admin.target.index') }}" 
                                title="Kelola Target"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.target.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.target.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Kelola Target</span>
                            </a>
                        </div>
                    </div>

                    <div x-show="sidebarCollapsed" class="w-8 mx-auto border-t border-slate-100"></div>

                    <!-- Section: DATA MASTER -->
                    <div>
                        <div x-show="!sidebarCollapsed" class="px-3 mb-2 text-[11px] font-bold text-purple-700 uppercase tracking-wider">Data Master</div>
                        <div class="space-y-1">
                            <a 
                                href="{{ route('admin.master-data.index') }}" 
                                title="Data Master"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.master-data.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.master-data.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Data Master</span>
                            </a>
                            <a 
                                href="{{ route('admin.wilayah.index') }}" 
                                title="Data Wilayah"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.wilayah.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.wilayah.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Data Wilayah</span>
                            </a>
                            <a 
                                href="{{ route('admin.sekolah.index') }}" 
                                title="Data Sekolah"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.sekolah.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.sekolah.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Data Sekolah</span>
                            </a>
                            <a 
                                href="{{ route('admin.prodi.index') }}" 
                                title="Data Prodi"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.prodi.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.prodi.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Data Prodi</span>
                            </a>
                            <a 
                                href="{{ route('admin.perusahaan.index') }}" 
                                title="Data Perusahaan"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.perusahaan.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.perusahaan.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Data Perusahaan</span>
                            </a>
                        </div>
                    </div>
                @else
                    <div x-show="sidebarCollapsed" class="w-8 mx-auto border-t border-slate-100"></div>

                    <!-- Section: CRM CORE (Sales, CS, SPV, HM) -->
                    <div>
                        <div x-show="!sidebarCollapsed" class="px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">CRM Inbound</div>
                        <div class="space-y-1">
                            <a 
                                href="{{ route('prospek.index') }}" 
                                title="Prospek ({{ $globalProspekCount ?? 0 }})"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('prospek.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('prospek.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Prospek</span>
                                <span x-show="!sidebarCollapsed" class="ml-auto px-1.5 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 font-medium">{{ $globalProspekCount ?? 0 }}</span>
                            </a>
                            
                            @if($currentUser['role'] !== 'CS')
                                <a 
                                    href="{{ route('kunjungan.index') }}" 
                                    title="Kunjungan"
                                    class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('kunjungan.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                    :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                                >
                                    <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('kunjungan.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span x-show="!sidebarCollapsed">Kunjungan</span>
                                </a>
                            @endif

                            <a 
                                href="{{ route('follow-up.index') }}" 
                                title="Follow Up ({{ $globalFollowUpTodayCount ?? 0 }} Hari Ini)"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('follow-up.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <div class="relative flex items-center justify-center">
                                    <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('follow-up.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    @if(isset($globalFollowUpTodayCount) && $globalFollowUpTodayCount > 0)
                                    <span x-show="sidebarCollapsed" class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-amber-500"></span>
                                    @endif
                                </div>
                                <span x-show="!sidebarCollapsed">Follow Up</span>
                                <span x-show="!sidebarCollapsed" class="ml-auto px-1.5 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-800 font-semibold">{{ $globalFollowUpTodayCount ?? 0 }} Hari Ini</span>
                            </a>

                            <a 
                                href="{{ route('pipeline.index') }}" 
                                title="Pipeline Board"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('pipeline.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('pipeline.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Pipeline Board</span>
                            </a>
                        </div>
                    </div>

                    <div x-show="sidebarCollapsed" class="w-8 mx-auto border-t border-slate-100"></div>

                    <!-- Section: PERFORMANCE -->
                    <div>
                        <div x-show="!sidebarCollapsed" class="px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Performance</div>
                        <div class="space-y-1">
                            <a 
                                href="{{ route('performa.index') }}" 
                                title="Target & Performa"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('performa.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('performa.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Target & Performa</span>
                            </a>
                            <a 
                                href="{{ route('laporan.index') }}" 
                                title="Laporan"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('laporan.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('laporan.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Laporan</span>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Section: MANAGEMENT (Head Marketing) -->
                @if($currentUser['role'] === 'Head Marketing')
                    <div x-show="sidebarCollapsed" class="w-8 mx-auto border-t border-slate-100 mt-5"></div>
                    <div class="mt-5">
                        <div x-show="!sidebarCollapsed" class="px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Management</div>
                        <div class="space-y-1">
                            <a 
                                href="{{ route('wilayah.index') }}" 
                                title="Kelola Wilayah"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('wilayah.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('wilayah.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Kelola Wilayah</span>
                            </a>
                            <a 
                                href="{{ route('tim.index') }}" 
                                title="Kelola Tim"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('tim.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('tim.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Kelola Tim</span>
                            </a>
                        </div>
                    </div>
                @endif

                <div x-show="sidebarCollapsed" class="w-8 mx-auto border-t border-slate-100 mt-5"></div>

                <!-- Section: ACCOUNT -->
                <div>
                    <div x-show="!sidebarCollapsed" class="px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Account</div>
                    <div class="space-y-1">
                        <a 
                            href="{{ route('profil.index') }}" 
                            title="Profil"
                            class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('profil.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('profil.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Profil</span>
                        </a>
                        <a 
                            href="{{ route('pengaturan.index') }}" 
                            title="Pengaturan"
                            class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('pengaturan.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('pengaturan.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Pengaturan</span>
                        </a>
                    </div>
                </div>

            </div>

            <!-- User Card Bottom (Menampilkan User yang Sedang Login) -->
            <div class="border-t border-slate-100 bg-slate-50/50" :class="sidebarCollapsed ? 'p-2' : 'p-3'">
                <div class="flex items-center rounded-xl transition" :class="sidebarCollapsed ? 'flex-col justify-center gap-2 p-1' : 'gap-3 p-2 hover:bg-white'">
                    <a href="{{ route('profil.index') }}" title="{{ $currentUser['name'] }} ({{ $currentUser['role_label'] }})" class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-semibold flex items-center justify-center text-xs shrink-0 shadow-xs hover:ring-2 hover:ring-blue-500/30 transition">
                        {{ $currentUser['avatar'] }}
                    </a>
                    <div x-show="!sidebarCollapsed" class="truncate flex-1 min-w-0">
                        <div class="text-xs font-bold text-slate-800 truncate">{{ $currentUser['name'] }}</div>
                        <div class="text-[10px] text-blue-600 font-semibold truncate mt-0.5" title="{{ $currentUser['role_label'] }}">{{ $currentUser['role_label'] }}</div>
                    </div>
                    <button 
                        type="button" 
                        onclick="confirmLogout()" 
                        title="Logout / Ganti Akun" 
                        class="text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition p-2 rounded-xl cursor-pointer shrink-0" 
                        :class="sidebarCollapsed ? 'rounded-lg hover:bg-rose-50' : ''"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- ============================================================== -->
        <!-- MAIN CONTENT AREA                                              -->
        <!-- ============================================================== -->
        <div 
            :class="sidebarCollapsed ? 'md:pl-20' : 'md:pl-64'"
            class="flex-1 flex flex-col min-w-0 transition-all duration-300 ease-in-out pb-mobile-nav md:pb-8"
        >
            
            <!-- TOPBAR (Desktop & Mobile) - TANPA MENU PINDAH ROLE -->
            <header class="sticky top-0 z-20 bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-4 sm:px-8 py-3.5 sm:py-4 min-h-[4.5rem] sm:min-h-[5rem] flex items-center justify-between shadow-xs">
                
                <!-- Left: Mobile Brand Logo / Desktop Page Title -->
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <div class="md:hidden flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-700 to-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                            U
                        </div>
                        <div>
                            <span class="font-bold text-slate-900 text-sm block leading-tight">CRM UCIC</span>
                            <span class="text-[10px] text-blue-600 font-semibold truncate block">{{ $currentUser['role_label'] }}</span>
                        </div>
                    </div>

                    <div class="hidden md:block">
                        <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight leading-snug">{{ $pageTitle ?? 'CRM Marketing & Sales' }}</h1>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">{{ $pageSubtitle ?? 'Universitas Catur Insan Cendekia' }}</p>
                    </div>
                </div>

                <!-- Right: Quick Tools & User Profile -->
                <div class="flex items-center gap-2.5 sm:gap-4">

                    <!-- Search Trigger / Modal Button -->
                    @if($currentUser['role'] !== 'Admin' && $currentUser['role'] !== 'Head Marketing')
                        <button 
                            @click="modalTambahProspek = true" 
                            class="inline-flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 sm:py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="font-semibold"><span class="hidden sm:inline">Tambah </span>Prospek</span>
                        </button>
                    @endif

                    <!-- Notifications Dropdown -->
                    <div class="relative" x-data="{ 
                            open: false,
                            unreadCount: {{ isset($globalUnreadNotifications) ? $globalUnreadNotifications->count() : 0 }},
                            markAllRead() {
                                fetch('{{ route('notifications.markAllRead') }}', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        'Accept': 'application/json'
                                    }
                                }).then(res => {
                                    if(res.ok) {
                                        this.unreadCount = 0;
                                        document.querySelectorAll('.unread-dot').forEach(el => el.classList.add('hidden'));
                                    }
                                });
                            }
                        }">
                    <!-- Notifications Dropdown (Interactive) -->
                    <div class="relative" x-data="{ open: false, filter: 'all' }">
                        <button 
                            @click="open = !open" 
                            class="p-2 sm:p-2.5 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100/80 transition relative cursor-pointer"
                            title="Notifikasi"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span x-show="unreadCount > 0" class="absolute top-2 right-2 flex items-center justify-center min-w-[14px] h-[14px] rounded-full bg-rose-500 text-[9px] font-bold text-white ring-2 ring-white px-[3px]" x-text="unreadCount"></span>
                            <template x-if="$store.crm.unreadCount > 0">
                                <span class="absolute top-1.5 right-1.5 min-w-4 h-4 px-1 rounded-full bg-rose-500 ring-2 ring-white text-[9px] font-bold text-white flex items-center justify-center animate-pulse" x-text="$store.crm.unreadCount"></span>
                            </template>
                        </button>

                        <div 
                            x-show="open" 
                            @click.away="open = false" 
                            x-cloak
                            x-transition:enter="transition ease-out duration-150 transform"
                            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100 transform"
                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                            class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200/90 py-2 z-50 overflow-hidden"
                        >
                            <div class="px-4 py-2 border-b border-slate-100 flex items-center justify-between">
                                <span class="font-bold text-xs text-slate-800">Notifikasi Terbaru</span>
                                <span x-show="unreadCount > 0" @click="markAllRead()" class="text-[10px] text-blue-600 font-semibold cursor-pointer hover:underline">Tandai Dibaca</span>
                            </div>
                            <div class="divide-y divide-slate-100 max-h-64 overflow-y-auto">
                                @if(isset($globalUnreadNotifications) && $globalUnreadNotifications->count() > 0)
                                    @foreach($globalUnreadNotifications as $notif)
                                    <div class="px-4 py-3 hover:bg-slate-50 transition cursor-pointer relative">
                                        <div class="absolute left-2 top-4 w-1.5 h-1.5 rounded-full bg-blue-500 unread-dot"></div>
                                        <div class="pl-2">
                                            <p class="text-xs font-semibold text-slate-800">{{ $notif->data['title'] ?? 'Notifikasi Baru' }}</p>
                                            <p class="text-[11px] text-slate-500 mt-0.5">{{ $notif->data['message'] ?? '' }}</p>
                                            <p class="text-[10px] text-slate-400 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                    @endforeach
                                @else
                                    <div class="px-4 py-6 text-center text-slate-500 text-xs">
                                        Tidak ada notifikasi baru
                                    </div>
                                @endif
                            <!-- Dropdown Header -->
                            <div class="px-4 py-2.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-xs text-slate-900">Notifikasi</span>
                                    <template x-if="$store.crm.unreadCount > 0">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700" x-text="$store.crm.unreadCount + ' Belum Dibaca'"></span>
                                    </template>
                                </div>
                                <button 
                                    @click="$store.crm.markAllAsRead()" 
                                    x-show="$store.crm.unreadCount > 0"
                                    class="text-[11px] text-blue-600 hover:text-blue-800 font-semibold cursor-pointer transition"
                                >
                                    Tandai Semua Dibaca
                                </button>
                            </div>

                            <!-- Filter Tabs -->
                            <div class="flex border-b border-slate-100 bg-white px-2 py-1 gap-1 text-[11px]">
                                <button 
                                    @click="filter = 'all'" 
                                    :class="filter === 'all' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-800'"
                                    class="px-2.5 py-1 rounded-lg transition cursor-pointer"
                                >
                                    Semua (<span x-text="$store.crm.notifications.length"></span>)
                                </button>
                                <button 
                                    @click="filter = 'unread'" 
                                    :class="filter === 'unread' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-800'"
                                    class="px-2.5 py-1 rounded-lg transition cursor-pointer"
                                >
                                    Belum Dibaca (<span x-text="$store.crm.unreadCount"></span>)
                                </button>
                            </div>

                            <!-- Items List -->
                            <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                                <template x-for="item in $store.crm.notifications.filter(n => filter === 'all' || !n.read)" :key="item.id">
                                    <div 
                                        @click="$store.crm.markAsRead(item.id); if(item.link) window.location.href = item.link;"
                                        class="px-4 py-3 hover:bg-slate-50 transition cursor-pointer flex items-start justify-between gap-3 group"
                                        :class="!item.read ? 'bg-blue-50/40' : ''"
                                    >
                                        <div class="flex items-start gap-2.5 flex-1 min-w-0">
                                            <span 
                                                class="w-2 h-2 rounded-full mt-1.5 shrink-0" 
                                                :class="{
                                                    'bg-blue-600': !item.read && item.type === 'info',
                                                    'bg-amber-500': !item.read && item.type === 'warning',
                                                    'bg-emerald-500': !item.read && item.type === 'success',
                                                    'bg-rose-500': !item.read && item.type === 'danger',
                                                    'bg-transparent': item.read
                                                }"
                                            ></span>
                                            <div>
                                                <p class="text-xs font-bold text-slate-800 leading-snug group-hover:text-blue-600 transition" x-text="item.title"></p>
                                                <p class="text-[11px] text-slate-500 mt-0.5 leading-normal" x-text="item.message"></p>
                                                <span class="text-[10px] text-slate-400 mt-1 block" x-text="item.time"></span>
                                            </div>
                                        </div>
                                        <button 
                                            @click.stop="$store.crm.deleteNotification(item.id)" 
                                            class="text-slate-300 hover:text-rose-600 p-1 opacity-0 group-hover:opacity-100 transition rounded-lg hover:bg-rose-50 cursor-pointer"
                                            title="Hapus notifikasi"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </template>

                                <template x-if="$store.crm.notifications.filter(n => filter === 'all' || !n.read).length === 0">
                                    <div class="p-6 text-center text-slate-400 space-y-1">
                                        <svg class="w-8 h-8 mx-auto text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                        <p class="text-xs font-semibold text-slate-600">Tidak Ada Notifikasi</p>
                                        <p class="text-[11px] text-slate-400">Semua pemberitahuan sudah Anda periksa.</p>
                                    </div>
                                </template>
                            </div>

                            <!-- Dropdown Footer -->
                            <div class="p-2 border-t border-slate-100 bg-slate-50/50 text-center">
                                <a href="{{ route('admin.audit-logs.index') }}" class="text-[11px] font-bold text-slate-600 hover:text-purple-700 transition">
                                    Lihat Log Aktivitas Sistem &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- User Avatar Pill (Header) -->
                    <div class="flex items-center gap-3 pl-3 sm:pl-4 border-l border-slate-200/80">
                        <a href="{{ route('profil.index') }}" title="Lihat Profil" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold flex items-center justify-center text-xs sm:text-sm shadow-xs hover:ring-2 hover:ring-blue-500/30 transition">
                            {{ $currentUser['avatar'] }}
                        </a>
                        <div class="hidden sm:block text-left">
                            <div class="text-xs font-bold text-slate-900 leading-snug">{{ $currentUser['name'] }}</div>
                            <div class="text-[10px] text-blue-600 font-semibold mt-0.5">{{ $currentUser['role_label'] }}</div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- MAIN PAGE SLOT -->
            <main class="flex-1 px-4 sm:px-6 lg:px-8 py-7 sm:py-8 max-w-7xl w-full mx-auto">
                {{ $slot }}
            </main>

        </div>

        <!-- ============================================================== -->
        <!-- MOBILE SUBMENU POPUP SHEETS (Triggered from Parent Sections)   -->
        <!-- ============================================================== -->
        
        <!-- Backdrop Overlay -->
        <div 
            x-show="activeMobileSection !== null" 
            x-cloak 
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-35 md:hidden"
            @click="activeMobileSection = null"
        ></div>

        <!-- Popup Bottom Sheet Container -->
        <div 
            x-show="activeMobileSection !== null" 
            x-cloak 
            x-transition:enter="transition ease-out duration-250 transform"
            x-transition:enter-start="opacity-0 translate-y-8 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150 transform"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-8 scale-95"
            class="fixed inset-x-3 bottom-[4.5rem] z-40 md:hidden bg-white rounded-2xl shadow-2xl border border-slate-200/90 overflow-hidden max-h-[75vh] flex flex-col"
            @click.away="activeMobileSection = null"
        >
            <!-- 1. POPUP FOR CRM INBOUND -->
            <template x-if="activeMobileSection === 'crm_inbound'">
                <div class="flex flex-col">
                    <!-- Sheet Header -->
                    <div class="px-4 py-3 border-b border-slate-100 bg-blue-50/60 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900">CRM Inbound</h4>
                                <p class="text-[10px] text-slate-500">Pilih menu aktivitas prospek & pipeline</p>
                            </div>
                        </div>
                        <button @click="activeMobileSection = null" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-white transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- Submenu Links Grid -->
                    <div class="p-3 space-y-1.5 overflow-y-auto max-h-[60vh]">
                        <a 
                            href="{{ route('prospek.index') }}" 
                            class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('prospek.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Daftar Prospek</div>
                                    <div class="text-[10px] text-slate-500">Database prospek & kontak</div>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-blue-100 text-blue-800 font-bold">142</span>
                        </a>

                        @if($currentUser['role'] !== 'CS')
                            <a 
                                href="{{ route('kunjungan.index') }}" 
                                class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('kunjungan.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-slate-800">Laporan Kunjungan</div>
                                        <div class="text-[10px] text-slate-500">Dokumentasi audiensi offline</div>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        @endif

                        <a 
                            href="{{ route('follow-up.index') }}" 
                            class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('follow-up.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Follow Up</div>
                                    <div class="text-[10px] text-slate-500">Jadwal interaksi harian prospek</div>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-800 font-bold">16 Hari Ini</span>
                        </a>

                        <a 
                            href="{{ route('pipeline.index') }}" 
                            class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('pipeline.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" /></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Pipeline Board</div>
                                    <div class="text-[10px] text-slate-500">Kanban tahapan closing siswa</div>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </template>

            <!-- 2. POPUP FOR PERFORMANCE -->
            <template x-if="activeMobileSection === 'performance'">
                <div class="flex flex-col">
                    <div class="px-4 py-3 border-b border-slate-100 bg-blue-50/60 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                            </span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900">Performance</h4>
                                <p class="text-[10px] text-slate-500">Target pencapaian & laporan analitik</p>
                            </div>
                        </div>
                        <button @click="activeMobileSection = null" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-white transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="p-3 space-y-1.5 overflow-y-auto max-h-[60vh]">
                        <a 
                            href="{{ route('performa.index') }}" 
                            class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('performa.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Target & Performa</div>
                                    <div class="text-[10px] text-slate-500">KPI closing & progres target tim</div>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>

                        <a 
                            href="{{ route('laporan.index') }}" 
                            class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('laporan.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Laporan Analitik</div>
                                    <div class="text-[10px] text-slate-500">Rekap konversi & export data</div>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </template>

            <!-- 3. POPUP FOR ADMIN CONTROL -->
            <template x-if="activeMobileSection === 'admin_control'">
                <div class="flex flex-col">
                    <div class="px-4 py-3 border-b border-slate-100 bg-purple-50/60 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-purple-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </span>
                            <div>
                                <h4 class="text-xs font-bold text-purple-900">Admin Control</h4>
                                <p class="text-[10px] text-purple-600 font-medium">Manajemen user, target, & audit log</p>
                            </div>
                        </div>
                        <button @click="activeMobileSection = null" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-white transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="p-3 space-y-1.5 overflow-y-auto max-h-[60vh]">
                        <a 
                            href="{{ route('admin.users.index') }}" 
                            class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('admin.users.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Kelola Pengguna</div>
                                    <div class="text-[10px] text-slate-500">Akun Sales, CS, SPV, & Head Marketing</div>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>

                        <a 
                            href="{{ route('admin.kunjungan.index') }}" 
                            class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('admin.kunjungan.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Kelola Kunjungan</div>
                                    <div class="text-[10px] text-slate-500">Monitoring laporan offline tim sales</div>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>

                        <a 
                            href="{{ route('admin.target.index') }}" 
                            class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('admin.target.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Kelola Target Tim</div>
                                    <div class="text-[10px] text-slate-500">Setting target bulanan personil</div>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>

                        <a 
                            href="{{ route('admin.audit-logs.index') }}" 
                            class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('admin.audit-logs.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Audit Logs</div>
                                    <div class="text-[10px] text-slate-500">Histori aktivitas login & sistem</div>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </template>

            <!-- 4. POPUP FOR DATA MASTER -->
            <template x-if="activeMobileSection === 'master_data'">
                <div class="flex flex-col">
                    <div class="px-4 py-3 border-b border-slate-100 bg-purple-50/60 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-purple-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            </span>
                            <div>
                                <h4 class="text-xs font-bold text-purple-900">Data Master</h4>
                                <p class="text-[10px] text-purple-600 font-medium">Database referensi wilayah, sekolah, & prodi</p>
                            </div>
                        </div>
                        <button @click="activeMobileSection = null" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-white transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="p-3 space-y-1.5 overflow-y-auto max-h-[60vh]">
                        <a 
                            href="{{ route('admin.master-data.index') }}" 
                            class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('admin.master-data.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Semua Data Master</div>
                                    <div class="text-[10px] text-slate-500">Pusat ringkasan master data</div>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>

                        <a 
                            href="{{ route('admin.wilayah.index') }}" 
                            class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('admin.wilayah.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Data Wilayah</div>
                                    <div class="text-[10px] text-slate-500">Zona dan cakupan wilayah sales</div>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>

                        <a 
                            href="{{ route('admin.sekolah.index') }}" 
                            class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('admin.sekolah.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" /></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Data Sekolah</div>
                                    <div class="text-[10px] text-slate-500">Daftar SMA / SMK / MA mitra</div>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>

                        <a 
                            href="{{ route('admin.prodi.index') }}" 
                            class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('admin.prodi.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Data Program Studi</div>
                                    <div class="text-[10px] text-slate-500">Fakultas & program studi UCIC</div>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>

                        <a 
                            href="{{ route('admin.perusahaan.index') }}" 
                            class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('admin.perusahaan.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Data Perusahaan</div>
                                    <div class="text-[10px] text-slate-500">Mitra industri & kelas karyawan</div>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </template>

            <!-- 5. POPUP FOR ACCOUNT -->
            <template x-if="activeMobileSection === 'account'">
                <div class="flex flex-col">
                    <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-slate-800 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900">Account</h4>
                                <p class="text-[10px] text-slate-500">Profil & preferensi akun login</p>
                            </div>
                        </div>
                        <button @click="activeMobileSection = null" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-white transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="p-3 space-y-1.5 overflow-y-auto max-h-[60vh]">
                        <a 
                            href="{{ route('profil.index') }}" 
                            class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('profil.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Profil Saya</div>
                                    <div class="text-[10px] text-slate-500">{{ $currentUser['name'] }} ({{ $currentUser['role_label'] }})</div>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>

                        <a 
                            href="{{ route('pengaturan.index') }}" 
                            class="flex items-center justify-between p-3 rounded-xl transition {{ request()->routeIs('pengaturan.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-200' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-700 border border-slate-100' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-200 text-slate-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Pengaturan Sistem</div>
                                    <div class="text-[10px] text-slate-500">Notifikasi & preferensi akun</div>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>

                        <div class="pt-2 border-t border-slate-100">
                            <button 
                                type="button" 
                                onclick="activeMobileSection = null; confirmLogout()"
                                class="w-full flex items-center justify-between p-3 rounded-xl bg-rose-50/60 hover:bg-rose-100/80 text-rose-700 transition border border-rose-100 cursor-pointer text-left"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-rose-800">Ganti Akun / Logout</div>
                                        <div class="text-[10px] text-rose-500">Keluar dari sesi akun saat ini</div>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- ============================================================== -->
        <!-- MOBILE FIXED BOTTOM NAVIGATION (Parent Sections)               -->
        <!-- ============================================================== -->
        <nav class="md:hidden fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-md border-t border-slate-200/90 z-40 px-2 py-2 pb-4 shadow-xl">
            @if($currentUser['role'] === 'Admin')
                <div class="grid grid-cols-4 gap-1 items-center">
                    <!-- 1. Dashboard -->
                    <a 
                        href="{{ route('dashboard') }}" 
                        @click="activeMobileSection = null"
                        class="flex flex-col items-center justify-center py-1.5 rounded-xl transition cursor-pointer relative"
                        :class="(activeMobileSection === null && {{ request()->routeIs('dashboard*') ? 'true' : 'false' }}) ? 'text-purple-700 font-bold bg-purple-50/90' : 'text-slate-500 hover:text-slate-900'"
                    >
                        <svg class="w-5 h-5" :class="(activeMobileSection === null && {{ request()->routeIs('dashboard*') ? 'true' : 'false' }}) ? 'text-purple-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span class="text-[10px] mt-0.5 font-medium">Dashboard</span>
                    </a>

                    <!-- 2. Admin Control -->
                    <button 
                        type="button" 
                        @click="activeMobileSection = activeMobileSection === 'admin_control' ? null : 'admin_control'"
                        class="flex flex-col items-center justify-center py-1.5 rounded-xl transition cursor-pointer relative"
                        :class="(activeMobileSection === 'admin_control' || (activeMobileSection === null && {{ (request()->routeIs('admin.users.*') || request()->routeIs('admin.kunjungan.*') || request()->routeIs('admin.target.*') || request()->routeIs('admin.audit-logs.*')) ? 'true' : 'false' }})) ? 'text-purple-700 font-bold bg-purple-50/90' : 'text-slate-500 hover:text-slate-900'"
                    >
                        <svg class="w-5 h-5" :class="(activeMobileSection === 'admin_control' || (activeMobileSection === null && {{ (request()->routeIs('admin.users.*') || request()->routeIs('admin.kunjungan.*') || request()->routeIs('admin.target.*') || request()->routeIs('admin.audit-logs.*')) ? 'true' : 'false' }})) ? 'text-purple-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span class="text-[10px] mt-0.5 font-medium">Admin Control</span>
                    </button>

                    <!-- 3. Data Master -->
                    <button 
                        type="button" 
                        @click="activeMobileSection = activeMobileSection === 'master_data' ? null : 'master_data'"
                        class="flex flex-col items-center justify-center py-1.5 rounded-xl transition cursor-pointer relative"
                        :class="(activeMobileSection === 'master_data' || (activeMobileSection === null && {{ (request()->routeIs('admin.master-data.*') || request()->routeIs('admin.wilayah.*') || request()->routeIs('admin.sekolah.*') || request()->routeIs('admin.prodi.*') || request()->routeIs('admin.perusahaan.*')) ? 'true' : 'false' }})) ? 'text-purple-700 font-bold bg-purple-50/90' : 'text-slate-500 hover:text-slate-900'"
                    >
                        <svg class="w-5 h-5" :class="(activeMobileSection === 'master_data' || (activeMobileSection === null && {{ (request()->routeIs('admin.master-data.*') || request()->routeIs('admin.wilayah.*') || request()->routeIs('admin.sekolah.*') || request()->routeIs('admin.prodi.*') || request()->routeIs('admin.perusahaan.*')) ? 'true' : 'false' }})) ? 'text-purple-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span class="text-[10px] mt-0.5 font-medium">Data Master</span>
                    </button>

                    <!-- 4. Account -->
                    <button 
                        type="button" 
                        @click="activeMobileSection = activeMobileSection === 'account' ? null : 'account'"
                        class="flex flex-col items-center justify-center py-1.5 rounded-xl transition cursor-pointer relative"
                        :class="(activeMobileSection === 'account' || (activeMobileSection === null && {{ (request()->routeIs('profil.*') || request()->routeIs('pengaturan.*')) ? 'true' : 'false' }})) ? 'text-purple-700 font-bold bg-purple-50/90' : 'text-slate-500 hover:text-slate-900'"
                    >
                        <svg class="w-5 h-5" :class="(activeMobileSection === 'account' || (activeMobileSection === null && {{ (request()->routeIs('profil.*') || request()->routeIs('pengaturan.*')) ? 'true' : 'false' }})) ? 'text-purple-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-[10px] mt-0.5 font-medium">Account</span>
                    </button>
                </div>
            @else
                <div class="grid grid-cols-4 gap-1 items-center">
                    <!-- 1. Dashboard -->
                    <a 
                        href="{{ route('dashboard') }}" 
                        @click="activeMobileSection = null"
                        class="flex flex-col items-center justify-center py-1.5 rounded-xl transition cursor-pointer relative"
                        :class="(activeMobileSection === null && {{ request()->routeIs('dashboard*') ? 'true' : 'false' }}) ? 'text-blue-600 font-bold bg-blue-50/90' : 'text-slate-500 hover:text-slate-900'"
                    >
                        <svg class="w-5 h-5" :class="(activeMobileSection === null && {{ request()->routeIs('dashboard*') ? 'true' : 'false' }}) ? 'text-blue-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span class="text-[10px] mt-0.5 font-medium">Dashboard</span>
                    </a>

                    <!-- 2. CRM Inbound -->
                    <button 
                        type="button" 
                        @click="activeMobileSection = activeMobileSection === 'crm_inbound' ? null : 'crm_inbound'"
                        class="flex flex-col items-center justify-center py-1.5 rounded-xl transition cursor-pointer relative"
                        :class="(activeMobileSection === 'crm_inbound' || (activeMobileSection === null && {{ (request()->routeIs('prospek.*') || request()->routeIs('kunjungan.*') || request()->routeIs('follow-up.*') || request()->routeIs('pipeline.*')) ? 'true' : 'false' }})) ? 'text-blue-600 font-bold bg-blue-50/90' : 'text-slate-500 hover:text-slate-900'"
                    >
                        <svg class="w-5 h-5" :class="(activeMobileSection === 'crm_inbound' || (activeMobileSection === null && {{ (request()->routeIs('prospek.*') || request()->routeIs('kunjungan.*') || request()->routeIs('follow-up.*') || request()->routeIs('pipeline.*')) ? 'true' : 'false' }})) ? 'text-blue-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="text-[10px] mt-0.5 font-medium">CRM Inbound</span>
                    </button>

                    <!-- 3. Performance -->
                    <button 
                        type="button" 
                        @click="activeMobileSection = activeMobileSection === 'performance' ? null : 'performance'"
                        class="flex flex-col items-center justify-center py-1.5 rounded-xl transition cursor-pointer relative"
                        :class="(activeMobileSection === 'performance' || (activeMobileSection === null && {{ (request()->routeIs('performa.*') || request()->routeIs('laporan.*')) ? 'true' : 'false' }})) ? 'text-blue-600 font-bold bg-blue-50/90' : 'text-slate-500 hover:text-slate-900'"
                    >
                        <svg class="w-5 h-5" :class="(activeMobileSection === 'performance' || (activeMobileSection === null && {{ (request()->routeIs('performa.*') || request()->routeIs('laporan.*')) ? 'true' : 'false' }})) ? 'text-blue-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        <span class="text-[10px] mt-0.5 font-medium">Performance</span>
                    </button>

                    <!-- 4. Account -->
                    <button 
                        type="button" 
                        @click="activeMobileSection = activeMobileSection === 'account' ? null : 'account'"
                        class="flex flex-col items-center justify-center py-1.5 rounded-xl transition cursor-pointer relative"
                        :class="(activeMobileSection === 'account' || (activeMobileSection === null && {{ (request()->routeIs('profil.*') || request()->routeIs('pengaturan.*')) ? 'true' : 'false' }})) ? 'text-blue-600 font-bold bg-blue-50/90' : 'text-slate-500 hover:text-slate-900'"
                    >
                        <svg class="w-5 h-5" :class="(activeMobileSection === 'account' || (activeMobileSection === null && {{ (request()->routeIs('profil.*') || request()->routeIs('pengaturan.*')) ? 'true' : 'false' }})) ? 'text-blue-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-[10px] mt-0.5 font-medium">Account</span>
                    </button>
                </div>
            @endif
        </nav>

    </div>

    <!-- ============================================================== -->
    <!-- GLOBAL REUSABLE MODALS                                         -->
    <!-- ============================================================== -->

    <!-- MODAL 1: TAMBAH PROSPEK -->
    <div 
        x-show="modalTambahProspek" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog" 
        aria-modal="true"
    >
        <div class="flex items-end sm:items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="modalTambahProspek" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalTambahProspek = false"></div>

            <div 
                x-show="modalTambahProspek" 
                class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10 max-h-[90vh] overflow-y-auto"
            >
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Tambah Prospek Baru</h3>
                        <p class="text-xs text-slate-500">Input prospek inbound baru untuk sekolah, corporate, atau individu.</p>
                    </div>
                    <button @click="modalTambahProspek = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form action="{{ route('prospek.store') }}" method="POST" class="mt-5 space-y-6">
                    @csrf
                    <!-- Section: INFORMASI DASAR -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-blue-700 bg-blue-50 px-2.5 py-1 rounded-md inline-block mb-3">1. Informasi Dasar</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Prospek (Sekolah / Instansi / Individu) *</label>
                                <input type="text" name="name" required placeholder="Contoh: SMA Negeri 1 Cirebon" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Prospek *</label>
                                <select name="type" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                    <option value="Sekolah">Sekolah (SMA/SMK/MA)</option>
                                    <option value="Corporate">Corporate / Perusahaan</option>
                                    <option value="Individu">Individu / Siswa Langsung</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Status Awal</label>
                                <select name="status" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                    <option value="Cold Lead">Cold Lead</option>
                                    <option value="Interested" selected>Interested</option>
                                    <option value="Follow Up">Follow Up</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Kontak / Nama PIC *</label>
                                <input type="text" name="pic" required placeholder="Contoh: Bpk. Bambang (Guru BK)" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp *</label>
                                <input type="tel" name="whatsapp" required placeholder="081234567890" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                            </div>
                        </div>
                    </div>

                    <!-- Section: TAKEOVER -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-md inline-block mb-3">2. Takeover Penugasan</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @php
                                $salesList = \App\Models\User::where('role', 'Sales')->get();
                                $csList = \App\Models\User::where('role', 'CS')->get();
                            @endphp
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Takeover by Sales</label>
                                <select name="sales_id" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                    <option value="">-- Pilih Sales --</option>
                                    @foreach($salesList as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Takeover by CS</label>
                                <select name="cs_id" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                    <option value="">-- Pilih CS --</option>
                                    @foreach($csList as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section: DETAIL & POTENSI -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 bg-slate-100 px-2.5 py-1 rounded-md inline-block mb-3">3. Detail & Potensi</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Potensi Prospek</label>
                                <input type="text" name="potential" placeholder="Contoh: 100 Siswa Jurusan RPL & TKJ potensi beasiswa UCIC" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan</label>
                                <textarea name="notes" rows="2" placeholder="Catatan awal hasil perbincangan atau sumber prospek..." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button type="button" @click="modalTambahProspek = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition cursor-pointer">Simpan Prospek</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: FOLLOW UP -->
    <div 
        x-show="modalFollowUp" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog" 
        aria-modal="true"
    >
        <div class="flex items-end sm:items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="modalFollowUp" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalFollowUp = false"></div>

            <div class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Catat Follow Up</h3>
                        <p class="text-xs text-slate-500" x-text="'Prospek: ' + selectedProspect.name"></p>
                    </div>
                    <button @click="modalFollowUp = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form action="{{ route('follow-up.store') }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="prospek_id" :value="selectedProspect.id">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Hasil Follow Up *</label>
                        <select name="hasil" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                            <option value="Tertarik & Minta Brosur/Proposal">Tertarik & Minta Brosur/Proposal</option>
                            <option value="Jadwalkan Kunjungan/Audiensi">Jadwalkan Kunjungan/Audiensi</option>
                            <option value="Beli Formulir PMDK">Beli Formulir PMDK</option>
                            <option value="Belum Respon / Menunggu">Belum Respon / Menunggu</option>
                            <option value="Kurang Berminat">Kurang Berminat (Simpan Arsip)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Follow Up *</label>
                        <textarea name="catatan" rows="3" required placeholder="Tuliskan hasil diskusi, pertanyaan prospek, atau kesepakatan..." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Next Follow Up</label>
                            <input name="next_follow_up" type="date" value="{{ date('Y-m-d', strtotime('+3 days')) }}" class="w-full text-xs sm:text-sm px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Update Status</label>
                            <select name="status" class="w-full text-xs sm:text-sm px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                <option value="Interested">Interested</option>
                                <option value="Follow Up" selected>Follow Up</option>
                                <option value="Beli Formulir">Beli Formulir</option>
                                <option value="Pembayaran Termin 1">Pembayaran Termin 1</option>
                                <option value="Closing">Closing</option>
                                <option value="Lost">Lost</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="modalFollowUp = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-xs">Simpan Follow Up</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 3: UPDATE STATUS -->
    <div 
        x-show="modalUpdateStatus" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog" 
        aria-modal="true"
    >
        <div class="flex items-end sm:items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="modalUpdateStatus" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalUpdateStatus = false"></div>

            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Update Status Prospek</h3>
                        <p class="text-xs text-slate-500" x-text="'Current Status: ' + selectedProspect.status"></p>
                    </div>
                    <button @click="modalUpdateStatus = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form @submit.prevent="modalUpdateStatus = false; $store.crm.showToast('Status berhasil diperbarui!')" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Status Baru *</label>
                        <select class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                            <option value="Cold Lead">Cold Lead (Neutral/Gray)</option>
                            <option value="Interested">Interested (Blue)</option>
                            <option value="Follow Up">Follow Up (Yellow/Orange)</option>
                            <option value="Beli Formulir">Beli Formulir (Purple)</option>
                            <option value="Pembayaran Termin 1">Pembayaran Termin 1 (Indigo)</option>
                            <option value="Closing">Closing (Green)</option>
                            <option value="Lost">Lost (Red - Arsip Historis)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Perubahan *</label>
                        <textarea rows="3" required placeholder="Tuliskan alasan atau ringkasan mengapa status prospek diperbarui..." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="modalUpdateStatus = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-xs">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 4: TAKEOVER UI -->
    <div 
        x-show="modalTakeover" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog" 
        aria-modal="true"
    >
        <div class="flex items-end sm:items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="modalTakeover" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalTakeover = false"></div>

            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Takeover Prospek</h3>
                        <p class="text-xs text-slate-500" x-text="selectedProspect.name"></p>
                    </div>
                    <button @click="modalTakeover = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form @submit.prevent="modalTakeover = false; $store.crm.showToast('Takeover berhasil diperbarui!')" class="mt-4 space-y-4" x-data="{ takeoverTarget: 'sales' }">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-2">Pilih Jenis Takeover</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button 
                                type="button" 
                                @click="takeoverTarget = 'sales'"
                                :class="takeoverTarget === 'sales' ? 'bg-blue-50 border-blue-600 text-blue-700 font-bold' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                                class="p-3 text-xs rounded-xl border text-center transition flex flex-col items-center gap-1 cursor-pointer"
                            >
                                <span class="font-bold">Takeover Sales</span>
                                <span class="text-[10px] text-slate-500">Kunjungan & EduFair</span>
                            </button>
                            <button 
                                type="button" 
                                @click="takeoverTarget = 'cs'"
                                :class="takeoverTarget === 'cs' ? 'bg-teal-50 border-teal-600 text-teal-800 font-bold' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                                class="p-3 text-xs rounded-xl border text-center transition flex flex-col items-center gap-1 cursor-pointer"
                            >
                                <span class="font-bold">Takeover CS</span>
                                <span class="text-[10px] text-slate-500">Follow-up WA & Call</span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Personil PIC *</label>
                        <select class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                            <template x-if="takeoverTarget === 'sales'">
                                <optgroup label="Tim Sales">
                                    <option value="Aurel Calista">Aurel Calista (Senior Sales)</option>
                                    <option value="Rizky Pratama">Rizky Pratama (Sales Representative)</option>
                                    <option value="Budi Santoso">Budi Santoso (Sales Representative)</option>
                                </optgroup>
                            </template>
                            <template x-if="takeoverTarget === 'cs'">
                                <optgroup label="Tim Customer Service">
                                    <option value="Dina Marlina">Dina Marlina (CS Lead)</option>
                                    <option value="Rini Anggraini">Rini Anggraini (CS Support)</option>
                                </optgroup>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Takeover (Opsional)</label>
                        <textarea rows="2" placeholder="Catatan instruksi untuk penanggung jawab baru..." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="modalTakeover = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-xs">Simpan Takeover</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 5: TAMBAH KUNJUNGAN (NO GPS / MAPS - FOTO DOKUMENTASI ONLY) -->
    <div 
        x-show="modalTambahKunjungan" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog" 
        aria-modal="true"
    >
        <div class="flex items-end sm:items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="modalTambahKunjungan" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalTambahKunjungan = false"></div>

            <div 
                x-show="modalTambahKunjungan"
                class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10 max-h-[90vh] overflow-y-auto"
                x-data="{ kunjunganType: 'sekolah', photoPreview: null }"
            >
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900">Tambah Laporan Kunjungan</h3>
                        <p class="text-xs text-slate-500">Catat dokumentasi kunjungan offline Sales UCIC.</p>
                    </div>
                    <button @click="modalTambahKunjungan = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Type Selector Tabs -->
                <div class="flex border-b border-slate-200 mt-4 mb-5">
                    <button 
                        type="button" 
                        @click="kunjunganType = 'sekolah'" 
                        :class="kunjunganType === 'sekolah' ? 'border-blue-600 text-blue-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-4 py-2 text-xs sm:text-sm font-semibold transition cursor-pointer"
                    >
                        Form Kunjungan Sekolah
                    </button>
                    <button 
                        type="button" 
                        @click="kunjunganType = 'corporate'" 
                        :class="kunjunganType === 'corporate' ? 'border-blue-600 text-blue-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-4 py-2 text-xs sm:text-sm font-semibold transition cursor-pointer"
                    >
                        Form Kunjungan Corporate
                    </button>
                </div>

                <form @submit.prevent="modalTambahKunjungan = false; $store.crm.showToast('Laporan kunjungan berhasil disimpan!')" class="space-y-5">
                    
                    <!-- FORM SEKOLAH -->
                    <template x-if="kunjunganType === 'sekolah'">
                        <div class="space-y-4">
                            <!-- Section: INFORMASI SEKOLAH -->
                            <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70 space-y-3">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Informasi Sekolah</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Sekolah *</label>
                                        <input type="text" required placeholder="Contoh: SMA Negeri 1 Cirebon" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Sekolah</label>
                                        <input type="text" placeholder="Jl. Pemuda No. 45, Cirebon" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                </div>
                            </div>

                            <!-- Section: PIC / BK -->
                            <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70 space-y-3">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">PIC / Guru BK</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama PIC / BK *</label>
                                        <input type="text" required placeholder="Nama guru / kepala sekolah" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp PIC *</label>
                                        <input type="tel" required placeholder="08xxxxxxxxxx" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                </div>
                            </div>

                            <!-- Section: POTENSI -->
                            <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70 space-y-3">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Potensi Kerjasama</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Potensi Beasiswa</label>
                                        <input type="text" placeholder="Jumlah kuota / estimasi siswa" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kesediaan Training AI/Robotics</label>
                                        <select class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                            <option value="Bersedia Bulan Ini">Bersedia Bulan Ini</option>
                                            <option value="Bersedia Semester Depan">Bersedia Semester Depan</option>
                                            <option value="Belum Bersedia">Belum Bersedia</option>
                                        </select>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Detail Potensi</label>
                                        <input type="text" placeholder="Contoh: Minat tinggi pada Prodi Teknik Informatika & Bisnis Digital" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- FORM CORPORATE -->
                    <template x-if="kunjunganType === 'corporate'">
                        <div class="space-y-4">
                            <!-- Section: INFORMASI PERUSAHAAN -->
                            <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70 space-y-3">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Informasi Perusahaan</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Perusahaan *</label>
                                        <input type="text" required placeholder="PT Contoh Solusi" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Bidang Usaha</label>
                                        <input type="text" placeholder="Contoh: Logistik / IT / Manufaktur" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                </div>
                            </div>

                            <!-- Section: PIC / HRD -->
                            <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70 space-y-3">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">PIC / HRD</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama HRD / PIC *</label>
                                        <input type="text" required placeholder="Ibu Maya (HR Manager)" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">WhatsApp HRD *</label>
                                        <input type="tel" required placeholder="08xxxxxxxxxx" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                </div>
                            </div>

                            <!-- Section: POTENSI CORPORATE -->
                            <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70 space-y-3">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Potensi Program</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Potensi S1/S2 Karyawan</label>
                                        <input type="text" placeholder="Estimasi peserta kelas karyawan" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Potensi CSR / Hibah</label>
                                        <input type="text" placeholder="Program beasiswa CSR perusahaan" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Section: DOKUMENTASI FOTO (NO GPS) -->
                    <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70 space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Dokumentasi Foto Kunjungan</h4>
                            <span class="text-[11px] text-slate-400">Hanya foto (Tanpa GPS)</span>
                        </div>

                        <div class="border-2 border-dashed border-slate-200 hover:border-blue-400 bg-white rounded-xl p-4 text-center transition">
                            <template x-if="!photoPreview">
                                <div>
                                    <svg class="mx-auto h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <div class="mt-2 text-xs text-slate-600">
                                        <label class="relative cursor-pointer rounded-md font-semibold text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                            <span>Upload foto dokumentasi</span>
                                            <input type="file" accept="image/*" class="sr-only" @change="
                                                const file = $event.target.files[0];
                                                if (file) {
                                                    const reader = new FileReader();
                                                    reader.onload = (e) => { photoPreview = e.target.result; };
                                                    reader.readAsDataURL(file);
                                                }
                                            ">
                                        </label>
                                        <span class="text-slate-400 block mt-0.5">PNG, JPG hingga 10MB</span>
                                    </div>
                                </div>
                            </template>

                            <template x-if="photoPreview">
                                <div class="relative inline-block">
                                    <img :src="photoPreview" class="max-h-40 rounded-lg shadow-xs object-cover mx-auto" alt="Preview foto">
                                    <button type="button" @click="photoPreview = null" class="absolute -top-2 -right-2 bg-rose-500 text-white rounded-full p-1 shadow-md hover:bg-rose-600">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Section: CATATAN -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Kunjungan *</label>
                        <textarea rows="2" required placeholder="Ringkasan hasil pertemuan dan tindak lanjut..." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button type="button" @click="modalTambahKunjungan = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition">Simpan Kunjungan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Global Logout Form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
        @csrf
    </form>

    <script>
        // Fallback global confirmLogout function
        if (typeof window.confirmLogout !== 'function') {
            window.confirmLogout = function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Konfirmasi Logout',
                        text: 'Apakah Anda yakin ingin keluar dari sistem CRM?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e11d48',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Ya, Logout',
                        cancelButtonText: 'Batal',
                        reverseButtons: true,
                        customClass: {
                            popup: 'rounded-2xl',
                            confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2.5',
                            cancelButton: 'rounded-xl text-xs font-semibold px-4 py-2.5'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('logout-form')?.submit();
                        }
                    });
                } else {
                    if (confirm('Apakah Anda yakin ingin logout?')) {
                        document.getElementById('logout-form')?.submit();
                    }
                }
            };
        }
    </script>

</body>
</html>
