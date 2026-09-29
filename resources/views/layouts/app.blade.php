<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 text-slate-900 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'CRM Marketing & Sales Inbound UCIC' }}</title>
    <meta name="description" content="Platform CRM Modern Marketing & Sales Inbound Universitas Catur Insan Cendekia (UCIC)">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo-ucic.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-ucic.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- TomSelect CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        @media print {
            aside,
            header,
            nav,
            .no-print,
            .fixed,
            [class*="state-simulator"],
            button:not(.allow-print) {
                display: none !important;
            }

            html, body {
                background: #ffffff !important;
                color: #0f172a !important;
                font-size: 10pt !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .min-h-full, .min-h-screen, .md\:pl-64, .md\:pl-20 {
                padding-left: 0 !important;
                margin: 0 !important;
                min-height: auto !important;
            }

            main {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            .print-only {
                display: block !important;
            }

            .crm-card, .border, .shadow-xs, .shadow-sm, .shadow-md, .shadow-lg {
                box-shadow: none !important;
            }

            tr, .print-avoid-break {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            thead {
                display: table-header-group !important;
            }

            @page {
                size: A4 portrait;
                margin: 1.2cm 1cm 1.2cm 1cm;
            }
        }

        @media screen {
            .print-only {
                display: none !important;
            }
        }
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <script>
        window.__NOTIFICATIONS__ = @json($globalNotifications ?? []);
    </script>
    @php
        if (!isset($currentUser) && auth()->check()) {
            $u = auth()->user();
            $roleLabels = ['Admin' => 'Administrator', 'HM' => 'Head Marketing', 'SPV' => 'Supervisor', 'Sales' => 'Sales', 'CS' => 'Customer Service', 'EO' => 'Event Organizer'];
            $currentUser = [
                'name' => $u->name,
                'avatar' => substr($u->name, 0, 2),
                'role' => $u->role,
                'role_label' => $roleLabels[$u->role] ?? $u->role,
            ];
        }
        $rawRole = strtolower($currentUser['role'] ?? auth()->user()->role ?? 'sales');
        $userRole = in_array($rawRole, ['spv', 'supervisor', 'supervisor marketing']) ? 'spv' : (in_array($rawRole, ['hm', 'head marketing']) ? 'hm' : $rawRole);
        $routePrefix = $userRole === 'sales' ? 'sales.' : ($userRole === 'spv' ? 'spv.' : '');
    @endphp
</head>
<body class="h-full bg-slate-50 font-sans text-slate-800" x-data="{ 
    sidebarCollapsed: false, 
    mobileMenuOpen: false,
    modalTambahProspek: {{ (isset($errors) && $errors->any() && (old('pic') || old('type') || old('whatsapp') || old('source') || old('sekolah_id') || old('perusahaan_id') || old('sekolah_manual') || old('perusahaan_manual'))) ? 'true' : 'false' }},
    modalFollowUp: false,
    modalUpdateStatus: false,
    modalTransaksi: false,
    modalTambahKunjungan: false,
    selectedProspect: { name: 'SMK Negeri 1 Cirebon', status: 'Interested', id: 1, owner: 'Aurel Calista' },
    notificationOpen: false
}
"
x-init="
    $nextTick(() => {
        @if(session('success'))
            if (window.Alpine && Alpine.store('crm')) {
                Alpine.store('crm').showToast({!! json_encode(session('success')) !!}, 'success');
            }
        @endif
        @if(session('error'))
            if (window.Alpine && Alpine.store('crm')) {
                Alpine.store('crm').showToast({!! json_encode(session('error')) !!}, 'error');
            }
        @endif
        @if(isset($errors) && $errors->any())
            @foreach($errors->all() as $error)
                if (window.Alpine && Alpine.store('crm')) {
                    Alpine.store('crm').showToast({!! json_encode($error) !!}, 'error');
                }
            @endforeach
        @endif
    });
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
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0 group" title="UCIC CRM - {{ $currentUser['role_label'] }}">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-white border border-slate-200/80 flex items-center justify-center p-1.5 shadow-2xs shrink-0 group-hover:scale-105 transition-transform duration-200">
                        <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" class="w-full h-full object-contain">
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
                        <a 
                            href="{{ route('calendar.index') }}" 
                            title="Kalender"
                            class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('calendar.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('calendar.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Kalender Internal</span>
                        </a>
                        <a 
                            href="{{ route('infografis.index') }}" 
                            title="Infografis"
                            class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('infografis.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('infografis.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Infografis</span>
                        </a>
                    </div>
                </div>

                @if($currentUser['role'] === 'EO')
                    <div x-show="sidebarCollapsed" class="w-8 mx-auto border-t border-slate-100"></div>

                    <!-- Section: EVENT ORGANIZER -->
                    <div>
                        <div x-show="!sidebarCollapsed" class="px-3 mb-2 text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Event Management</div>
                        <div class="space-y-1">
                            <a 
                                href="{{ route('eo.events.index') }}" 
                                title="Kelola Event"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('eo.events.*') ? 'bg-emerald-50 text-emerald-700 font-bold border border-emerald-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('eo.events.*') ? 'text-emerald-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Kelola Event</span>
                            </a>
                            <a 
                                href="{{ route('eo.event-types.index') }}" 
                                title="Kelola Jenis Event"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('eo.event-types.*') ? 'bg-emerald-50 text-emerald-700 font-bold border border-emerald-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('eo.event-types.*') ? 'text-emerald-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Jenis Event</span>
                            </a>
                        </div>
                    </div>
                @endif


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
                                href="{{ route('admin.hm-wilayah.index') }}" 
                                title="Penugasan Wilayah HM"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.hm-wilayah.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.hm-wilayah.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Wilayah HM</span>
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
                            <a 
                                href="{{ route('admin.events.index') }}" 
                                title="Kelola Event"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.events.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.events.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Kelola Event</span>
                            </a>
                            <a 
                                href="{{ route('admin.event-types.index') }}" 
                                title="Kelola Jenis Event"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.event-types.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.event-types.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Jenis Event</span>
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
                                href="{{ route('potensi-wilayah.index') }}" 
                                title="Potensi Wilayah"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('potensi-wilayah.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('potensi-wilayah.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Potensi Wilayah</span>
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
                            <a 
                                href="{{ route('admin.tahun-akademik.index') }}" 
                                title="Tahun Akademik"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.tahun-akademik.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.tahun-akademik.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Tahun Akademik</span>
                            </a>
                                href="{{ route('admin.attendance-locations.index') }}" 
                                title="Lokasi Absensi"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.attendance-locations.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.attendance-locations.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Lokasi Absensi</span>
                            </a>
                            <a
                                href="{{ route('admin.bank-accounts.index') }}"
                                title="Rekening Bank"
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="rekening bank mandiri pembayaran transfer"
                                x-show="matches('rekening bank mandiri pembayaran transfer')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.bank-accounts.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.bank-accounts.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                <span class="flex-1">Rekening Bank</span>
                            </a>
                            <a 
                                href="{{ route('payments.index') }}" 
                                title="Tagihan & Pembayaran"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('payments.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('payments.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Tagihan & Payment</span>
                            </a>
                            <a 

                                href="{{ route('admin.audit-logs.index') }}" 
                                title="Audit Logs"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.audit-logs.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.audit-logs.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Audit Logs</span>
                            </a>
                        </div>
                    </div>
                @elseif($currentUser['role'] !== 'EO')
                    <div x-show="sidebarCollapsed" class="w-8 mx-auto border-t border-slate-100"></div>

                    <!-- Section: CRM CORE (Sales, CS, SPV, HM) -->
                    @php
                        $prospekMenuTitle = 'Prospek';
                        if ($userRole === 'sales') $prospekMenuTitle = 'Data Prospek';
                        if ($userRole === 'cs') $prospekMenuTitle = 'Data Kontak';
                    @endphp
                    <div>
                        <div x-show="!sidebarCollapsed" class="px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">CRM Inbound</div>
                        <div class="space-y-1">
                            <a 
                                href="{{ route($routePrefix . 'prospek.index') }}" 
                                title="{{ $prospekMenuTitle }} ({{ $globalProspekCount ?? 0 }})"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs($routePrefix . 'prospek.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs($routePrefix . 'prospek.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">{{ $prospekMenuTitle }}</span>
                                <span x-show="!sidebarCollapsed" class="ml-auto px-1.5 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 font-medium">{{ $globalProspekCount ?? 0 }}</span>
                            </a>
                            
                            @if($currentUser['role'] !== 'CS')
                                <a 
                                    href="{{ route($routePrefix . 'kunjungan.index') }}" 
                                    title="Kunjungan"
                                    class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs($routePrefix . 'kunjungan.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                    :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                                >
                                    <svg class="w-5 h-5 shrink-0 {{ request()->routeIs($routePrefix . 'kunjungan.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span x-show="!sidebarCollapsed">Kunjungan</span>
                                </a>
                            @endif

                            @if($userRole === 'sales')
                            <a 
                                href="{{ route('sales.follow-up.index') }}" 
                                title="Follow Up ({{ $globalFollowUpTodayCount ?? 0 }} Hari Ini)"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('sales.follow-up.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <div class="relative flex items-center justify-center">
                                    <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('sales.follow-up.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    @if(isset($globalFollowUpTodayCount) && $globalFollowUpTodayCount > 0)
                                    <span x-show="sidebarCollapsed" class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-amber-500"></span>
                                    @endif
                                </div>
                                <span x-show="!sidebarCollapsed">Follow Up</span>
                                <span x-show="!sidebarCollapsed" class="ml-auto px-1.5 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-800 font-semibold">{{ $globalFollowUpTodayCount ?? 0 }} Hari Ini</span>
                            </a>

                             {{-- Verifikasi Pembayaran untuk CS/Admin/HM --}}
                             @if(in_array($currentUser['role'], ['CS', 'Admin', 'HM']))
                             @php $pendingVerifikasiCount = \App\Models\Transaksi::where('jenis', 'Pembayaran Termin 1')->where('payment_status', 'pending')->count(); @endphp
                             <a
                                 href="{{ route('cs.verifikasi.index') }}"
                                 title="Verifikasi Pembayaran"
                                 class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('cs.verifikasi.*') ? 'bg-amber-50 text-amber-700 font-bold border border-amber-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                 :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                             >
                                 <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('cs.verifikasi.*') ? 'text-amber-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                 </svg>
                                 <span x-show="!sidebarCollapsed">Verifikasi Pembayaran</span>
                                 @if($pendingVerifikasiCount > 0)
                                     <span x-show="!sidebarCollapsed" class="ml-auto px-1.5 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-800 font-bold">{{ $pendingVerifikasiCount }}</span>
                                 @endif
                             </a>
                             @endif
                            <a 

                                href="{{ route('sales.event.index') }}" 
                                title="Jadwal Event"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('sales.event.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('sales.event.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span x-show="!sidebarCollapsed">Jadwal Event</span>
                            </a>
                            @elseif($userRole !== 'spv')
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
                                </div>
                                <span x-show="!sidebarCollapsed">Follow Up</span>
                            </a>
                            @endif

                            <a 
                                href="{{ route($routePrefix . 'pipeline.index') }}" 
                                title="Pipeline Board"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs($routePrefix . 'pipeline.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs($routePrefix . 'pipeline.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                href="{{ route($routePrefix . 'performa.index') }}" 
                                title="Target & Performa"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs($routePrefix . 'performa.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs($routePrefix . 'performa.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Target & Performa</span>
                            </a>
                            <a 
                                href="{{ route($routePrefix . 'laporan.index') }}" 
                                title="Laporan"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs($routePrefix . 'laporan.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs($routePrefix . 'laporan.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Laporan</span>
                            </a>
                        </div>
                    </div>

                    @if($userRole === 'spv')
                    <div x-show="sidebarCollapsed" class="w-8 mx-auto border-t border-slate-100"></div>

                    <!-- Section: TIM SPV -->
                    <div>
                        <div x-show="!sidebarCollapsed" class="px-3 mb-2 text-[11px] font-bold text-indigo-600 uppercase tracking-wider">Manajemen Tim</div>
                        <div class="space-y-1">
                            <a 
                                href="{{ route('spv.tim.index') }}" 
                                title="Kelola Sales"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('spv.tim.*') ? 'bg-indigo-50 text-indigo-700 font-bold border border-indigo-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('spv.tim.*') ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Kelola Sales</span>
                            </a>
                            <a 
                                href="{{ route('spv.events.index') }}" 
                                title="Assignment Event"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('spv.events.*') ? 'bg-indigo-50 text-indigo-700 font-bold border border-indigo-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('spv.events.*') ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Assignment Event</span>
                            </a>
                            <a 
                                href="{{ route('potensi-wilayah.index') }}" 
                                title="Potensi Wilayah"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('potensi-wilayah.*') ? 'bg-indigo-50 text-indigo-700 font-bold border border-indigo-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('potensi-wilayah.*') ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Potensi Wilayah</span>
                            </a>
                        </div>
                    </div>
                    @endif
                @endif

                <!-- Section: MANAGEMENT (Head Marketing) -->
                @if(in_array($currentUser['role'], ['HM', 'Head Marketing']))
                    <div x-show="sidebarCollapsed" class="w-8 mx-auto border-t border-slate-100 mt-5"></div>
                    <div class="mt-5">
                        <div x-show="!sidebarCollapsed" class="px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Management</div>
                        <div class="space-y-1">
                            <a 
                                href="{{ route('hm.wilayah.index') }}" 
                                title="Wilayah Saya"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('hm.wilayah.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('hm.wilayah.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Wilayah Saya</span>
                            </a>
                            <a 
                                href="{{ route('potensi-wilayah.index') }}" 
                                title="Potensi Wilayah"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('potensi-wilayah.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('potensi-wilayah.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Potensi Wilayah</span>
                            </a>

                            <a 
                                href="{{ route('hm.spv.index') }}" 
                                title="Kelola SPV"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('hm.spv.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('hm.spv.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Kelola SPV</span>
                            </a>

                            <a 
                                href="{{ route('hm.cs.index') }}" 
                                title="Kelola CS"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('hm.cs.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('hm.cs.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Kelola CS</span>
                            </a>



                            <a 
                                href="{{ route('admin.target.index') }}" 
                                title="Kelola Target"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.target.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.target.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Kelola Target</span>
                            </a>
                            <a 
                                href="{{ route('admin.audit-logs.index') }}" 
                                title="Log Aktivitas Sistem"
                                class="flex items-center rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.audit-logs.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                :class="sidebarCollapsed ? 'justify-center w-11 h-11 mx-auto p-0' : 'px-3 py-2.5 gap-3'"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.audit-logs.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span x-show="!sidebarCollapsed">Log Aktivitas</span>
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
                    <a href="{{ route('profil.index') }}" title="{{ $currentUser['name'] }} ({{ $currentUser['role_label'] }})" class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-semibold flex items-center justify-center text-xs shrink-0 shadow-xs hover:ring-2 hover:ring-blue-500/30 transition overflow-hidden">
                        @if(!empty($currentUser['avatar_url']))
                            <img src="{{ $currentUser['avatar_url'] }}" alt="{{ $currentUser['name'] }}" class="w-full h-full object-cover">
                        @else
                            {{ $currentUser['avatar'] }}
                        @endif
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
            class="flex-1 flex flex-col min-w-0 transition-all duration-300 ease-in-out pb-8"
        >
            
            <!-- TOPBAR (Desktop & Mobile) - TANPA MENU PINDAH ROLE -->
            <header class="sticky top-0 z-20 bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-4 sm:px-8 py-3.5 sm:py-4 min-h-[4.5rem] sm:min-h-[5rem] flex items-center justify-between shadow-xs">
                
                <!-- Left: Mobile Brand Logo / Desktop Page Title -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <div class="md:hidden flex items-center gap-2">
                        <button 
                            type="button" 
                            @click="mobileMenuOpen = true" 
                            class="p-2 -ml-1.5 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer"
                            title="Buka Menu Navigasi"
                            aria-label="Buka Menu"
                        >
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-white border border-slate-200/80 flex items-center justify-center p-1 shadow-2xs shrink-0">
                                <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" class="w-full h-full object-contain">
                            </div>
                            <div>
                                <span class="font-bold text-slate-900 text-sm block leading-tight">CRM UCIC</span>
                                <span class="text-[10px] text-blue-600 font-semibold truncate block">{{ $currentUser['role_label'] }}</span>
                            </div>
                        </a>
                    </div>

                    <div class="hidden md:block">
                        <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight leading-snug">{{ $pageTitle ?? 'CRM Marketing & Sales' }}</h1>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">{{ $pageSubtitle ?? 'Universitas Catur Insan Cendekia' }}</p>
                    </div>
                </div>

                <!-- Right: Quick Tools & User Profile -->
                <div class="flex items-center gap-2.5 sm:gap-4">

                    <!-- Notifications Dropdown -->
                    <div class="relative flex items-center justify-center" x-data="{ 
                            open: false,
                            filter: 'all',
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
                        <button 
                            @click="open = !open" 
                            class="p-2 sm:p-2.5 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100/80 transition relative cursor-pointer"
                            title="Notifikasi"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
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
                            class="absolute right-0 top-full mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200/90 py-2 z-50 overflow-hidden transform origin-top-right"
                        >
                            <!-- Dropdown Header -->
                            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-xs text-slate-900">Notifikasi Terbaru</span>
                                    <!-- Sound Controls -->
                                    <button 
                                        @click.stop="$store.crm.toggleSound()"
                                        type="button"
                                        class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition cursor-pointer"
                                        :title="$store.crm.soundEnabled ? 'Suara Aktif (Klik untuk mematikan)' : 'Suara Dimatikan (Klik untuk menyalakan)'"
                                    >
                                        <template x-if="$store.crm.soundEnabled">
                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                            </svg>
                                        </template>
                                        <template x-if="!$store.crm.soundEnabled">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                                            </svg>
                                        </template>
                                    </button>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button 
                                        @click.stop="$store.crm.testSound()" 
                                        type="button"
                                        class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100 transition cursor-pointer flex items-center gap-1"
                                        title="Uji coba suara lonceng notifikasi"
                                    >
                                        <span>🔔 Tes Suara</span>
                                    </button>
                                    <template x-if="$store.crm.unreadCount > 0">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700" x-text="$store.crm.unreadCount + ' Baru'"></span>
                                    </template>
                                    <button 
                                        @click="$store.crm.markAllAsRead()" 
                                        x-show="$store.crm.unreadCount > 0"
                                        class="text-[11px] text-blue-600 hover:text-blue-800 font-semibold cursor-pointer transition"
                                    >
                                        Tandai Dibaca
                                    </button>
                                </div>
                            </div>

                            <!-- Filter Tabs -->
                            <div class="flex border-b border-slate-100 bg-white px-2 py-2 gap-1 text-[11px]">
                                <button 
                                    @click="filter = 'all'" 
                                    :class="filter === 'all' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-800'"
                                    class="px-3 py-1.5 rounded-lg transition cursor-pointer"
                                >
                                    Semua (<span x-text="$store.crm.notifications.length"></span>)
                                </button>
                                <button 
                                    @click="filter = 'unread'" 
                                    :class="filter === 'unread' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-800'"
                                    class="px-3 py-1.5 rounded-lg transition cursor-pointer"
                                >
                                    Belum Dibaca (<span x-text="$store.crm.unreadCount"></span>)
                                </button>
                            </div>

                            <!-- Items List -->
                            <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                                <template x-for="item in $store.crm.notifications.filter(n => filter === 'all' || !n.read)" :key="item.id">
                                    <div 
                                        @click="$store.crm.markAsRead(item.id); if(item.link && item.link !== '#') window.location.href = item.link;"
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
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <template x-if="item.icon">
                                                        <span class="text-xs" x-text="item.icon"></span>
                                                    </template>
                                                    <p class="text-xs font-bold text-slate-800 leading-snug group-hover:text-blue-600 transition" x-text="item.title"></p>
                                                </div>
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
                        <a href="{{ route('profil.index') }}" title="Lihat Profil" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold flex items-center justify-center text-xs sm:text-sm shadow-xs hover:ring-2 hover:ring-blue-500/30 transition overflow-hidden">
                            @if(!empty($currentUser['avatar_url']))
                                <img src="{{ $currentUser['avatar_url'] }}" alt="{{ $currentUser['name'] }}" class="w-full h-full object-cover">
                            @else
                                {{ $currentUser['avatar'] }}
                            @endif
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
                @if(auth()->check() && strtolower(auth()->user()->role) === 'sales')
                    @php
                        $pendingVisits = \App\Models\Prospek::where('sales_id', auth()->id())->where('needs_visit_report', true)->count();
                    @endphp
                    @if($pendingVisits > 0)
                        <div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start sm:items-center justify-between gap-4 shadow-xs">
                            <div class="flex items-start gap-3">
                                <div class="bg-amber-100 p-2 rounded-lg text-amber-600 shrink-0 mt-0.5 sm:mt-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-amber-800">Pengingat Laporan Kunjungan</h4>
                                    <p class="text-xs text-amber-700 mt-0.5">Ada <span class="font-bold">{{ $pendingVisits }} prospek baru</span> yang belum memiliki laporan kunjungan. Jangan lupa lengkapi data kunjungannya!</p>
                                </div>
                            </div>
                            <button @click="modalTambahKunjungan = true" class="shrink-0 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg transition shadow-sm">
                                Lapor Sekarang
                            </button>
                        </div>
                    @endif
                @endif
                {{ $slot ?? '' }}
                @yield('content')
            </main>

        </div>

        <!-- ============================================================== -->
        <!-- MOBILE DRAWER NAVIGATION (Hamburger Menu + Live Search)        -->
        <!-- ============================================================== -->
        
        <!-- Backdrop Overlay -->
        <div 
            x-show="mobileMenuOpen" 
            x-cloak 
            x-transition:enter="transition-opacity ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 md:hidden"
            @click="mobileMenuOpen = false"
        ></div>

        <!-- Offcanvas Drawer -->
        <aside 
            x-show="mobileMenuOpen" 
            x-cloak 
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 w-[85%] max-w-sm bg-white shadow-2xl z-50 md:hidden flex flex-col border-r border-slate-200"
            @keydown.escape.window="mobileMenuOpen = false"
            x-data="{
                searchQuery: '',
                matches(keywords) {
                    if (!this.searchQuery || !this.searchQuery.trim()) return true;
                    return keywords.toLowerCase().includes(this.searchQuery.toLowerCase().trim());
                },
                hasMatches() {
                    if (!this.searchQuery || !this.searchQuery.trim()) return true;
                    const q = this.searchQuery.toLowerCase().trim();
                    const items = this.$el.querySelectorAll('[data-menu-keywords]');
                    for (let el of items) {
                        if ((el.getAttribute('data-menu-keywords') || '').toLowerCase().includes(q)) {
                            return true;
                        }
                    }
                    return false;
                }
            }"
        >
            <!-- Drawer Header: Brand & Close -->
            <div class="h-16 px-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/80 shrink-0">
                <a href="{{ route('dashboard') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-white border border-slate-200/80 flex items-center justify-center p-1 shadow-2xs shrink-0">
                        <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" class="w-full h-full object-contain">
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-bold text-slate-900 tracking-tight leading-tight">UCIC CRM</div>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-blue-50 text-blue-700 font-semibold border border-blue-200 mt-0.5 leading-none">
                            {{ $currentUser['role_label'] }}
                        </span>
                    </div>
                </a>
                <button 
                    type="button" 
                    @click="mobileMenuOpen = false" 
                    class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition cursor-pointer"
                    title="Tutup Menu"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Drawer Search Bar -->
            <div class="p-3 border-b border-slate-100 bg-white shrink-0">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        x-model="searchQuery" 
                        placeholder="Cari menu..." 
                        class="w-full pl-9 pr-8 py-2 text-xs bg-slate-100/90 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:bg-white transition"
                    >
                    <button 
                        x-show="searchQuery.length > 0" 
                        @click="searchQuery = ''" 
                        type="button"
                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Drawer Menu List (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-3 space-y-4">

                <!-- SECTION: MENU UTAMA -->
                <div x-show="matches('dashboard kalender internal utama agenda infografis statistik')">
                    <div class="px-3 mb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Menu Utama</div>
                    <div class="space-y-1">
                        <a 
                            href="{{ route('dashboard') }}" 
                            @click="mobileMenuOpen = false"
                            data-menu-keywords="dashboard beranda home utama"
                            x-show="matches('dashboard beranda home utama')"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('dashboard*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="flex-1">Dashboard</span>
                        </a>

                        <a 
                            href="{{ route('calendar.index') }}" 
                            @click="mobileMenuOpen = false"
                            data-menu-keywords="kalender internal agenda jadwal kegiatan"
                            x-show="matches('kalender internal agenda jadwal kegiatan')"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('calendar.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('calendar.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="flex-1">Kalender Internal</span>
                        </a>
                        
                        <a 
                            href="{{ route('infografis.index') }}" 
                            @click="mobileMenuOpen = false"
                            data-menu-keywords="infografis statistik laporan grafik data"
                            x-show="matches('infografis statistik laporan grafik data')"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('infografis.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('infografis.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                            <span class="flex-1">Infografis</span>
                        </a>
                    </div>
                </div>

                @if($currentUser['role'] === 'EO')
                    <!-- SECTION: EVENT ORGANIZER -->
                    <div x-show="matches('kelola event acara agenda eo management')">
                        <div class="px-3 mb-1.5 text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Event Management</div>
                        <div class="space-y-1">
                            <a 
                                href="{{ route('eo.events.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="kelola event acara agenda manajemen eo"
                                x-show="matches('kelola event acara agenda manajemen eo')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('eo.events.*') ? 'bg-emerald-50 text-emerald-700 font-bold border border-emerald-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('eo.events.*') ? 'text-emerald-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span class="flex-1">Kelola Event</span>
                            </a>
                        </div>
                    </div>
                @endif

                @if($currentUser['role'] === 'Admin')
                    <!-- SECTION: ADMIN PANEL -->
                    <div x-show="matches('admin control kelola pengguna user kunjungan target')">
                        <div class="px-3 mb-1.5 text-[10px] font-bold text-purple-700 uppercase tracking-wider">Admin Control</div>
                        <div class="space-y-1">
                            <a 
                                href="{{ route('admin.users.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="kelola pengguna user akun staf pegawai admin"
                                x-show="matches('kelola pengguna user akun staf pegawai admin')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.users.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.users.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span class="flex-1">Kelola Pengguna</span>
                            </a>

                            <a 
                                href="{{ route('admin.hm-wilayah.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="wilayah hm penugasan kota kabupaten marketing admin"
                                x-show="matches('wilayah hm penugasan kota kabupaten marketing admin')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.hm-wilayah.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.hm-wilayah.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                                <span class="flex-1">Wilayah HM</span>
                            </a>

                            <a 
                                href="{{ route('admin.kunjungan.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="kelola kunjungan visit sekolah instansi admin"
                                x-show="matches('kelola kunjungan visit sekolah instansi admin')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.kunjungan.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.kunjungan.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span class="flex-1">Kelola Kunjungan</span>
                            </a>

                            <a 
                                href="{{ route('admin.target.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="kelola target sasaran kpi performa admin"
                                x-show="matches('kelola target sasaran kpi performa admin')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.target.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.target.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <span class="flex-1">Kelola Target</span>
                            </a>
                        </div>
                    </div>

                    <!-- SECTION: DATA MASTER -->
                    <div x-show="matches('data master wilayah sekolah prodi jurusan perusahaan audit log')">
                        <div class="px-3 mb-1.5 text-[10px] font-bold text-purple-700 uppercase tracking-wider">Data Master</div>
                        <div class="space-y-1">
                            <a 
                                href="{{ route('admin.master-data.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="data master pusat referensi"
                                x-show="matches('data master pusat referensi')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.master-data.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.master-data.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <span class="flex-1">Data Master</span>
                            </a>

                            <a 
                                href="{{ route('admin.wilayah.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="data wilayah kota kabupaten zonasi daerah"
                                x-show="matches('data wilayah kota kabupaten zonasi daerah')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.wilayah.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.wilayah.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="flex-1">Data Wilayah</span>
                            </a>

                            <a 
                                href="{{ route('potensi-wilayah.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="potensi wilayah prospek closing"
                                x-show="matches('potensi wilayah prospek closing')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('potensi-wilayah.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('potensi-wilayah.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="flex-1">Potensi Wilayah</span>
                            </a>

                            <a 
                                href="{{ route('admin.sekolah.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="data sekolah sma smk ma institusi pendidikan"
                                x-show="matches('data sekolah sma smk ma institusi pendidikan')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.sekolah.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.sekolah.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                </svg>
                                <span class="flex-1">Data Sekolah</span>
                            </a>

                            <a 
                                href="{{ route('admin.prodi.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="data prodi program studi jurusan fakultas"
                                x-show="matches('data prodi program studi jurusan fakultas')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.prodi.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.prodi.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <span class="flex-1">Data Prodi</span>
                            </a>

                            <a 
                                href="{{ route('admin.perusahaan.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="data perusahaan instansi mitra kantor corporate"
                                x-show="matches('data perusahaan instansi mitra kantor corporate')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.perusahaan.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.perusahaan.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span class="flex-1">Data Perusahaan</span>
                            </a>

                            <a 
                                href="{{ route('admin.audit-logs.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="audit log log aktivitas riwayat sistem keamanan"
                                x-show="matches('audit log log aktivitas riwayat sistem keamanan')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.audit-logs.*') ? 'bg-purple-50 text-purple-700 font-bold border border-purple-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.audit-logs.*') ? 'text-purple-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="flex-1">Log Aktivitas</span>
                            </a>
                        </div>
                    </div>
                @elseif($currentUser['role'] !== 'EO')
                    <!-- SECTION: CRM INBOUND (Sales, CS, SPV, HM) -->
                    @php
                        $prospekMobileTitle = 'Prospek';
                        if ($userRole === 'sales') $prospekMobileTitle = 'Data Prospek';
                        if ($userRole === 'cs') $prospekMobileTitle = 'Data Kontak';
                    @endphp
                    <div x-show="matches('crm inbound prospek kontak kunjungan follow up event pipeline')">
                        <div class="px-3 mb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">CRM Inbound</div>
                        <div class="space-y-1">
                            <a 
                                href="{{ route($routePrefix . 'prospek.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="prospek data prospek kontak leads inbound database"
                                x-show="matches('prospek data prospek kontak leads inbound database')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs($routePrefix . 'prospek.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs($routePrefix . 'prospek.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span class="flex-1">{{ $prospekMobileTitle }}</span>
                                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 font-medium">{{ $globalProspekCount ?? 0 }}</span>
                            </a>

                            @if($currentUser['role'] !== 'CS')
                                <a 
                                    href="{{ route($routePrefix . 'kunjungan.index') }}" 
                                    @click="mobileMenuOpen = false"
                                    data-menu-keywords="kunjungan visit sekolah instansi audiensi"
                                    x-show="matches('kunjungan visit sekolah instansi audiensi')"
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs($routePrefix . 'kunjungan.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                >
                                    <svg class="w-5 h-5 shrink-0 {{ request()->routeIs($routePrefix . 'kunjungan.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="flex-1">Kunjungan</span>
                                </a>
                            @endif

                            @if($userRole === 'sales')
                                <a 
                                    href="{{ route('sales.follow-up.index') }}" 
                                    @click="mobileMenuOpen = false"
                                    data-menu-keywords="follow up penindaklanjutan kontak prospek telepon chat whatsapp hari ini"
                                    x-show="matches('follow up penindaklanjutan kontak prospek telepon chat whatsapp hari ini')"
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('sales.follow-up.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                >
                                    <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('sales.follow-up.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <span class="flex-1">Follow Up</span>
                                    @if(isset($globalFollowUpTodayCount) && $globalFollowUpTodayCount > 0)
                                        <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-800 font-semibold">{{ $globalFollowUpTodayCount }} Hari Ini</span>
                                    @endif
                                </a>

                                <a 
                                    href="{{ route('sales.event.index') }}" 
                                    @click="mobileMenuOpen = false"
                                    data-menu-keywords="jadwal event acara kegiatan pameran sosialisasi"
                                    x-show="matches('jadwal event acara kegiatan pameran sosialisasi')"
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('sales.event.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                >
                                    <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('sales.event.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="flex-1">Jadwal Event</span>
                                </a>
                            @elseif($userRole !== 'spv')
                                <a 
                                    href="{{ route('follow-up.index') }}" 
                                    @click="mobileMenuOpen = false"
                                    data-menu-keywords="follow up penindaklanjutan kontak prospek telepon chat whatsapp"
                                    x-show="matches('follow up penindaklanjutan kontak prospek telepon chat whatsapp')"
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('follow-up.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                >
                                    <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('follow-up.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <span class="flex-1">Follow Up</span>
                                </a>
                            @endif

                            <a 
                                href="{{ route($routePrefix . 'pipeline.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="pipeline board alur prospek prospek funnel kanban closing"
                                x-show="matches('pipeline board alur prospek prospek funnel kanban closing')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs($routePrefix . 'pipeline.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs($routePrefix . 'pipeline.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                </svg>
                                <span class="flex-1">Pipeline Board</span>
                            </a>
                        </div>
                    </div>

                    <!-- SECTION: PERFORMANCE -->
                    <div x-show="matches('performance performa target kpi laporan report')">
                        <div class="px-3 mb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Performance</div>
                        <div class="space-y-1">
                            <a 
                                href="{{ route($routePrefix . 'performa.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="target performa capaian penjualan sales kpi"
                                x-show="matches('target performa capaian penjualan sales kpi')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs($routePrefix . 'performa.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs($routePrefix . 'performa.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                <span class="flex-1">Target & Performa</span>
                            </a>

                            <a 
                                href="{{ route($routePrefix . 'laporan.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="laporan report rekap export unduh analisa"
                                x-show="matches('laporan report rekap export unduh analisa')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs($routePrefix . 'laporan.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs($routePrefix . 'laporan.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="flex-1">Laporan</span>
                            </a>
                        </div>
                    </div>

                    @if($userRole === 'spv')
                        <!-- SECTION: TIM SPV -->
                        <div x-show="matches('manajemen tim spv assignment event penugasan supervisor')">
                            <div class="px-3 mb-1.5 text-[10px] font-bold text-indigo-600 uppercase tracking-wider">Manajemen Tim</div>
                            <div class="space-y-1">
                                <a 
                                    href="{{ route('spv.tim.index') }}" 
                                    @click="mobileMenuOpen = false"
                                    data-menu-keywords="kelola sales tim spv manajemen tim supervisor sales staf"
                                    x-show="matches('kelola sales tim spv manajemen tim supervisor sales staf')"
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('spv.tim.*') ? 'bg-indigo-50 text-indigo-700 font-bold border border-indigo-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                >
                                    <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('spv.tim.*') ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span class="flex-1">Kelola Sales</span>
                                </a>

                                <a 
                                    href="{{ route('spv.events.index') }}" 
                                    @click="mobileMenuOpen = false"
                                    data-menu-keywords="assignment event penugasan acara spv jadwal tim"
                                    x-show="matches('assignment event penugasan acara spv jadwal tim')"
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('spv.events.*') ? 'bg-indigo-50 text-indigo-700 font-bold border border-indigo-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                >
                                    <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('spv.events.*') ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="flex-1">Assignment Event</span>
                                </a>
                            </div>
                        </div>
                    @endif
                @endif

                @if(in_array($currentUser['role'], ['HM', 'Head Marketing']))
                    <!-- SECTION: MANAGEMENT (Head Marketing) -->
                    <div x-show="matches('management kelola wilayah tim target head marketing')">
                        <div class="px-3 mb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Management</div>
                        <div class="space-y-1">
                            <a 
                                href="{{ route('hm.wilayah.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="wilayah saya kelola wilayah zonasi daerah marketing"
                                x-show="matches('wilayah saya kelola wilayah zonasi daerah marketing')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('hm.wilayah.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('hm.wilayah.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="flex-1">Wilayah Saya</span>
                            </a>

                            <a 
                                href="{{ route('potensi-wilayah.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="potensi wilayah prospek closing"
                                x-show="matches('potensi wilayah prospek closing')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('potensi-wilayah.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('potensi-wilayah.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="flex-1">Potensi Wilayah</span>
                            </a>

                            <a 
                                href="{{ route('admin.target.index') }}" 
                                @click="mobileMenuOpen = false"
                                data-menu-keywords="kelola target sasaran kpi marketing"
                                x-show="matches('kelola target sasaran kpi marketing')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.target.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            >
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.target.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span class="flex-1">Kelola Target</span>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- SECTION: AKUN & PENGATURAN -->
                <div x-show="matches('account profil saya profil akun pengaturan sistem settings password')">
                    <div class="px-3 mb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Akun & Pengaturan</div>
                    <div class="space-y-1">
                        <a 
                            href="{{ route('profil.index') }}" 
                            @click="mobileMenuOpen = false"
                            data-menu-keywords="profil saya profil akun biodata data diri password"
                            x-show="matches('profil saya profil akun biodata data diri password')"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('profil.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('profil.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span class="flex-1">Profil Saya</span>
                        </a>

                        <a 
                            href="{{ route('pengaturan.index') }}" 
                            @click="mobileMenuOpen = false"
                            data-menu-keywords="pengaturan sistem settings preferensi notifikasi keamanan"
                            x-show="matches('pengaturan sistem settings preferensi notifikasi keamanan')"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('pengaturan.*') ? 'bg-blue-50 text-blue-700 font-bold border border-blue-100 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('pengaturan.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="flex-1">Pengaturan</span>
                        </a>
                    </div>
                </div>

                <!-- Empty Search State -->
                <div x-show="searchQuery.trim().length > 0 && !hasMatches()" class="py-10 px-4 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <div class="text-xs font-bold text-slate-700">Menu tidak ditemukan</div>
                    <p class="text-[11px] text-slate-400 mt-1 max-w-[200px] mx-auto">
                        Tidak ada menu yang sesuai dengan kata kunci "<span class="font-semibold text-slate-600" x-text="searchQuery"></span>"
                    </p>
                    <button 
                        type="button" 
                        @click="searchQuery = ''" 
                        class="mt-3 px-3 py-1.5 text-xs text-blue-600 font-semibold bg-blue-50 hover:bg-blue-100 rounded-lg transition cursor-pointer"
                    >
                        Hapus pencarian
                    </button>
                </div>

            </div>

            <!-- Drawer Footer: User Profile Card & Logout -->
            <div class="border-t border-slate-100 bg-slate-50/70 p-3 shrink-0">
                <div class="flex items-center justify-between gap-2 p-2 bg-white rounded-xl border border-slate-200/80 shadow-xs">
                    <a href="{{ route('profil.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-semibold flex items-center justify-center text-xs shrink-0 shadow-xs overflow-hidden">
                            @if(!empty($currentUser['avatar_url']))
                                <img src="{{ $currentUser['avatar_url'] }}" alt="{{ $currentUser['name'] }}" class="w-full h-full object-cover">
                            @else
                                {{ $currentUser['avatar'] }}
                            @endif
                        </div>
                        <div class="min-w-0 flex-1 truncate">
                            <div class="text-xs font-bold text-slate-800 truncate">{{ $currentUser['name'] }}</div>
                            <div class="text-[10px] text-blue-600 font-medium truncate">{{ $currentUser['role_label'] }}</div>
                        </div>
                    </a>
                    <button 
                        type="button" 
                        onclick="confirmLogout()" 
                        title="Logout / Keluar" 
                        class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

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
                class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10 max-h-[90vh] overflow-y-auto"
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

                <form action="{{ route((auth()->user()->role === 'Sales' ? 'sales.' : '') . 'prospek.store') }}" method="POST" class="mt-5 space-y-6" x-data="{ prospekType: 'Sekolah' }">
                    @csrf

                    @if (isset($errors) && $errors->any() && (old('pic') || old('type') || old('whatsapp') || old('source') || old('sekolah_id') || old('perusahaan_id') || old('sekolah_manual') || old('perusahaan_manual')))
                        <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl text-xs space-y-1">
                            <div class="font-bold flex items-center gap-1.5 text-rose-900">
                                <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Gagal Menyimpan Prospek:</span>
                            </div>
                            <ul class="list-disc pl-5 space-y-0.5 font-medium">
                                @foreach ($errors->all() as $error)
                                    <li class="whitespace-pre-line">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Section: INFORMASI PROSPEK -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-blue-700 bg-blue-50 px-2.5 py-1 rounded-md inline-block mb-3">Informasi Prospek</h4>
                        @php
                            $sekolahsList = \App\Models\Sekolah::getDynamicSchools();
                            $perusahaansList = \App\Models\Perusahaan::getDynamicPerusahaans();
                            $statusProspekList = \App\Models\MasterData::where('type', 'status_prospek')->where('status', 'Aktif')->get();
                            if (strtolower(auth()->user()->role ?? '') === 'sales') {
                                $statusProspekList = $statusProspekList->filter(fn($item) => strtoupper($item->nama) !== 'LUNAS');
                            }
                            $sumberProspekList = \App\Models\MasterData::where('type', 'sumber_prospek')->where('status', 'Aktif')->get();
                            $prodisList = \App\Models\Prodi::where('status', 'Aktif')->orderBy('nama')->get();
                            $currentUser = auth()->user();
                            $canAssignSales = in_array($currentUser->role ?? '', ['SPV', 'Admin', 'Head Marketing']);
                            $salesAssignees = [];
                            if ($canAssignSales) {
                                if ($currentUser->role === 'SPV') {
                                    $salesAssignees = \App\Models\User::where('supervisor_id', $currentUser->id)->where('role', 'Sales')->where('status', 'Aktif')->get();
                                    if ($salesAssignees->isEmpty()) {
                                        $salesAssignees = \App\Models\User::where('role', 'Sales')->where('status', 'Aktif')->get();
                                    }
                                } else {
                                    $salesAssignees = \App\Models\User::where('role', 'Sales')->where('status', 'Aktif')->get();
                                }
                            }
                        @endphp
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @if($canAssignSales)
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Assign ke Sales *</label>
                                    <select name="sales_id" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                        <option value="">-- Pilih Personil Sales --</option>
                                        @foreach($salesAssignees as $sUser)
                                            <option value="{{ $sUser->id }}">{{ $sUser->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <input type="hidden" name="sales_id" value="{{ auth()->id() }}">
                            @endif

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5 h-5 flex items-center">Tipe Prospek <span class="text-rose-500 ml-0.5">*</span></label>
                                <select name="type" x-model="prospekType" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                    <option value="Sekolah">Sekolah (SMA/SMK/MA)</option>
                                    <option value="Corporate">Corporate / Perusahaan</option>
                                    <option value="Individu">Individu / Siswa Langsung</option>
                                </select>
                            </div>

                            <!-- Input Sekolah (Search & Manual Mode) -->
                            <div x-show="prospekType === 'Sekolah'" x-data="{ modeManualSekolah: false }" x-on:switch-manual.stop="if ($event.detail.name === 'sekolah_id') { modeManualSekolah = true; $nextTick(function() { const inp = $el.querySelector('input[name=sekolah_manual]'); if(inp) { inp.value = $event.detail.search; inp.focus(); } }); }" class="space-y-1">
                                <div class="flex items-center justify-between mb-1.5 h-5">
                                    <template x-if="!modeManualSekolah">
                                        <div class="flex items-center justify-between w-full">
                                            <label class="block text-xs font-semibold text-slate-700">Nama Sekolah <span class="text-rose-500">*</span></label>
                                            <button 
                                                type="button" 
                                                @click="modeManualSekolah = true; const sel = $el.closest('.space-y-1').querySelector('input[name=sekolah_id]'); if(sel) sel.value = '';" 
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200/60 transition cursor-pointer group"
                                            >
                                                <svg class="w-3 h-3 text-blue-500 group-hover:text-blue-700 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                <span>Input Manual</span>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="modeManualSekolah">
                                        <div class="flex items-center justify-between w-full">
                                            <div class="flex items-center gap-1.5">
                                                <label class="block text-xs font-semibold text-slate-700">Nama Sekolah <span class="text-rose-500">*</span></label>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/80">
                                                    Manual
                                                </span>
                                            </div>
                                            <button 
                                                type="button" 
                                                @click="modeManualSekolah = false;" 
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 transition cursor-pointer"
                                            >
                                                <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                <span>Cari Database</span>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <div x-show="!modeManualSekolah">
                                    <x-searchable-select 
                                        name="sekolah_id" 
                                        :options="$sekolahsList" 
                                        placeholder="-- Ketik untuk mencari Sekolah... --" 
                                    />
                                </div>

                                <div x-show="modeManualSekolah" style="display: none;" class="space-y-1.5">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-blue-500">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <input 
                                            type="text" 
                                            name="sekolah_manual" 
                                            placeholder="Ketik nama sekolah (misal: SMAN 1 Cirebon)..." 
                                            class="w-full text-xs sm:text-sm pl-10 pr-3.5 py-2.5 rounded-xl border border-blue-200 bg-blue-50/20 text-slate-800 font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition shadow-2xs"
                                        >
                                    </div>
                                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50/70 border border-amber-200/50 text-[10px] text-amber-800 font-medium">
                                        <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Sekolah baru akan otomatis tersimpan ke master data.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Input Corporate (Search & Manual Mode) -->
                            <div x-show="prospekType === 'Corporate'" x-data="{ modeManualCorp: false }" x-on:switch-manual.stop="if ($event.detail.name === 'perusahaan_id') { modeManualCorp = true; $nextTick(function() { const inp = $el.querySelector('input[name=perusahaan_manual]'); if(inp) { inp.value = $event.detail.search; inp.focus(); } }); }" style="display: none;" class="space-y-1">
                                <div class="flex items-center justify-between mb-1.5 h-5">
                                    <template x-if="!modeManualCorp">
                                        <div class="flex items-center justify-between w-full">
                                            <label class="block text-xs font-semibold text-slate-700">Nama Perusahaan <span class="text-rose-500">*</span></label>
                                            <button 
                                                type="button" 
                                                @click="modeManualCorp = true; const sel = $el.closest('.space-y-1').querySelector('input[name=perusahaan_id]'); if(sel) sel.value = '';" 
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200/60 transition cursor-pointer group"
                                            >
                                                <svg class="w-3 h-3 text-blue-500 group-hover:text-blue-700 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                <span>Input Manual</span>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="modeManualCorp">
                                        <div class="flex items-center justify-between w-full">
                                            <div class="flex items-center gap-1.5">
                                                <label class="block text-xs font-semibold text-slate-700">Nama Perusahaan <span class="text-rose-500">*</span></label>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/80">
                                                    Manual
                                                </span>
                                            </div>
                                            <button 
                                                type="button" 
                                                @click="modeManualCorp = false;" 
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 transition cursor-pointer"
                                            >
                                                <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                <span>Cari Database</span>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <div x-show="!modeManualCorp">
                                    <x-searchable-select 
                                        name="perusahaan_id" 
                                        :options="$perusahaansList" 
                                        placeholder="-- Ketik untuk mencari Perusahaan... --" 
                                    />
                                </div>

                                <div x-show="modeManualCorp" style="display: none;" class="space-y-1.5">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-blue-500">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input 
                                            type="text" 
                                            name="perusahaan_manual" 
                                            placeholder="Ketik nama perusahaan (misal: PT Telkom Cirebon)..." 
                                            class="w-full text-xs sm:text-sm pl-10 pr-3.5 py-2.5 rounded-xl border border-blue-200 bg-blue-50/20 text-slate-800 font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition shadow-2xs"
                                        >
                                    </div>
                                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50/70 border border-amber-200/50 text-[10px] text-amber-800 font-medium">
                                        <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Perusahaan baru akan otomatis tersimpan ke master data.</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5 h-5 flex items-center justify-between">
                                    <span>Program Studi Diminati</span>
                                    <span class="text-[11px] text-slate-400 font-normal">Opsional</span>
                                </label>
                                <select name="prodi_id" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                    <option value="">-- Pilih Program Studi (Boleh Dikosongkan) --</option>
                                    @foreach($prodisList as $prd)
                                        <option value="{{ $prd->id }}">{{ $prd->nama }} ({{ $prd->jenjang }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Status Awal *</label>
                                <select name="status" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                    @foreach($statusProspekList as $status)
                                        <option value="{{ $status->nama }}" {{ in_array($status->nama, ['BARU', 'Baru', 'Interested']) ? 'selected' : '' }}>{{ $status->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama</label>
                                <input type="text" name="pic" required placeholder="Contoh: Budi Santoso" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp *</label>
                                <input type="tel" name="whatsapp" required placeholder="Masukkan nomor .." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Sumber Prospek *</label>
                                <select name="source" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                    <option value="">-- Pilih Sumber --</option>
                                    @foreach($sumberProspekList as $sumber)
                                        <option value="{{ $sumber->nama }}">{{ $sumber->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Prospek</label>
                                @php
                                    $kategoriProspekList = \App\Models\MasterData::where('type', 'kategori_prospek')->where('status', 'Aktif')->get();
                                @endphp
                                <select name="category" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($kategoriProspekList as $kategori)
                                        <option value="{{ $kategori->nama }}">{{ $kategori->nama }}</option>
                                    @endforeach
                                </select>
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Metode Follow Up *</label>
                            <select name="metode" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                <option value="WhatsApp">WhatsApp</option>
                                <option value="Telepon">Telepon</option>
                                <option value="Meeting">Meeting (Tatap Muka)</option>
                                <option value="Email">Email</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Hasil Follow Up *</label>
                            @php
                                $statusFollowupList = \App\Models\MasterData::where('type', 'status_followup')->where('status', 'Aktif')->get();
                            @endphp
                            <select name="hasil" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                @foreach($statusFollowupList as $hasil)
                                    <option value="{{ $hasil->nama }}">{{ $hasil->nama }}</option>
                                @endforeach
                            </select>
                        </div>
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
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Update Status Prospek</label>
                            @php
                                $activeStagesFU = \App\Models\Prospek::ACTIVE_STAGES;
                                if (strtolower(auth()->user()->role ?? '') === 'sales') {
                                    $activeStagesFU = array_filter($activeStagesFU, fn($s) => strtoupper($s) !== 'LUNAS');
                                }
                            @endphp
                            <select name="status" class="w-full text-xs sm:text-sm px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                @foreach($activeStagesFU as $stage)
                                    <option value="{{ $stage }}" x-bind:selected="selectedProspect.status === '{{ $stage }}'">{{ $stage }}</option>
                                @endforeach
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

                <form @submit.prevent="
                    fetch('/pipeline/update-status', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            prospek_id: selectedProspect.id,
                            status: $event.target.status.value
                        })
                    }).then(r => r.json()).then(data => {
                        if(data.success) {
                            $store.crm.showToast('Status berhasil diperbarui!');
                            setTimeout(() => window.location.reload(), 800);
                        } else {
                            alert(data.message || 'Gagal mengubah status');
                        }
                    }).catch(err => alert('Terjadi kesalahan sistem'));
                " class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Status Baru *</label>
                        @php
                            $statusProspekList = \App\Models\MasterData::where('type', 'status_prospek')->where('status', 'Aktif')->get();
                            if (strtolower(auth()->user()->role ?? '') === 'sales') {
                                $statusProspekList = $statusProspekList->filter(fn($item) => strtoupper($item->nama) !== 'LUNAS');
                            }
                        @endphp
                        <select name="status" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                            @foreach($statusProspekList as $status)
                                <option value="{{ $status->nama }}">{{ $status->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Perubahan (Opsional)</label>
                        <textarea name="notes" rows="3" placeholder="Tuliskan alasan atau ringkasan mengapa status prospek diperbarui..." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="modalUpdateStatus = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-xs">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- MODAL 5: TAMBAH KUNJUNGAN (WITH GPS & FOTO) -->
    <div 
        x-show="modalTambahKunjungan" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog" 
        aria-modal="true"
         @fill-kunjungan.window="
            modalTambahKunjungan = true;
            isEventMode = true;
            eventId = $event.detail.event_id;
            eventName = $event.detail.event_name;
            eventDate = $event.detail.event_date;
            namaInstitusi = $event.detail.nama_institusi;
            eventTempat = $event.detail.tempat || $event.detail.nama_institusi || $event.detail.lokasi;
            
            if ($event.detail.type) {
                kunjunganType = $event.detail.type;
            }
            if ($event.detail.sekolah_id) {
                selectedSekolahId = $event.detail.sekolah_id;
            }
            if ($event.detail.perusahaan_id) {
                selectedPerusahaanId = $event.detail.perusahaan_id;
            }
            picName = $event.detail.pic_name || '';
            picWhatsapp = $event.detail.pic_whatsapp || '';
         "
    >
        <div class="flex items-end sm:items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="modalTambahKunjungan" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalTambahKunjungan = false"></div>

            <div 
                x-show="modalTambahKunjungan"
                class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10 max-h-[90vh] overflow-y-auto"
                x-data="{ 
                    isEventMode: false,
                    eventId: '',
                    eventName: '',
                    eventDate: '',
                    namaInstitusi: '',
                    eventTempat: '',
                    kunjunganType: 'sekolah', 
                    photoPreview: null,
                    modeManualSekolah: false,
                    sekolah_manual: '',
                    modeManualCorp: false,
                    perusahaan_manual: '',
                    selectedSchoolSource: '',
                    selectedSekolahId: '',
                    selectedPerusahaanId: '',
                    prospek_id: '',
                    sekolahPicMap: {},
                    prospekPicMap: {},
                    perusahaanPicMap: {},
                    picName: '',
                    picWhatsapp: '',
                    lat: '',
                    lng: '',
                    geoAddress: '',
                    geoStatus: '',
                    handleSchoolSelect(sourceVal) {
                        this.selectedSchoolSource = sourceVal;
                        if (this.kunjunganType === 'sekolah' && !this.isEventMode) {
                            if (sourceVal && sourceVal.startsWith('prospek_')) {
                                const pid = sourceVal.replace('prospek_', '');
                                const item = this.prospekPicMap[pid];
                                if (item) {
                                    this.prospek_id = item.id;
                                    this.selectedSekolahId = item.sekolah_id || '';
                                    this.namaInstitusi = item.name;
                                    this.picName = item.pic || '';
                                    this.picWhatsapp = item.whatsapp || '';
                                }
                            } else if (sourceVal && sourceVal.startsWith('sekolah_')) {
                                const sid = sourceVal.replace('sekolah_', '');
                                this.prospek_id = '';
                                this.selectedSekolahId = sid;
                                const item = this.sekolahPicMap[sid];
                                if (item) {
                                    this.namaInstitusi = item.nama;
                                    this.picName = item.pic || '';
                                    this.picWhatsapp = item.whatsapp || '';
                                }
                            } else {
                                this.prospek_id = '';
                                this.selectedSekolahId = '';
                                this.namaInstitusi = '';
                                this.picName = '';
                                this.picWhatsapp = '';
                            }
                        }
                    },
                    handleCorpSelect(corpId) {
                        this.selectedPerusahaanId = corpId;
                        if (this.kunjunganType === 'corporate' && !this.isEventMode) {
                            if (corpId && this.perusahaanPicMap[corpId]) {
                                this.namaInstitusi = this.perusahaanPicMap[corpId].nama || '';
                                this.picName = this.perusahaanPicMap[corpId].pic || '';
                                this.picWhatsapp = this.perusahaanPicMap[corpId].whatsapp || '';
                            } else {
                                this.picName = '';
                                this.picWhatsapp = '';
                            }
                        }
                    },
                    getGeolocation() {
                        this.geoStatus = 'Mencari lokasi GPS...';
                        if (navigator.geolocation) {
                            navigator.geolocation.getCurrentPosition(
                                (position) => {
                                    this.lat = position.coords.latitude;
                                    this.lng = position.coords.longitude;
                                    this.geoStatus = 'Koordinat ditemukan ✓. Melacak alamat...';
                                    
                                    // Reverse Geocoding via Nominatim
                                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${this.lat}&lon=${this.lng}`)
                                        .then(res => res.json())
                                        .then(data => {
                                            if (data && data.display_name) {
                                                this.geoAddress = data.display_name;
                                                this.geoStatus = 'Alamat ditemukan ✓';
                                            } else {
                                                this.geoStatus = 'Gagal melacak alamat detail, hanya koordinat yang disimpan.';
                                            }
                                        })
                                        .catch(() => {
                                            this.geoStatus = 'Gagal melacak alamat detail, hanya koordinat yang disimpan.';
                                        });
                                },
                                (error) => {
                                    this.geoStatus = 'Gagal mendapatkan lokasi. Aktifkan Izin Lokasi/GPS.';
                                },
                                { enableHighAccuracy: true, timeout: 10000 }
                            );
                        } else {
                            this.geoStatus = 'Browser tidak mendukung GPS.';
                        }
                    }
                }"
                x-init="
                    @php
                        $userVisit = auth()->user();
                        $prospekSekolahQuery = \App\Models\Prospek::query()->where('type', 'Sekolah');
                        if ($userVisit && $userVisit->role === 'Sales') {
                            $prospekSekolahQuery->where('sales_id', $userVisit->id);
                        } elseif ($userVisit && $userVisit->role === 'SPV') {
                            $subIds = \App\Models\User::where('supervisor_id', $userVisit->id)->pluck('id')->push($userVisit->id);
                            $prospekSekolahQuery->whereIn('sales_id', $subIds);
                        }
                        $availProspeks = $prospekSekolahQuery->orderBy('name')->get();

                        $pMap = [];
                        foreach($availProspeks as $ps) {
                            $pMap[$ps->id] = [
                                'id' => $ps->id,
                                'name' => $ps->name,
                                'sekolah_id' => $ps->sekolah_id,
                                'pic' => $ps->pic,
                                'whatsapp' => $ps->whatsapp,
                                'prodi_id' => $ps->prodi_id,
                                'status' => $ps->status,
                            ];
                        }

                        $sPicMap = [];
                        foreach(\App\Models\Sekolah::where('status', 'Aktif')->get() as $p) {
                            $sPicMap[$p->id] = ['nama' => $p->nama, 'pic' => $p->pic_name, 'whatsapp' => $p->pic_phone];
                        }
                        
                        $pPicMap = [];
                        foreach(\App\Models\Perusahaan::where('status', 'Aktif')->get() as $p) {
                            $pPicMap[$p->id] = ['nama' => $p->nama, 'pic' => $p->pic_name, 'whatsapp' => $p->pic_phone];
                        }

                        $modalSchoolOptions = [];
                        foreach($availProspeks as $p) {
                            $modalSchoolOptions[] = [
                                'value' => 'prospek_' . $p->id,
                                'label' => $p->name,
                                'sub'   => '📋 Prospek • Status: ' . $p->status . ($p->pic ? ' • PIC: ' . $p->pic : ''),
                            ];
                        }
                        foreach(\App\Models\Sekolah::where('status', 'Aktif')->orderBy('nama')->get() as $sek) {
                            $modalSchoolOptions[] = [
                                'value' => 'sekolah_' . $sek->id,
                                'label' => $sek->nama,
                                'sub'   => 'Master Database Sekolah' . ($sek->kota ? ' • ' . $sek->kota : ''),
                            ];
                        }

                        $modalCorpOptions = [];
                        foreach(\App\Models\Perusahaan::where('status', 'Aktif')->orderBy('nama')->get() as $per) {
                            $modalCorpOptions[] = [
                                'value' => (string)$per->id,
                                'label' => $per->nama,
                                'sub'   => '🏢 Database Perusahaan' . ($per->kota ? ' • ' . $per->kota : ''),
                            ];
                        }
                    @endphp
                    prospekPicMap = {{ json_encode($pMap) }};
                    sekolahPicMap = {{ json_encode($sPicMap) }};
                    perusahaanPicMap = {{ json_encode($pPicMap) }};

                    $watch('modalTambahKunjungan', value => {
                        if (value && !lat) {
                            getGeolocation();
                        }
                    });
                "
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
                <div class="flex border-b border-slate-200 mt-4 mb-5" x-show="!isEventMode">
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
                
                <div x-show="isEventMode" class="mb-5 p-3.5 bg-blue-50/50 border border-blue-100 rounded-xl">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-blue-100 text-blue-600 rounded-lg shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Terhubung dengan Event</h4>
                            <p class="text-sm font-semibold text-blue-800 mt-0.5" x-text="eventName"></p>
                            <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span x-text="eventDate"></span>
                            </p>
                        </div>
                    </div>
                </div>

                <form action="{{ route(\Illuminate\Support\Facades\Route::has(($routePrefix ?? '') . 'kunjungan.store') ? ($routePrefix ?? '') . 'kunjungan.store' : 'kunjungan.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    <input type="hidden" name="event_id" :value="eventId" x-bind:disabled="!isEventMode">
                    <input type="hidden" name="lokasi_penugasan" :value="geoAddress">
                    <input type="hidden" name="jenis" :value="kunjunganType === 'sekolah' ? 'Sekolah' : 'Perusahaan'">
                    <input type="hidden" name="tanggal" :value="new Date().toLocaleDateString('en-CA')" value="{{ date('Y-m-d') }}">
                    <input type="hidden" name="waktu" :value="String(new Date().getHours()).padStart(2, '0') + ':' + String(new Date().getMinutes()).padStart(2, '0')" value="{{ date('H:i') }}">
                    <input type="hidden" name="lat" x-model="lat">
                    <input type="hidden" name="lng" x-model="lng">
                    
                    <!-- INFORMASI KUNJUNGAN (Event Mode Only) -->
                    <div x-show="isEventMode" class="bg-slate-50/80 p-4 rounded-xl border border-slate-200/70 space-y-4">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Informasi Kunjungan</h4>
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Tempat</label>
                                <div class="text-sm font-semibold text-slate-800" x-text="eventTempat"></div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">PIC / Guru BK / HRD</label>
                                <div class="text-sm font-semibold text-slate-800" x-text="picName"></div>
                                <input type="hidden" name="pic_name" :value="picName" x-bind:disabled="!isEventMode">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Nomor WhatsApp PIC</label>
                                <div class="text-sm font-semibold text-slate-800" x-text="picWhatsapp"></div>
                                <input type="hidden" name="pic_whatsapp" :value="picWhatsapp" x-bind:disabled="!isEventMode">
                            </div>
                        </div>
                    </div>
                    
                    <!-- FORM SEKOLAH -->
                    <div x-show="kunjunganType === 'sekolah'">
                        <div class="space-y-4">
                            <!-- Event Mode: readonly info -->
                            <div x-show="isEventMode" class="bg-indigo-50/80 p-3.5 rounded-xl border border-indigo-200/70 space-y-3">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Informasi Sekolah / Tempat</h4>
                                <div class="grid grid-cols-1 gap-3">
                                    <div>
                                        <p class="text-[11px] font-bold text-slate-500 mb-0.5">Sekolah / Tempat</p>
                                        <p class="text-sm font-bold text-slate-900" x-text="eventTempat || namaInstitusi || '—'"></p>
                                        <input type="hidden" name="nama_institusi" :value="eventTempat || namaInstitusi">
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold text-slate-500 mb-0.5">PIC / Guru BK</p>
                                        <p class="text-sm font-bold text-slate-900" x-text="picName || '—'"></p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold text-slate-500 mb-0.5">Nomor WhatsApp PIC</p>
                                        <p class="text-sm font-bold text-slate-900" x-text="picWhatsapp || '—'"></p>
                                    </div>
                                </div>
                                <input type="hidden" name="pic_name" :value="picName">
                                <input type="hidden" name="pic_whatsapp" :value="picWhatsapp">
                            </div>

                            <!-- Non-Event Mode: Informasi Sekolah -->
                            <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70 space-y-3" x-show="!isEventMode">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Informasi Sekolah</h4>
                                    <span class="text-[11px] text-blue-600 font-medium">Terhubung Prospek / Master Sekolah</span>
                                </div>

                                <!-- Sekolah Selection (Search Plugin & Manual Mode) -->
                                <div 
                                    x-on:switch-manual.stop="if ($event.detail.name === 'school_source_select') { 
                                        modeManualSekolah = true; 
                                        sekolah_manual = $event.detail.search; 
                                        namaInstitusi = $event.detail.search; 
                                        selectedSchoolSource = ''; 
                                        prospek_id = ''; 
                                        selectedSekolahId = ''; 
                                        $nextTick(function() { 
                                            const inp = $el.querySelector('input[name=sekolah_manual]'); 
                                            if(inp) { inp.value = $event.detail.search; inp.focus(); } 
                                        }); 
                                    }" 
                                    class="space-y-1.5"
                                >
                                    <div class="flex items-center justify-between mb-1 h-5">
                                        <template x-if="!modeManualSekolah">
                                            <div class="flex items-center justify-between w-full">
                                                <label class="block text-xs font-semibold text-slate-700">Nama Sekolah <span class="text-rose-500">*</span></label>
                                                <button 
                                                    type="button" 
                                                    @click="modeManualSekolah = true; selectedSchoolSource = ''; prospek_id = ''; selectedSekolahId = ''; const sel = $el.closest('.space-y-1.5').querySelector('input[name=school_source_select]'); if(sel) sel.value = '';" 
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200/60 transition cursor-pointer group"
                                                >
                                                    <svg class="w-3 h-3 text-blue-500 group-hover:text-blue-700 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                    <span>Input Manual</span>
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="modeManualSekolah">
                                            <div class="flex items-center justify-between w-full">
                                                <div class="flex items-center gap-1.5">
                                                    <label class="block text-xs font-semibold text-slate-700">Nama Sekolah <span class="text-rose-500">*</span></label>
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/80">
                                                        Manual
                                                    </span>
                                                </div>
                                                <button 
                                                    type="button" 
                                                    @click="modeManualSekolah = false; sekolah_manual = '';" 
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 transition cursor-pointer"
                                                >
                                                    <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                    <span>Cari Database / Prospek</span>
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    <div x-show="!modeManualSekolah" x-on:change="if ($event.detail.name === 'school_source_select') handleSchoolSelect($event.detail.value)">
                                        <x-searchable-select 
                                            name="school_source_select" 
                                            :options="$modalSchoolOptions" 
                                            placeholder="-- Cari & Pilih Nama Sekolah dari Prospek / Database --" 
                                        />
                                    </div>

                                    <div x-show="modeManualSekolah" style="display: none;" class="space-y-1.5">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-blue-500">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            </div>
                                            <input 
                                                type="text" 
                                                name="sekolah_manual" 
                                                x-model="sekolah_manual" 
                                                @input="namaInstitusi = $event.target.value"
                                                placeholder="Ketik nama sekolah manual (misal: SMAN 1 Cirebon)..." 
                                                class="w-full text-xs sm:text-sm pl-10 pr-3.5 py-2.5 rounded-xl border border-blue-200 bg-blue-50/20 text-slate-800 font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition shadow-2xs"
                                            >
                                        </div>
                                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50/70 border border-amber-200/50 text-[10px] text-amber-800 font-medium">
                                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>Sekolah baru akan otomatis tersimpan ke master data.</span>
                                        </div>
                                    </div>

                                    <input type="hidden" name="prospek_id" :value="prospek_id">
                                    <input type="hidden" name="sekolah_id" :value="modeManualSekolah ? '' : selectedSekolahId">
                                    <input type="hidden" name="nama_institusi" :value="isEventMode ? (eventTempat || namaInstitusi) : (modeManualSekolah ? sekolah_manual : namaInstitusi)">
                                    <p class="text-[11px] text-slate-500 mt-1" x-show="!modeManualSekolah">Memilih nama sekolah otomatis mengisi data PIC & nomor WhatsApp di bawah.</p>
                                </div>
                            </div>

                            <!-- Non-Event Mode: PIC / BK -->
                            <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70 space-y-3" x-show="!isEventMode">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">PIC / Guru BK</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama PIC / BK *</label>
                                        <input type="text" name="pic_name" x-model="picName" :required="!isEventMode" placeholder="Nama guru / kepala sekolah" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp PIC *</label>
                                        <input type="tel" name="pic_whatsapp" x-model="picWhatsapp" :required="!isEventMode" placeholder="08xxxxxxxxxx" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                </div>
                            </div>

                            <!-- Section: POTENSI -->
                            <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70 space-y-3">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Potensi Kerjasama</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Potensi Mahasiswa / Pendaftar Baru</label>
                                        <input type="text" name="potensi_mahasiswa" placeholder="Jumlah kuota / estimasi siswa" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Detail Potensi Mahasiswa (Prodi Diminati dll)</label>
                                        <input type="text" name="detail_potensi_mahasiswa" placeholder="Contoh: Minat tinggi pada Prodi Teknik Informatika & Bisnis Digital" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FORM CORPORATE -->
                    <div x-show="kunjunganType === 'corporate'">
                        <div class="space-y-4">
                            <!-- Event Mode: readonly info -->
                            <div x-show="isEventMode" class="bg-indigo-50/80 p-3.5 rounded-xl border border-indigo-200/70 space-y-3">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Informasi Perusahaan / Tempat</h4>
                                <div class="grid grid-cols-1 gap-3">
                                    <div>
                                        <p class="text-[11px] font-bold text-slate-500 mb-0.5">Perusahaan / Tempat</p>
                                        <p class="text-sm font-bold text-slate-900" x-text="eventTempat || namaInstitusi || '—'"></p>
                                        <input type="hidden" name="nama_institusi" :value="eventTempat || namaInstitusi">
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold text-slate-500 mb-0.5">PIC / HRD</p>
                                        <p class="text-sm font-bold text-slate-900" x-text="picName || '—'"></p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold text-slate-500 mb-0.5">Nomor WhatsApp PIC</p>
                                        <p class="text-sm font-bold text-slate-900" x-text="picWhatsapp || '—'"></p>
                                    </div>
                                </div>
                                <input type="hidden" name="pic_name" :value="picName">
                                <input type="hidden" name="pic_whatsapp" :value="picWhatsapp">
                            </div>

                            <!-- Non-Event Mode: Informasi Perusahaan -->
                            <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70 space-y-3" x-show="!isEventMode">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Informasi Perusahaan</h4>
                                    <span class="text-[11px] text-purple-600 font-medium">Database Corporate</span>
                                </div>

                                <!-- Corporate Selection (Search Plugin & Manual Mode) -->
                                <div 
                                    x-on:switch-manual.stop="if ($event.detail.name === 'corp_source_select') { 
                                        modeManualCorp = true; 
                                        perusahaan_manual = $event.detail.search; 
                                        namaInstitusi = $event.detail.search; 
                                        selectedPerusahaanId = ''; 
                                        $nextTick(function() { 
                                            const inp = $el.querySelector('input[name=perusahaan_manual]'); 
                                            if(inp) { inp.value = $event.detail.search; inp.focus(); } 
                                        }); 
                                    }" 
                                    class="space-y-1.5"
                                >
                                    <div class="flex items-center justify-between mb-1 h-5">
                                        <template x-if="!modeManualCorp">
                                            <div class="flex items-center justify-between w-full">
                                                <label class="block text-xs font-semibold text-slate-700">Pilih Perusahaan <span class="text-rose-500">*</span></label>
                                                <button 
                                                    type="button" 
                                                    @click="modeManualCorp = true; selectedPerusahaanId = ''; const sel = $el.closest('.space-y-1.5').querySelector('input[name=corp_source_select]'); if(sel) sel.value = '';" 
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200/60 transition cursor-pointer group"
                                                >
                                                    <svg class="w-3 h-3 text-purple-500 group-hover:text-purple-700 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                    <span>Input Manual</span>
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="modeManualCorp">
                                            <div class="flex items-center justify-between w-full">
                                                <div class="flex items-center gap-1.5">
                                                    <label class="block text-xs font-semibold text-slate-700">Nama Perusahaan <span class="text-rose-500">*</span></label>
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/80">
                                                        Manual
                                                    </span>
                                                </div>
                                                <button 
                                                    type="button" 
                                                    @click="modeManualCorp = false; perusahaan_manual = '';" 
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 transition cursor-pointer"
                                                >
                                                    <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                    <span>Cari Database Perusahaan</span>
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    <div x-show="!modeManualCorp" x-on:change="if ($event.detail.name === 'corp_source_select') handleCorpSelect($event.detail.value)">
                                        <x-searchable-select 
                                            name="corp_source_select" 
                                            :options="$modalCorpOptions" 
                                            placeholder="-- Cari & Pilih Perusahaan dari Database --" 
                                        />
                                    </div>

                                    <div x-show="modeManualCorp" style="display: none;" class="space-y-1.5">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-purple-500">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            </div>
                                            <input 
                                                type="text" 
                                                name="perusahaan_manual" 
                                                x-model="perusahaan_manual" 
                                                @input="namaInstitusi = $event.target.value"
                                                placeholder="Ketik nama perusahaan manual (misal: PT Telkom Indonesia)..." 
                                                class="w-full text-xs sm:text-sm pl-10 pr-3.5 py-2.5 rounded-xl border border-purple-200 bg-purple-50/20 text-slate-800 font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition shadow-2xs"
                                            >
                                        </div>
                                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50/70 border border-amber-200/50 text-[10px] text-amber-800 font-medium">
                                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>Perusahaan baru akan otomatis tersimpan ke master data.</span>
                                        </div>
                                    </div>

                                    <input type="hidden" name="perusahaan_id" :value="modeManualCorp ? '' : selectedPerusahaanId">
                                    <p class="text-[11px] text-slate-500 mt-1" x-show="!modeManualCorp">Memilih perusahaan otomatis mengisi data HRD / PIC di bawah.</p>
                                </div>
                            </div>

                            <!-- Non-Event Mode: PIC / HRD -->
                            <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70 space-y-3" x-show="!isEventMode">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">PIC / HRD</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama HRD / PIC *</label>
                                        <input type="text" name="pic_name" x-model="picName" :required="!isEventMode" placeholder="Ibu Maya (HR Manager)" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">WhatsApp HRD *</label>
                                        <input type="tel" name="pic_whatsapp" x-model="picWhatsapp" :required="!isEventMode" placeholder="08xxxxxxxxxx" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                </div>
                            </div>

                            <!-- Section: POTENSI CORPORATE -->
                            <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70 space-y-3">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Potensi Program</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Potensi S1/S2 Karyawan</label>
                                        <input type="text" name="potensi_s1" placeholder="Estimasi peserta kelas karyawan" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Potensi CSR / Hibah</label>
                                        <input type="text" name="potensi_csr" placeholder="Program beasiswa CSR perusahaan" class="w-full text-xs sm:text-sm px-3 py-2 rounded-lg bg-white border border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: DOKUMENTASI FOTO (NO GPS) -->
                    <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70 space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Dokumentasi Foto Kunjungan</h4>
                            <span class="text-[11px] text-slate-400">Hanya foto (Tanpa GPS)</span>
                        </div>

                        <div class="border-2 border-dashed border-slate-200 hover:border-blue-400 bg-white rounded-xl p-4 text-center transition relative cursor-pointer" @click="document.getElementById('foto_upload').click()">
                            <!-- File input is always in DOM -->
                            <input type="file" id="foto_upload" name="foto" accept="image/*" class="sr-only" @click.stop @change="
                                const file = $event.target.files[0];
                                if (file) {
                                    const reader = new FileReader();
                                    reader.onload = (e) => { photoPreview = e.target.result; };
                                    reader.readAsDataURL(file);
                                }
                            ">

                            <div x-show="!photoPreview">
                                <svg class="mx-auto h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2-2v12a2 2 0 002 2z" />
                                </svg>
                                <div class="mt-2 text-xs text-slate-600">
                                    <span class="relative font-semibold text-blue-600 hover:text-blue-500">
                                        Upload foto dokumentasi
                                    </span>
                                    <span class="text-slate-400 block mt-0.5">PNG, JPG hingga 10MB</span>
                                </div>
                            </div>

                            <div x-show="photoPreview" x-cloak>
                                <div class="relative inline-block">
                                    <img :src="photoPreview" class="max-h-40 rounded-lg shadow-xs object-cover mx-auto" alt="Preview foto">
                                    <button type="button" @click.stop="photoPreview = null; document.getElementById('foto_upload').value = ''" class="absolute -top-2 -right-2 bg-rose-500 text-white rounded-full p-1 shadow-md hover:bg-rose-600 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: GEOLOCATION / LOKASI -->
                    <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70 space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Lokasi Kunjungan (Geo-tagging)</h4>
                            <span class="text-[11px] text-slate-400">Wajib radius 100m</span>
                        </div>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                            <button type="button" @click="getGeolocation()" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold rounded-lg transition shrink-0">
                                Refresh Lokasi
                            </button>
                            <div class="text-xs">
                                <span class="font-semibold text-slate-600" x-text="geoStatus"></span>
                                <span x-show="lat" class="text-slate-500 block sm:inline mt-1 sm:mt-0 sm:ml-2 text-[10px]" x-text="lat + ', ' + lng"></span>
                            </div>
                        </div>
                        <p x-show="!lat && geoStatus.includes('Gagal')" class="text-[11px] text-rose-500 font-medium">Mohon izinkan akses lokasi (GPS) pada browser Anda untuk dapat menyimpan kunjungan.</p>
                    </div>

                    <!-- Section: PRODI & DOSEN PEMATERI -->
                    <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200/70 space-y-4">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5">
                                    <span>Program Studi yang Dipromosikan</span>
                                    <span class="text-[10px] text-blue-700 bg-blue-100 font-bold px-2 py-0.5 rounded-full">Bisa Pilih Beberapa</span>
                                </h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">Centang satu atau beberapa program studi yang dipromosikan / diminati.</p>
                            </div>
                            <div class="flex items-center gap-2 text-[11px]">
                                <button type="button" @click="$el.closest('.space-y-4').querySelectorAll('input[name=\'prodi_ids[]\']').forEach(cb => cb.checked = true)" class="text-blue-600 hover:text-blue-800 font-semibold cursor-pointer">Pilih Semua</button>
                                <span class="text-slate-300">|</span>
                                <button type="button" @click="$el.closest('.space-y-4').querySelectorAll('input[name=\'prodi_ids[]\']').forEach(cb => cb.checked = false)" class="text-slate-500 hover:text-slate-700 font-semibold cursor-pointer">Hapus Pilihan</button>
                            </div>
                        </div>

                        @php
                            $prodisList = \App\Models\Prodi::where('status', 'Aktif')->orderBy('nama')->get();
                        @endphp
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-52 overflow-y-auto pr-1 p-1">
                            @foreach($prodisList as $prd)
                                <label class="flex items-start gap-2.5 p-2.5 rounded-xl border border-slate-200 bg-white hover:bg-blue-50/40 hover:border-blue-300 transition cursor-pointer group select-none shadow-2xs">
                                    <input 
                                        type="checkbox" 
                                        name="prodi_ids[]" 
                                        value="{{ $prd->id }}" 
                                        class="w-4 h-4 mt-0.5 text-blue-600 rounded border-slate-300 focus:ring-blue-500 shrink-0"
                                    >
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-slate-800 group-hover:text-blue-900 leading-tight">
                                            {{ $prd->nama }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-medium mt-0.5 flex items-center gap-1.5">
                                            <span class="inline-block px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-semibold text-[9px]">{{ $prd->jenjang }}</span>
                                            <span class="truncate">{{ $prd->fakultas }}</span>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <div class="pt-2 border-t border-slate-200/60">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Dosen / Pemateri <span class="text-slate-400 font-normal">(Opsional)</span></label>
                            <input type="text" name="dosen_pemateri" placeholder="Contoh: Dr. Ir. H. Ahmad, M.T." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                        </div>
                    </div>

                    <!-- Section: CATATAN -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Kunjungan *</label>
                        <textarea name="catatan" rows="2" required placeholder="Ringkasan hasil pertemuan dan tindak lanjut..." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"></textarea>
                    </div>

                    <!-- JADIKAN PROSPEK -->
                    <div class="flex items-center gap-2 p-3 bg-blue-50 rounded-xl border border-blue-100">
                        <input type="checkbox" id="jadikan_prospek" name="jadikan_prospek" value="1" class="w-4 h-4 text-blue-600 bg-white border-slate-300 rounded focus:ring-blue-500">
                        <label for="jadikan_prospek" class="text-xs sm:text-sm font-semibold text-blue-800 cursor-pointer">
                            Sekaligus tambahkan sebagai Prospek Baru (Masuk ke antrean Follow Up)
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button type="button" @click="modalTambahKunjungan = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">Batal</button>
                        <button type="submit" :disabled="!lat || !photoPreview" :class="(!lat || !photoPreview) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-semibold shadow-xs transition">Simpan Kunjungan</button>
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
        // Global SweetAlert2 Confirmation Dialog
        window.confirmAction = function(options = {}, callback) {
            const isDanger = options.isDanger || options.icon === 'warning' || options.icon === 'error';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: options.title || (isDanger ? 'Konfirmasi Tindakan' : 'Konfirmasi'),
                    text: options.text || 'Apakah Anda yakin ingin melanjutkan?',
                    icon: options.icon || (isDanger ? 'warning' : 'question'),
                    showCancelButton: true,
                    confirmButtonColor: options.confirmButtonColor || (isDanger ? '#e11d48' : '#2563eb'),
                    cancelButtonColor: options.cancelButtonColor || '#64748b',
                    confirmButtonText: options.confirmButtonText || (isDanger ? 'Ya, Lanjutkan' : 'Ya, Setuju'),
                    cancelButtonText: options.cancelButtonText || 'Batal',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-2xl border border-slate-100 p-6',
                        title: 'text-slate-900 font-bold text-lg',
                        htmlContainer: 'text-slate-600 text-xs mt-1',
                        confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2.5 shadow-xs',
                        cancelButton: 'rounded-xl text-xs font-semibold px-4 py-2.5'
                    }
                }).then((result) => {
                    if (result.isConfirmed && typeof callback === 'function') {
                        callback();
                    }
                });
            } else {
                if (confirm(options.text || 'Apakah Anda yakin?')) {
                    if (typeof callback === 'function') callback();
                }
            }
        };

        // Setup global confirm
        window.confirmLogout = function() {
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: 'Apakah Anda yakin ingin keluar dari aplikasi?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logout-form').submit();
                }
            });
        };

        // Initialize TomSelect for elements with tom-select-init class
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.tom-select-init').forEach((el) => {
                new TomSelect(el, {
                    create: false,
                    sortField: {
                        field: "text",
                        direction: "asc"
                    }
                });
            });
        });

        // Automatic SweetAlert2 Form Confirmation for forms with data-confirm
        document.addEventListener('submit', function(e) {
            const form = e.target;
            const confirmMsg = form.getAttribute('data-confirm');
            if (confirmMsg && !form.dataset.confirmed) {
                e.preventDefault();
                const action = form.action || '';
                const isDelete   = form.querySelector('input[name="_method"][value="DELETE"]') || action.includes('destroy') || action.includes('delete');
                const isApprove  = action.includes('/approve');
                const isReject   = action.includes('/reject');
                const isToggle   = action.includes('/toggle');
                const isReset    = action.includes('/reset-password');

                let opts = {
                    title: 'Konfirmasi',
                    text: confirmMsg,
                    icon: 'question',
                    confirmButtonColor: '#2563eb',
                    confirmButtonText: 'Ya, Lanjutkan',
                    cancelButtonText: 'Batal',
                };

                if (isApprove) {
                    opts = { ...opts, title: 'ACC Pendaftaran', icon: 'question', confirmButtonColor: '#10b981', confirmButtonText: 'Ya, ACC Sekarang' };
                } else if (isReject) {
                    opts = { ...opts, title: 'Tolak Pendaftaran', icon: 'warning', confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Tolak' };
                } else if (isDelete) {
                    opts = { ...opts, title: 'Hapus Pengguna', icon: 'warning', confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Hapus', isDanger: true };
                } else if (isToggle) {
                    opts = { ...opts, title: 'Ubah Status Akun', icon: 'question', confirmButtonColor: '#f59e0b', confirmButtonText: 'Ya, Ubah' };
                } else if (isReset) {
                    opts = { ...opts, title: 'Reset Password', icon: 'warning', confirmButtonColor: '#f59e0b', confirmButtonText: 'Ya, Reset' };
                }

                window.confirmAction(opts, function() {
                    form.dataset.confirmed = 'true';
                    form.submit();
                });
            }
        });

        // Flash message handling on load with SweetAlert2 Toast / Modal
        function triggerFlashAlerts() {
            @if(session('success'))
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: {!! json_encode(session('success')) !!},
                        timer: 3500,
                        timerProgressBar: true,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        customClass: {
                            popup: 'rounded-xl shadow-lg border border-emerald-200 bg-white'
                        }
                    });
                }
            @endif

            @if(session('error'))
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Perhatian!',
                        text: {!! json_encode(session('error')) !!},
                        confirmButtonColor: '#e11d48',
                        confirmButtonText: 'Tutup',
                        customClass: {
                            popup: 'rounded-2xl shadow-xl border border-rose-100',
                            confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2.5'
                        }
                    });
                }
            @endif

            @if(isset($errors) && $errors->any())
                if (typeof Swal !== 'undefined') {
                    var errorList = @json($errors->all());
                    var errorHtml = '<ul class="text-left text-xs text-rose-700 space-y-1.5 list-disc pl-5 mt-2">';
                    errorList.forEach(function(msg) {
                        var safeMsg = String(msg).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        errorHtml += '<li>' + safeMsg + '</li>';
                    });
                    errorHtml += '</ul>';

                    Swal.fire({
                        icon: 'error',
                        title: 'Validasi Gagal',
                        html: errorHtml,
                        confirmButtonColor: '#e11d48',
                        confirmButtonText: 'Perbaiki',
                        customClass: {
                            popup: 'rounded-2xl shadow-xl border border-rose-100',
                            confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2.5'
                        }
                    });
                }
            @endif
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                triggerFlashAlerts();
                scrollToActiveMenu();
            });
        } else {
            setTimeout(() => {
                triggerFlashAlerts();
                scrollToActiveMenu();
            }, 100);
        }

        function scrollToActiveMenu() {
            const activeLink = document.querySelector('aside a.bg-blue-50, aside a.bg-purple-50, aside a.bg-emerald-50, aside a.bg-indigo-50');
            if (activeLink) {
                activeLink.scrollIntoView({ block: 'center' });
            }
        }
    </script>
</body>
</html>
