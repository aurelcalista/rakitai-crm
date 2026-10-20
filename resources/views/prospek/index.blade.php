@php
    $pageTitle = 'Daftar Prospek Inbound';
    $pageSubtitle = 'Database Prospek Sekolah, Corporate & Individu UCIC';
@endphp

<x-app-layout :title="'Prospek - CRM UCIC'">

    <!-- State Simulation Wrappers -->
    <div x-show="$store.crm.activeState === 'loading'" x-cloak>
        <x-loading-skeleton type="table" />
    </div>

    <div x-show="$store.crm.activeState === 'empty'" x-cloak>
        <x-empty-state 
            title="Belum ada prospek ditemukan" 
            description="Tidak ada data prospek yang sesuai dengan kriteria filter pencarian Anda."
            :actionLabel="in_array(strtolower(auth()->user()->role ?? ''), ['sales', 'cs']) ? 'Tambah Prospek Baru' : null"
            :actionClick="in_array(strtolower(auth()->user()->role ?? ''), ['sales', 'cs']) ? 'modalTambahProspek = true' : null"
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak>
        <x-error-state />
    </div>

    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6" x-data="{
        searchQuery: '',
        selectedScope: '{{ request('scope', 'all') }}',
        selectedStatus: 'all',
        selectedType: 'all',
        selectedTakeover: 'all',
        selectedGelombang: 'all',
        currentPage: 1,
        perPage: 25,
        authUserId: {{ auth()->id() ?? 0 }},
        userRole: '{{ strtolower(auth()->user()->role ?? '') }}',
        prospectsList: {{ json_encode($prospects) }},
        modalPresensiManual: {{ old('form_type') === 'presensi_manual' ? 'true' : 'false' }},
        
        isOwnProspect(p) {
            if (p.is_own === true) return true;
            if (!this.authUserId) return false;
            if (this.userRole && this.userRole.toLowerCase() === 'cs') {
                return Boolean(p.cs_id == this.authUserId || p.owner_id == this.authUserId || p.takeover_by_id == this.authUserId);
            }
            return Boolean(p.sales_id == this.authUserId || p.owner_id == this.authUserId || p.takeover_by_id == this.authUserId);
        },
        isOtherProspect(p) {
            if (this.isOwnProspect(p)) return false;
            if (this.userRole && this.userRole.toLowerCase() === 'cs') {
                const hasOtherCs = Boolean((p.cs_id && p.cs_id != this.authUserId) || (p.owner_id && p.owner_id != this.authUserId && p.cs_name && p.cs_name !== '-') || (p.takeover_by_id && p.takeover_by_id != this.authUserId));
                return Boolean(p.is_other === true || hasOtherCs);
            }
            const hasSalesOwner = Boolean(p.sales_id || (p.owner_id && p.owner_id !== '-') || p.takeover_by_id);
            return Boolean(p.is_other === true || hasSalesOwner);
        },
        isPresensiSiswa(p) {
            return Boolean(p.is_presensi === true || (p.source && p.source.toLowerCase().includes('presensi')));
        },
        updateUrlScope(scope) {
            this.selectedScope = scope;
            try {
                const url = new URL(window.location.href);
                if (scope === 'all') {
                    url.searchParams.delete('scope');
                } else {
                    url.searchParams.set('scope', scope);
                }
                window.history.replaceState({}, '', url.toString());
            } catch(e) {}
        },
        get filteredProspects() {
            return this.prospectsList.filter(p => {
                const q = this.searchQuery.toLowerCase().trim();
                const matchSearch = !q || 
                    (p.name && p.name.toLowerCase().includes(q)) || 
                    (p.no_registrasi && p.no_registrasi.toLowerCase().includes(q)) ||
                    (p.gelombang && p.gelombang.toLowerCase().includes(q)) ||
                    (p.pic && p.pic.toLowerCase().includes(q)) ||
                    (p.whatsapp && p.whatsapp.includes(q)) ||
                    (p.sekolah_name && p.sekolah_name.toLowerCase().includes(q)) ||
                    (p.wilayah_name && p.wilayah_name.toLowerCase().includes(q)) ||
                    (p.sales_name && p.sales_name.toLowerCase().includes(q)) ||
                    (p.cs_name && p.cs_name.toLowerCase().includes(q)) ||
                    (p.takeover_cs && p.takeover_cs.toLowerCase().includes(q)) ||
                    (p.sekolah_name && p.sekolah_name.toLowerCase().includes(q)) ||
                    (p.owner && p.owner.toLowerCase().includes(q));
                
                const matchStatus = this.selectedStatus === 'all' || p.status === this.selectedStatus;
                const matchType = this.selectedType === 'all' || p.type === this.selectedType;
                const matchGelombang = this.selectedGelombang === 'all' || p.gelombang === this.selectedGelombang;
                const matchTakeover = this.selectedTakeover === 'all' || 
                    (this.selectedTakeover === 'sales' && p.active_takeover && p.active_takeover.includes('Sales')) ||
                    (this.selectedTakeover === 'cs' && p.active_takeover && p.active_takeover.includes('CS')) ||
                    (this.selectedTakeover === 'taken' && p.status_takeover === 'Sudah Di-takeover') ||
                    (this.selectedTakeover === 'untaken' && p.status_takeover === 'Belum Di-takeover');

                let matchScope = true;
                if (this.selectedScope === 'own') {
                    matchScope = this.isOwnProspect(p);
                } else if (this.selectedScope === 'others') {
                    matchScope = this.isOtherProspect(p);
                } else if (this.selectedScope === 'presensi') {
                    matchScope = this.isPresensiSiswa(p);
                }
                
                return matchSearch && matchStatus && matchType && matchTakeover && matchGelombang && matchScope;
            });
        },
        get paginatedProspects() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredProspects.slice(start, start + this.perPage);
        },
        get totalPages() {
            return Math.ceil(this.filteredProspects.length / this.perPage) || 1;
        },
        getProspectUrl(prospect) {
            const role = '{{ strtolower(auth()->user()->role ?? '') }}';
            if (role === 'sales') {
                return '/sales/prospek/' + prospect.id;
            }
            if (role === 'spv') {
                return '/spv/prospek/' + prospect.id;
            }
            return '/prospek/' + prospect.id;
        },
        modalDeleteProspek: false,
        deleteProspectTarget: null,
        openDeleteProspectModal(prospect) {
            this.deleteProspectTarget = prospect;
            this.modalDeleteProspek = true;
        },
        getDeleteProspectUrl(prospect) {
            if (!prospect) return '#';
            const role = '{{ strtolower(auth()->user()->role ?? '') }}';
            if (role === 'sales') {
                return '/sales/prospek/' + prospect.id;
            }
            if (role === 'spv') {
                return '/spv/prospek/' + prospect.id;
            }
            return '/prospek/' + prospect.id;
        }
    }" x-effect="searchQuery; selectedScope; selectedStatus; selectedType; selectedTakeover; selectedGelombang; currentPage = 1">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Manajemen Prospek</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola data calon mahasiswa, sekolah mitra, dan instansi inbound UCIC.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                {{-- Tombol Unduh Spreadsheet --}}
                <a 
                    :href="'{{ route('export.prospek') }}?' + new URLSearchParams(Object.fromEntries(Object.entries({
                        scope: selectedScope,
                        status: selectedStatus !== 'all' ? selectedStatus : '',
                        type: selectedType !== 'all' ? selectedType : '',
                        gelombang: selectedGelombang !== 'all' ? selectedGelombang : '',
                        takeover: selectedTakeover !== 'all' ? selectedTakeover : '',
                        search: searchQuery || '',
                        start_date: '{{ request('start_date', '') }}',
                        end_date: '{{ request('end_date', '') }}'
                    }).filter(([_, v]) => v !== '' && v !== null && v !== undefined)))"
                    target="_blank"
                    class="px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs sm:text-sm font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer"
                    title="Unduh Spreadsheet Data Prospek (.xlsx)"
                >
                    <svg class="w-4 h-4 text-emerald-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Unduh Spreadsheet</span>
                </a>

                @if(in_array(auth()->user()->role ?? '', ['Sales', 'CS', 'Admin']))
                    @if(in_array(auth()->user()->role ?? '', ['CS', 'Admin', 'Sales']))
                    <button 
                        type="button" 
                        @click="modalPresensiManual = true"
                        class="px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 text-white text-xs sm:text-sm font-bold shadow-xs transition flex items-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Tambah Presensi Siswa</span>
                    </button>
                    @endif
                    <button 
                        type="button" 
                        @click="modalTambahProspek = true"
                        class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs sm:text-sm font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Prospek</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- Prospect Activity Metrics & Period Filter (CS & Sales) --}}
        @php
            $userRoleLower = strtolower(auth()->user()->role ?? '');
            $metricsData = $userRoleLower === 'sales' ? ($salesMetrics ?? null) : ($csMetrics ?? null);
            $showMetrics = isset($metricsData) && in_array($userRoleLower, ['sales', 'cs', 'admin']);
            $metricsTitle = $userRoleLower === 'sales' ? 'Aktivitas & Metrik Prospek Sales' : 'Aktivitas & Metrik Prospek CS';
        @endphp
        @if($showMetrics)
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <span>{{ $metricsTitle }}</span>
                        @if(!empty($metricsData['start_date']) || !empty($metricsData['end_date']))
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                Periode: {{ $metricsData['start_date'] ?? 'Awal' }} s/d {{ $metricsData['end_date'] ?? 'Kini' }}
                            </span>
                        @endif
                    </h3>
                    <p class="text-[11px] text-slate-500">Pantau produktivitas penanganan dan interaksi prospek berdasarkan rentang periode.</p>
                </div>
                {{-- Date Filter Form --}}
                <form method="GET" action="{{ url()->current() }}" class="flex flex-wrap items-center gap-2 text-xs">
                    @if(request()->filled('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                    @if(request()->filled('q'))
                        <input type="hidden" name="q" value="{{ request('q') }}">
                    @endif
                    <div class="flex items-center gap-1.5">
                        <label class="text-[11px] font-semibold text-slate-500">Dari:</label>
                        <input type="date" name="start_date" value="{{ $metricsData['start_date'] ?? '' }}"
                               class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-700 text-xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>
                    <div class="flex items-center gap-1.5">
                        <label class="text-[11px] font-semibold text-slate-500">Sampai:</label>
                        <input type="date" name="end_date" value="{{ $metricsData['end_date'] ?? '' }}"
                               class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-700 text-xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>
                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-semibold transition cursor-pointer text-xs">
                        Filter
                    </button>
                    @if(!empty($metricsData['start_date']) || !empty($metricsData['end_date']))
                        <a href="{{ url()->current() }}" class="px-2.5 py-1.5 rounded-xl text-slate-500 hover:text-slate-800 font-semibold transition text-xs">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            {{-- 4 Metric Cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                {{-- 1. Diinput --}}
                <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/50">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-600">Prospek Diinput</span>
                        <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-slate-900">{{ $metricsData['diinput'] }}</span>
                        <span class="text-[11px] text-slate-500">prospek</span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Dibuat dalam periode</p>
                </div>

                {{-- 2. Dihubungi / Prospek Aktif --}}
                <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/50">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-600">{{ $userRoleLower === 'sales' ? 'Prospek Aktif' : 'Prospek Dihubungi' }}</span>
                        <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-slate-900">{{ $metricsData['dihubungi'] }}</span>
                        <span class="text-[11px] text-slate-500">prospek unik</span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">{{ $userRoleLower === 'sales' ? 'Memiliki histori interaksi' : 'Histori kontak tercatat' }}</p>
                </div>

                {{-- 3. Di-Follow-up --}}
                <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/50">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-600">Di-Follow-up</span>
                        <span class="w-7 h-7 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-slate-900">{{ $metricsData['follow_up'] }}</span>
                        <span class="text-[11px] text-slate-500">prospek unik</span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Tercatat follow-up</p>
                </div>

                {{-- 4. Hot Prospect --}}
                <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/50">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-600">Hot Prospect</span>
                        <span class="w-7 h-7 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/></svg>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-rose-900">{{ $metricsData['hot_prospect'] }}</span>
                        <span class="text-[11px] text-rose-700">prospek</span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Status/transisi HOT</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Search & Filter Controls -->
        <div class="crm-card bg-white p-4 sm:p-5 space-y-4">
            
            <!-- Multirole Scope Filter Tabs: Semua Prospek, Prospek Saya, Prospek User Lain, Presensi Siswa -->
            <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl w-fit border border-slate-200/60 flex-wrap">
                <button 
                    type="button" 
                    @click="updateUrlScope('all')" 
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer flex items-center gap-1.5"
                    :class="selectedScope === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                >
                    <span>Semua Prospek</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                          :class="selectedScope === 'all' ? 'bg-slate-100 text-slate-800' : 'bg-slate-200/70 text-slate-600'"
                          x-text="prospectsList.length"></span>
                </button>
                <button 
                    type="button" 
                    @click="updateUrlScope('own')" 
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer flex items-center gap-1.5"
                    :class="selectedScope === 'own' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                >
                    <span>Prospek Saya</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                          :class="selectedScope === 'own' ? 'bg-blue-100 text-blue-800' : 'bg-slate-200/70 text-slate-600'"
                          x-text="prospectsList.filter(p => isOwnProspect(p)).length"></span>
                </button>
                <button 
                    type="button" 
                    @click="updateUrlScope('others')" 
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer flex items-center gap-1.5"
                    :class="selectedScope === 'others' ? 'bg-white text-amber-700 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                >
                    <span x-text="userRole.toLowerCase() === 'cs' ? 'Prospek CS Lain' : (userRole.toLowerCase() === 'sales' ? 'Follow-Up Sales Lain' : 'Prospek User Lain')"></span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                          :class="selectedScope === 'others' ? 'bg-amber-100 text-amber-800' : 'bg-slate-200/70 text-slate-600'"
                          x-text="prospectsList.filter(p => isOtherProspect(p)).length"></span>
                </button>
                <button 
                    type="button" 
                    @click="updateUrlScope('presensi')" 
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer flex items-center gap-1.5"
                    :class="selectedScope === 'presensi' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                >
                    <span>Presensi Siswa</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                          :class="selectedScope === 'presensi' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200/70 text-slate-600'"
                          x-text="prospectsList.filter(p => isPresensiSiswa(p)).length"></span>
                </button>
            </div>
            
            <!-- Big Search Bar -->
            <div class="relative">
                <input 
                    type="text" 
                    x-model="searchQuery"
                    placeholder="Cari no registrasi, nama siswa, instansi, kontak PIC, telepon, gelombang, sales, CS..." 
                    class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 text-xs sm:text-sm transition bg-slate-50/50 focus:bg-white"
                >
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <button 
                    x-show="searchQuery" 
                    @click="searchQuery = ''"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- Filter Dropdowns & Chips -->
            <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-slate-100 text-xs">
                <!-- Takeover Filter -->
                <select x-model="selectedTakeover" class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:ring-2 focus:ring-blue-500/20">
                    <option value="all">Semua Takeover</option>
                    <option value="sales">Takeover Sales</option>
                    <option value="cs">Takeover CS</option>
                </select>
                
                @if(isset($isHm) && $isHm)
                @php
                    $selectedSpvObj = isset($spvs) ? $spvs->firstWhere('id', request('spv_id')) : null;
                    $selectedSpvNama = $selectedSpvObj ? $selectedSpvObj->name : 'Semua SPV';
                @endphp
                <div class="relative" x-data="{
                    open: false,
                    search: '',
                    selectedId: '{{ request('spv_id', '') }}',
                    selectedNama: '{{ addslashes($selectedSpvNama) }}',
                    items: [
                        { id: '', nama: 'Semua SPV' },
                        @if(isset($spvs))
                        @foreach($spvs as $spv)
                            { id: '{{ $spv->id }}', nama: '{{ addslashes($spv->name) }}' },
                        @endforeach
                        @endif
                    ],
                    get filteredItems() {
                        if (!this.search.trim()) return this.items;
                        return this.items.filter(item => item.nama.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    selectSpv(item) {
                        window.location.href = '?spv_id=' + item.id;
                    }
                }" @click.outside="open = false">

                    <button type="button" @click="open = !open" class="px-3 py-2 text-xs rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-semibold focus:ring-2 focus:ring-purple-500/20 flex items-center gap-2 cursor-pointer transition">
                        <svg class="w-3.5 h-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span x-text="selectedId ? selectedNama : 'Semua SPV'"></span>
                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute left-0 mt-1.5 w-60 bg-white rounded-2xl shadow-xl border border-slate-200/90 z-50 p-2.5 overflow-hidden"
                         style="display: none;">
                        
                        <div class="relative mb-2">
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" 
                                   x-model="search" 
                                   placeholder="Cari Nama SPV..." 
                                   class="w-full text-xs pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 text-slate-800 font-medium"
                                   style="padding-left: 2.25rem !important;"
                                   @keydown.escape="open = false">
                        </div>

                        <div class="max-h-52 overflow-y-auto space-y-0.5 custom-scrollbar">
                            <template x-for="item in filteredItems" :key="item.id">
                                <button type="button" 
                                        @click="selectSpv(item)" 
                                        class="w-full text-left px-3 py-1.5 text-xs rounded-xl transition flex items-center justify-between font-semibold"
                                        :class="selectedId == item.id ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-700 hover:bg-slate-50'">
                                    <span x-text="item.nama"></span>
                                    <svg x-show="selectedId == item.id" class="w-3.5 h-3.5 text-purple-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Filter Sales -->
                <select @change="window.location.href = '?spv_id={{ request('spv_id') }}&sales_id=' + $event.target.value" class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Semua Sales</option>
                    @foreach($salesList as $s)
                        <option value="{{ $s->id }}" {{ request('sales_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
                @endif

                <!-- Status Filter -->
                <select x-model="selectedStatus" class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:ring-2 focus:ring-blue-500/20">
                    <option value="all">Semua Status</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}">{{ $st }}</option>
                    @endforeach
                </select>

                <!-- Tipe Prospek Filter -->
                <select x-model="selectedType" class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:ring-2 focus:ring-blue-500/20">
                    <option value="all">Semua Tipe</option>
                    <option value="Sekolah">Sekolah</option>
                    <option value="Corporate">Corporate</option>
                    <option value="Individu">Individu</option>
                </select>

                <!-- Gelombang Filter -->
                <select x-model="selectedGelombang" class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:ring-2 focus:ring-blue-500/20">
                    <option value="all">Semua Gelombang</option>
                    @foreach($gelombangList ?? [] as $gel)
                        <option value="{{ $gel->nama }}">{{ $gel->nama }}</option>
                    @endforeach
                </select>

                <!-- Reset Filter Button -->
                <button 
                    type="button" 
                    @click="selectedScope = 'all'; selectedStatus = 'all'; selectedType = 'all'; selectedTakeover = 'all'; selectedGelombang = 'all'; searchQuery = ''; @if(isset($isHm) && $isHm && (request('spv_id') || request('sales_id'))) window.location.href = '{{ route('prospek.index') }}'; @endif"
                    class="ml-auto text-slate-400 hover:text-slate-600 font-medium px-2 py-1"
                >
                    Reset Filter
                </button>
            </div>

        </div>

        <!-- Prospect List Container -->
        <div class="crm-card bg-white overflow-hidden">
            
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-800">Menampilkan</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700" x-text="filteredProspects.length + ' Prospek'"></span>
                </div>
            </div>

            <!-- DESKTOP TABLE VIEW -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3.5 px-3 w-10 text-center">No</th>
                            <th class="py-3.5 px-4">Nama Siswa / PIC</th>
                            <th class="py-3.5 px-3">Asal Sekolah / Wilayah</th>
                            <th class="py-3.5 px-3">Kelas Kuliah</th>
                            <th class="py-3.5 px-3">WhatsApp</th>
                            <th class="py-3.5 px-3">Status Pipeline</th>
                            <th class="py-3.5 px-3">Status Takeover</th>
                            <th class="py-3.5 px-3">Owner Awal</th>
                            <th class="py-3.5 px-3">Aktivitas</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(prospect, index) in paginatedProspects" :key="prospect.id">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-4 px-3 text-center font-bold text-slate-400 text-xs" x-text="(currentPage - 1) * perPage + index + 1"></td>
                                <td class="py-4 px-4 font-semibold text-slate-900">
                                    <a :href="getProspectUrl(prospect)" class="hover:text-blue-600 text-xs font-bold block" x-text="(prospect.pic && prospect.pic !== '-') ? prospect.pic : prospect.name"></a>
                                    <div class="flex items-center gap-1.5 flex-wrap mt-1">
                                        <template x-if="prospect.no_registrasi">
                                            <span class="inline-flex items-center gap-1 font-mono text-[10px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200 shadow-2xs" title="No. Registrasi">
                                                <svg class="w-3 h-3 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                                <span x-text="prospect.no_registrasi"></span>
                                            </span>
                                        </template>
                                        <template x-if="prospect.gelombang">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-teal-700 bg-teal-50 px-1.5 py-0.5 rounded border border-teal-200 shadow-2xs" title="Gelombang Pendaftaran">
                                                <svg class="w-3 h-3 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                                <span x-text="prospect.gelombang"></span>
                                            </span>
                                        </template>
                                        <div class="text-[10px] text-slate-400 font-medium" x-show="prospect.is_presensi">
                                            <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold bg-emerald-50 px-1.5 py-0.2 rounded border border-emerald-200 text-[9px]">Presensi Siswa</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-3">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium" x-text="prospect.type"></span>
                                    <div class="font-semibold text-slate-800 text-[11px] mt-1" x-show="prospect.sekolah_name && prospect.sekolah_name !== '-'" x-text="prospect.sekolah_name"></div>
                                    <div class="text-[10px] text-slate-400 font-medium mt-0.5" x-show="prospect.wilayah_name" x-text="prospect.wilayah_name"></div>
                                    <div class="text-[10px] text-slate-500 font-medium" x-show="prospect.asal_kelas && prospect.asal_kelas !== '-'" x-text="'Kelas: ' + prospect.asal_kelas"></div>
                                    <div class="text-[10px] text-slate-500 font-semibold" x-show="prospect.sales_name && prospect.sales_name !== '-'" x-text="'Sales: ' + prospect.sales_name"></div>
                                    <div class="text-[10px] text-teal-600 font-semibold" x-show="(prospect.cs_name && prospect.cs_name !== '-') || (prospect.takeover_cs && prospect.takeover_cs !== '-')" x-text="'CS: ' + ((prospect.cs_name && prospect.cs_name !== '-') ? prospect.cs_name : prospect.takeover_cs)"></div>
                                </td>
                                <td class="py-4 px-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                          :class="(prospect.kelas && prospect.kelas.toLowerCase().includes('karyawan')) ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-blue-50 text-blue-700 border-blue-200'"
                                          x-text="prospect.kelas || 'Reguler'"></span>
                                </td>
                                <td class="py-4 px-3">
                                    <a :href="'https://wa.me/' + prospect.whatsapp.replace(/[^0-9]/g, '')" target="_blank" class="text-[11px] text-emerald-600 hover:text-emerald-700 flex items-center gap-1 font-medium">
                                        <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                        <span x-text="prospect.whatsapp"></span>
                                    </a>
                                </td>
                                <td class="py-4 px-3">
                                    <x-status-badge :status="''" x-text="prospect.status" ::class="'badge-' + prospect.status.toLowerCase().replace(/ /g, '-')" />
                                </td>
                                <td class="py-4 px-3">
                                    <span 
                                        :class="prospect.status_takeover === 'Sudah Di-takeover' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200'"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-semibold border"
                                        x-text="prospect.status_takeover || 'Belum Di-takeover'"
                                    ></span>
                                    <div class="text-[10px] text-slate-500 font-medium mt-0.5" x-show="prospect.takeover_by_name" x-text="'Oleh: ' + prospect.takeover_by_name"></div>
                                    <div class="text-[9px] text-slate-400 mt-0.2" x-show="prospect.takeover_at" x-text="prospect.takeover_at"></div>
                                </td>
                                <td class="py-4 px-3 text-slate-600 font-medium">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span x-text="prospect.owner || 'Sistem'"></span>
                                        <span x-show="!prospect.owner_id || prospect.owner === 'Sistem'" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-100 text-slate-600 border border-slate-200">Sistem</span>
                                        <span x-show="isOtherProspect(prospect)" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200" :title="userRole.toLowerCase() === 'cs' ? 'Prospek milik CS lain' : 'Prospek milik Sales lain'" x-text="userRole.toLowerCase() === 'cs' ? 'CS Lain' : 'Sales Lain'"></span>
                                    </div>
                                </td>
                                <td class="py-4 px-3 text-slate-500 text-[11px]" x-text="prospect.last_activity"></td>
                                <td class="py-4 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <template x-if="prospect.is_presensi && prospect.status_takeover !== 'Sudah Di-takeover'">
                                            <a 
                                                :href="getProspectUrl(prospect)"
                                                class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-[11px] transition shadow-2xs whitespace-nowrap"
                                                title="Takeover Prospek Ini"
                                            >
                                                Takeover
                                            </a>
                                        </template>
                                        <button 
                                            @click="selectedProspect = prospect; modalFollowUp = true"
                                            :class="isOtherProspect(prospect) ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-blue-50 text-blue-700 hover:bg-blue-100'"
                                            class="p-1.5 rounded-lg transition cursor-pointer"
                                            :title="isOtherProspect(prospect) ? (userRole.toLowerCase() === 'cs' ? 'Bantu Follow Up CS Lain' : 'Bantu Follow Up') : 'Follow Up'"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                        </button>
                                        <a 
                                            :href="getProspectUrl(prospect)"
                                            class="p-1.5 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition"
                                            title="Lihat Detail"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                        </a>
                                        <button 
                                            type="button"
                                            @click="openDeleteProspectModal(prospect)"
                                            class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition cursor-pointer"
                                            title="Hapus Prospek"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- MOBILE RESPONSIVE CARDS VIEW -->
            <div class="md:hidden divide-y divide-slate-100">
                <template x-for="prospect in paginatedProspects" :key="prospect.id">
                    <div class="p-4 space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-xs shrink-0" x-text="((prospect.pic && prospect.pic !== '-') ? prospect.pic : prospect.name).substring(0, 2).toUpperCase()"></div>
                                <div>
                                    <a :href="getProspectUrl(prospect)" class="font-bold text-xs text-slate-900 block" x-text="(prospect.pic && prospect.pic !== '-') ? prospect.pic : prospect.name"></a>
                                    <span class="text-[11px] text-slate-500 block" x-text="prospect.type"></span>
                                    <div class="text-[10px] text-slate-500 font-semibold mt-0.5" x-show="prospect.sekolah_name && prospect.sekolah_name !== '-'" x-text="prospect.sekolah_name"></div>
                                    <div class="text-[10px] text-slate-400 font-medium" x-show="prospect.wilayah_name" x-text="prospect.wilayah_name"></div>
                                    <div class="text-[10px] text-slate-500 font-semibold" x-show="prospect.sales_name && prospect.sales_name !== '-'" x-text="'Sales: ' + prospect.sales_name"></div>
                                    <div class="text-[10px] text-teal-600 font-semibold" x-show="(prospect.cs_name && prospect.cs_name !== '-') || (prospect.takeover_cs && prospect.takeover_cs !== '-')" x-text="'CS: ' + ((prospect.cs_name && prospect.cs_name !== '-') ? prospect.cs_name : prospect.takeover_cs)"></div>
                                    <div class="text-[10px] text-slate-400" x-show="prospect.owner" x-text="'Owner: ' + (prospect.owner || 'Sistem')"></div>
                                    <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        <template x-if="prospect.no_registrasi">
                                            <span class="font-mono text-[9px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200" x-text="prospect.no_registrasi"></span>
                                        </template>
                                        <template x-if="prospect.gelombang">
                                            <span class="text-[9px] font-bold text-teal-700 bg-teal-50 px-1.5 py-0.5 rounded border border-teal-200" x-text="prospect.gelombang"></span>
                                        </template>
                                        <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold border"
                                              :class="(prospect.kelas && prospect.kelas.toLowerCase().includes('karyawan')) ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-blue-50 text-blue-700 border-blue-200'"
                                              x-text="'Kelas ' + (prospect.kelas || 'Reguler')"></span>
                                        <span class="px-1.5 py-0.5 rounded-md text-[9px] font-semibold border"
                                              :class="prospect.status_takeover === 'Sudah Di-takeover' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'"
                                              x-text="prospect.status_takeover || 'Belum Di-takeover'"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span 
                                    :class="'badge-' + prospect.status.toLowerCase().replace(/ /g, '-')"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                                    x-text="prospect.status"
                                ></span>
                                <span x-show="isOtherProspect(prospect)" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200" :title="userRole.toLowerCase() === 'cs' ? 'Prospek milik CS lain' : 'Prospek milik Sales lain'" x-text="userRole.toLowerCase() === 'cs' ? 'CS Lain' : 'Sales Lain'"></span>
                            </div>
                        </div>

                        <div class="p-2.5 rounded-xl bg-slate-50/80 border border-slate-100 text-xs space-y-1">

                            <div class="flex justify-between items-center text-[11px]">
                                <span class="text-slate-400">Last Activity:</span>
                                <span class="text-slate-600" x-text="prospect.last_activity"></span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <button 
                                @click="selectedProspect = prospect; modalFollowUp = true"
                                :class="isOtherProspect(prospect) ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-blue-50 text-blue-700 border-blue-200/80'"
                                class="flex-1 py-2 rounded-xl border text-xs font-semibold text-center"
                                x-text="isOtherProspect(prospect) ? 'Bantu Follow Up' : 'Follow Up'"
                            >
                            </button>
                            <button 
                                @click="selectedProspect = prospect; modalUpdateStatus = true"
                                class="py-2 px-3 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold"
                            >
                                Status
                            </button>
                            <a 
                                :href="getProspectUrl(prospect)"
                                class="py-2 px-3 rounded-xl bg-blue-600 text-white text-xs font-semibold shadow-xs"
                            >
                                Detail
                            </a>
                            <button 
                                type="button"
                                @click="openDeleteProspectModal(prospect)"
                                class="py-2 px-3 rounded-xl bg-rose-50 text-rose-600 border border-rose-200 text-xs font-semibold hover:bg-rose-100 cursor-pointer"
                                title="Hapus Prospek"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            <x-table-pagination total="filteredProspects.length" page="currentPage" perPage="perPage" totalPages="totalPages" color="blue" />
        </div>

        <script>
            window.crmSekolahsList = @json(collect($sekolahs ?? [])->map(fn($s) => [
                'id' => is_object($s) ? $s->id : ($s['id'] ?? ''),
                'nama' => is_object($s) ? $s->nama : ($s['nama'] ?? ($s['label'] ?? '')),
                'wilayah' => is_object($s) ? ($s->wilayah ? $s->wilayah->nama : '') : ($s['wilayah'] ?? ($s['sub'] ?? ''))
            ])->values()->all());

            function modalPresensiManualData() {
                return {
                    manualSchool: {{ old('sekolah_manual') ? 'true' : 'false' }},
                    manualSchoolName: @json(old('sekolah_manual', '')),
                    selectedProdi: @json(old('prodi_id', '')),
                    isSubmitting: false,
                    sekolahsList: window.crmSekolahsList || [],
                    async submitPresensi(e) {
                        if (this.manualSchool && this.manualSchoolName.trim()) {
                            const result = await window.checkSchoolDuplicate({
                                manualName: this.manualSchoolName,
                                schoolList: this.sekolahsList,
                                onUseExisting: (matched) => {
                                    this.manualSchool = false;
                                    this.manualSchoolName = '';
                                    const inputSekolah = e.target.querySelector('input[name="sekolah_id"]');
                                    if (inputSekolah) inputSekolah.value = String(matched.id);
                                    const inputManual = e.target.querySelector('input[name="sekolah_manual"]');
                                    if (inputManual) inputManual.value = '';
                                    window.dispatchEvent(new CustomEvent('select-item', {
                                        detail: { name: 'sekolah_id', value: String(matched.id), label: matched.nama }
                                    }));
                                }
                            });
                            if (result === false) {
                                this.isSubmitting = false;
                                return;
                            }
                        }

                        // Ensure clean mutual exclusivity between master school and manual school
                        if (this.manualSchool) {
                            const inputSekolah = e.target.querySelector('input[name="sekolah_id"]');
                            if (inputSekolah) inputSekolah.value = '';
                        } else {
                            const inputManual = e.target.querySelector('input[name="sekolah_manual"]');
                            if (inputManual) inputManual.value = '';
                        }

                        this.isSubmitting = true;
                        e.target.submit();
                    }
                };
            }
        </script>

        <!-- MODAL: TAMBAH PRESENSI SISWA MANUAL (LANGSUNG) -->
        <div 
            x-show="modalPresensiManual" 
            x-cloak 
            class="fixed inset-0 z-50 overflow-y-auto"
            role="dialog" 
            aria-modal="true"
        >
            <div class="flex items-end sm:items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Backdrop -->
                <div 
                    x-show="modalPresensiManual" 
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" 
                    @click="modalPresensiManual = false"
                ></div>

                <!-- Dialog Box -->
                <div 
                    x-show="modalPresensiManual" 
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10 max-h-[90vh] overflow-y-auto border border-slate-100"
                    x-data="modalPresensiManualData()"
                    x-on:switch-manual.window="if ($event.detail.name === 'sekolah_id') { manualSchool = true; manualSchoolName = $event.detail.search; }"
                >
                    <!-- Header -->
                    <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base sm:text-lg font-bold text-slate-900">Tambah Presensi Siswa (Langsung)</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Presensi Siswa</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">Catat kehadiran siswa/calon mahasiswa yang hadir langsung di counter CS atau event.</p>
                            </div>
                        </div>
                        <button @click="modalPresensiManual = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- Validation Error Alert -->
                    @if ($errors->any() && old('form_type') === 'presensi_manual')
                        <div class="mt-4 bg-rose-50 border border-rose-200 text-rose-800 p-3.5 rounded-xl text-xs space-y-1">
                            <div class="font-bold flex items-center gap-1.5 text-rose-900">
                                <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Gagal Menyimpan Presensi:</span>
                            </div>
                            <ul class="list-disc pl-5 space-y-0.5 font-medium">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Form -->
                    <form action="{{ route('prospek.presensi-manual') }}" method="POST" @submit.prevent="submitPresensi($event)" class="mt-4 space-y-4">
                        @csrf
                        <input type="hidden" name="form_type" value="presensi_manual">

                        <!-- 1. Nama Lengkap Siswa -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Siswa <span class="text-rose-500">*</span></label>
                            <input 
                                type="text" 
                                name="nama" 
                                value="{{ old('form_type') === 'presensi_manual' ? old('nama') : '' }}" 
                                required 
                                placeholder="Contoh: Muhammad Rizki Fauzi"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-xs sm:text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
                            >
                        </div>

                        <!-- No. Registrasi & Gelombang -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">No. Registrasi <span class="text-slate-400 font-normal">(Opsional)</span></label>
                                <input 
                                    type="text" 
                                    name="no_registrasi" 
                                    value="{{ old('form_type') === 'presensi_manual' ? old('no_registrasi') : '' }}" 
                                    placeholder="Contoh: PMB-UCIC-895-1789183710" 
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-xs sm:text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition font-mono"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Gelombang <span class="text-slate-400 font-normal">(Opsional)</span></label>
                                <select 
                                    name="gelombang" 
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-xs sm:text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition cursor-pointer"
                                >
                                    <option value="">-- Pilih Gelombang --</option>
                                    @foreach($gelombangList ?? [] as $gel)
                                        <option value="{{ $gel->nama }}" {{ (old('form_type') === 'presensi_manual' && old('gelombang') == $gel->nama) ? 'selected' : '' }}>
                                            {{ $gel->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- 2. Asal Sekolah Selector (Master + Manual Toggle) -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-700">Asal Sekolah <span class="text-rose-500">*</span></label>
                                <button 
                                    type="button" 
                                    @click="manualSchool = !manualSchool" 
                                    class="text-[11px] text-emerald-600 hover:text-emerald-800 font-semibold cursor-pointer underline decoration-emerald-300"
                                >
                                    <span x-show="!manualSchool">+ Sekolah belum ada di list? Ketik manual</span>
                                    <span x-show="manualSchool">&larr; Pilih dari Master Sekolah</span>
                                </button>
                            </div>

                            <!-- Dropdown Master Sekolah -->
                            <div x-show="!manualSchool">
                                <x-searchable-select 
                                    name="sekolah_id" 
                                    :options="$sekolahs ?? []" 
                                    placeholder="-- Ketik untuk mencari Sekolah... --" 
                                    :value="old('form_type') === 'presensi_manual' ? old('sekolah_id') : ''" 
                                />
                            </div>

                            <!-- Manual Input Sekolah -->
                            <div x-show="manualSchool" style="display: none;">
                                <input 
                                    type="text" 
                                    name="sekolah_manual" 
                                    x-model="manualSchoolName"
                                    placeholder="Contoh: SMK NEGERI 1 CIREBON" 
                                    autocomplete="off"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-xs sm:text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
                                >
                                <p class="text-[10px] text-slate-400 mt-1">Jika sekolah belum terdaftar di database, masukkan nama lengkap dan detail sesuai nama resminya. Sekolah baru otomatis dibuat di Master Sekolah.</p>
                            </div>
                        </div>

                        <!-- 3. Asal Kelas / Jurusan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Kelas / Jurusan di Sekolah <span class="text-rose-500">*</span></label>
                            <input 
                                type="text" 
                                name="asal_kelas" 
                                value="{{ old('form_type') === 'presensi_manual' ? old('asal_kelas') : '' }}" 
                                required 
                                placeholder="Contoh: 12 RPL 1 / 12 MIPA 2" 
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-xs sm:text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
                            >
                        </div>

                        <!-- 4. Nomor Kontak (WhatsApp Siswa & Ortu) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp Siswa <span class="text-rose-500">*</span></label>
                                <x-input-whatsapp 
                                    name="no_whatsapp" 
                                    :value="old('form_type') === 'presensi_manual' ? old('no_whatsapp') : ''" 
                                    required 
                                    placeholder="81234567890" 
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp Orang Tua <span class="text-slate-400 font-normal">(Opsional)</span></label>
                                <x-input-whatsapp 
                                    name="wa_ortu" 
                                    :value="old('form_type') === 'presensi_manual' ? old('wa_ortu') : ''" 
                                    placeholder="81298765432" 
                                />
                            </div>
                        </div>

                        <!-- 5. Pilihan Program Studi -->
                        @php
                            $prodisList = isset($prodis) && count($prodis) ? $prodis : \App\Models\Prodi::where('status', 'Aktif')->orderBy('nama')->get();
                        @endphp
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Pilihan Program Studi <span class="text-rose-500">*</span></label>
                            <select 
                                name="prodi_id" 
                                x-model="selectedProdi" 
                                required 
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-xs sm:text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition cursor-pointer"
                            >
                                <option value="">-- Pilih Program Studi yang Diminati --</option>
                                @foreach($prodisList as $prodi)
                                    <option value="{{ $prodi->id }}" {{ (old('form_type') === 'presensi_manual' && old('prodi_id') == $prodi->id) ? 'selected' : '' }}>
                                        {{ $prodi->jenjang }} {{ $prodi->nama }}
                                    </option>
                                @endforeach
                                <option value="lainnya" {{ (old('form_type') === 'presensi_manual' && old('prodi_id') === 'lainnya') ? 'selected' : '' }}>Lainnya / Belum Memilih</option>
                            </select>

                            <!-- Input Prodi Lainnya -->
                            <div x-show="selectedProdi === 'lainnya'" x-transition class="mt-2" style="display: none;">
                                <input 
                                    type="text" 
                                    name="prodi_lainnya" 
                                    value="{{ old('form_type') === 'presensi_manual' ? old('prodi_lainnya') : '' }}" 
                                    :required="selectedProdi === 'lainnya'" 
                                    placeholder="Sebutkan program studi atau peminatan..." 
                                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-xs sm:text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
                                >
                            </div>
                        </div>

                        <!-- 6. Pilihan Tipe Kelas -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Tipe Kelas Kampus <span class="text-rose-500">*</span></label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center justify-center p-2.5 rounded-xl border cursor-pointer text-xs font-bold text-center transition bg-slate-50 border-slate-200 text-slate-700 hover:bg-emerald-50/50 hover:border-emerald-300">
                                    <input type="radio" name="kelas" value="Reguler" required class="mr-2 text-emerald-600 focus:ring-emerald-500" {{ (old('form_type') === 'presensi_manual' ? old('kelas', 'Reguler') : 'Reguler') === 'Reguler' ? 'checked' : '' }}>
                                    <span>Reguler (Pagi)</span>
                                </label>
                                <label class="flex items-center justify-center p-2.5 rounded-xl border cursor-pointer text-xs font-bold text-center transition bg-slate-50 border-slate-200 text-slate-700 hover:bg-emerald-50/50 hover:border-emerald-300">
                                    <input type="radio" name="kelas" value="Karyawan" required class="mr-2 text-emerald-600 focus:ring-emerald-500" {{ (old('form_type') === 'presensi_manual' && old('kelas') === 'Karyawan') ? 'checked' : '' }}>
                                    <span>Karyawan (Sore/Malam)</span>
                                </label>
                            </div>
                        </div>

                        <!-- 7. Catatan Khusus Presensi (Opsional) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan <span class="text-slate-400 font-normal">(Opsional)</span></label>
                            <textarea 
                                name="notes" 
                                rows="2" 
                                placeholder="Contoh: Datang langsung ke kampus bersama orang tua, berminat beasiswa KIP / Prestasi..." 
                                class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-xs sm:text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
                            >{{ old('form_type') === 'presensi_manual' ? old('notes') : '' }}</textarea>
                        </div>

                        <!-- Footer Buttons -->
                        <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="modalPresensiManual = false" 
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="isSubmitting" 
                                class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold shadow-xs transition flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                <svg x-show="!isSubmitting" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg x-show="isSubmitting" x-cloak class="animate-spin h-4 w-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Presensi Siswa'">Simpan Presensi Siswa</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL KONFIRMASI HAPUS PROSPEK -->
        <div 
            x-show="modalDeleteProspek" 
            class="fixed inset-0 z-50 overflow-y-auto"
            x-cloak
            @keydown.escape.window="modalDeleteProspek = false"
        >
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalDeleteProspek = false"></div>

                <div class="relative z-10 bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-sm p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Hapus Data Prospek?</h3>
                    <p class="text-sm text-slate-500 mb-6">
                        Yakin ingin menghapus prospek <strong class="text-slate-800" x-text="deleteProspectTarget ? (deleteProspectTarget.pic || deleteProspectTarget.name) : ''"></strong>? Seluruh histori timeline dan data follow-up terkait juga akan dihapus.
                    </p>
                    <div class="flex items-center gap-3 justify-center">
                        <button
                            type="button"
                            @click="modalDeleteProspek = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <form
                            :action="getDeleteProspectUrl(deleteProspectTarget)"
                            method="POST"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition cursor-pointer shadow-xs"
                            >
                                Ya, Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>

