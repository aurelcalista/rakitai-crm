@php $pageTitle = 'Potensi Wilayah'; @endphp

<x-app-layout :title="'Potensi Wilayah - CRM UCIC'">
    <div class="space-y-6" x-data="{
        provinces: {{ json_encode($provinces) }},
        globalStats: {{ json_encode($globalStats) }},
        userRole: '{{ auth()->user()->role }}',
        currentLevel: '{{ $initialLevel ?? "provinsi" }}',
        selectedProvinsiName: '{{ $initialProvinsi ?? "Jawa Barat" }}',
        selectedKotaId: {{ $initialKotaId ? $initialKotaId : 'null' }},
        selectedKecamatanId: {{ $initialKecamatanId ? $initialKecamatanId : 'null' }},
        selectedSchool: null,
        schoolDetailModal: false,
        activeTab: 'peta', // 'peta', 'ranking'
        searchQuery: '',
        filterPotensi: 'all',
        perPage: 25,
        schoolPage: 1,
        kotaPage: 1,
        kecamatanPage: 1,

        get currentProvinsi() {
            return this.provinces.find(p => p.nama === this.selectedProvinsiName) || this.provinces[0] || null;
        },

        get currentKota() {
            if (!this.selectedKotaId || !this.currentProvinsi) return null;
            return this.currentProvinsi.kotas.find(k => k.id == this.selectedKotaId) || null;
        },

        get currentKecamatan() {
            if (!this.selectedKecamatanId || !this.currentKota) return null;
            return this.currentKota.kecamatans.find(kc => kc.id == this.selectedKecamatanId) || null;
        },

        // Searchable Dropdowns State
        openProvinsiDropdown: false,
        searchProvinsi: '',
        openKotaDropdown: false,
        searchKota: '',
        openKecamatanDropdown: false,
        searchKecamatan: '',

        openDropdown(type) {
            this.openProvinsiDropdown = (type === 'provinsi') ? !this.openProvinsiDropdown : false;
            this.openKotaDropdown = (type === 'kota') ? !this.openKotaDropdown : false;
            this.openKecamatanDropdown = (type === 'kecamatan') ? !this.openKecamatanDropdown : false;

            if (this.openProvinsiDropdown) {
                this.searchProvinsi = '';
                this.$nextTick(() => this.$refs.searchProvinsiInput?.focus());
            }
            if (this.openKotaDropdown) {
                this.searchKota = '';
                this.$nextTick(() => this.$refs.searchKotaInput?.focus());
            }
            if (this.openKecamatanDropdown) {
                this.searchKecamatan = '';
                this.$nextTick(() => this.$refs.searchKecamatanInput?.focus());
            }
        },

        closeAllDropdowns() {
            this.openProvinsiDropdown = false;
            this.openKotaDropdown = false;
            this.openKecamatanDropdown = false;
        },

        // Navigation Drilldowns
        selectProvinsi(provName) {
            this.selectedProvinsiName = provName;
            this.selectedKotaId = null;
            this.selectedKecamatanId = null;
            this.currentLevel = 'provinsi';
            this.searchQuery = '';
            this.kotaPage = 1;
            this.kecamatanPage = 1;
            this.schoolPage = 1;
            this.closeAllDropdowns();
        },

        selectKota(kotaId) {
            this.selectedKotaId = kotaId;
            this.selectedKecamatanId = null;
            this.currentLevel = 'kota';
            this.searchQuery = '';
            this.kecamatanPage = 1;
            this.schoolPage = 1;
            this.closeAllDropdowns();
        },

        selectKecamatan(kecId) {
            this.selectedKecamatanId = kecId;
            this.currentLevel = 'kecamatan';
            this.searchQuery = '';
            this.schoolPage = 1;
            this.closeAllDropdowns();
        },

        resetToNational() {
            this.selectedProvinsiName = '{{ $initialProvinsi ?? "Jawa Barat" }}';
            if (this.userRole === 'Sales') {
                this.selectedKotaId = {{ $initialKotaId ? $initialKotaId : 'null' }};
                this.selectedKecamatanId = {{ $initialKecamatanId ? $initialKecamatanId : 'null' }};
                this.currentLevel = '{{ $initialLevel ?? "kecamatan" }}';
            } else if (this.userRole === 'SPV') {
                this.selectedKotaId = {{ $initialKotaId ? $initialKotaId : 'null' }};
                this.selectedKecamatanId = null;
                this.currentLevel = '{{ $initialLevel ?? "kota" }}';
            } else {
                this.selectedKotaId = null;
                this.selectedKecamatanId = null;
                this.currentLevel = 'provinsi';
            }
            this.searchQuery = '';
            this.kotaPage = 1;
            this.kecamatanPage = 1;
            this.schoolPage = 1;
            this.closeAllDropdowns();
        },

        openSchoolDetail(school) {
            this.selectedSchool = school;
            this.schoolDetailModal = true;
        },

        // Searchable Filter Getters for Dropdowns
        get searchableProvinces() {
            if (!this.searchProvinsi.trim()) return this.provinces;
            const q = this.searchProvinsi.toLowerCase().trim();
            return this.provinces.filter(p => p.nama.toLowerCase().includes(q) || (p.kode && p.kode.toLowerCase().includes(q)));
        },

        get searchableKotas() {
            if (!this.currentProvinsi) return [];
            const list = this.filteredKotas;
            if (!this.searchKota.trim()) return list;
            const q = this.searchKota.toLowerCase().trim();
            return list.filter(k => k.nama.toLowerCase().includes(q) || k.kode.toLowerCase().includes(q) || (k.hm_name && k.hm_name.toLowerCase().includes(q)));
        },

        get searchableKecamatans() {
            if (!this.currentKota) return [];
            const list = this.filteredKecamatans;
            if (!this.searchKecamatan.trim()) return list;
            const q = this.searchKecamatan.toLowerCase().trim();
            return list.filter(kc => {
                const matchSales = (kc.assigned_sales || []).some(s => s.name.toLowerCase().includes(q));
                return kc.nama.toLowerCase().includes(q) || kc.kode.toLowerCase().includes(q) || matchSales;
            });
        },

        // Dynamic Filtering & Auto-Sorting (Closing Terbanyak & Prospek Terbanyak Paling Atas)
        get filteredKotas() {
            if (!this.currentProvinsi) return [];
            const q = this.searchQuery.toLowerCase().trim();
            let list = this.currentProvinsi.kotas.filter(k => {
                const matchQ = !q || k.nama.toLowerCase().includes(q) || k.kode.toLowerCase().includes(q) || (k.hm_name && k.hm_name.toLowerCase().includes(q));
                const matchPotensi = this.filterPotensi === 'all' || k.potensi_rating === this.filterPotensi;
                return matchQ && matchPotensi;
            });

            return list.sort((a, b) => {
                const closingA = Number(a.total_closing) || 0;
                const closingB = Number(b.total_closing) || 0;
                if (closingB !== closingA) {
                    return closingB - closingA;
                }
                const prospekA = Number(a.total_prospek) || 0;
                const prospekB = Number(b.total_prospek) || 0;
                if (prospekB !== prospekA) {
                    return prospekB - prospekA;
                }
                return a.nama.localeCompare(b.nama);
            });
        },

        get paginatedKotas() {
            const start = (this.kotaPage - 1) * this.perPage;
            return this.filteredKotas.slice(start, start + this.perPage);
        },

        get totalKotaPages() {
            return Math.ceil(this.filteredKotas.length / this.perPage) || 1;
        },

        get filteredKecamatans() {
            if (!this.currentKota) return [];
            const q = this.searchQuery.toLowerCase().trim();
            let list = this.currentKota.kecamatans.filter(kc => {
                const matchSales = (kc.assigned_sales || []).some(s => s.name.toLowerCase().includes(q));
                const matchQ = !q || kc.nama.toLowerCase().includes(q) || kc.kode.toLowerCase().includes(q) || matchSales;
                const matchPotensi = this.filterPotensi === 'all' || kc.potensi_rating === this.filterPotensi;
                return matchQ && matchPotensi;
            });

            return list.sort((a, b) => {
                const closingA = Number(a.total_closing) || 0;
                const closingB = Number(b.total_closing) || 0;
                if (closingB !== closingA) {
                    return closingB - closingA;
                }
                const prospekA = Number(a.total_prospek) || 0;
                const prospekB = Number(b.total_prospek) || 0;
                if (prospekB !== prospekA) {
                    return prospekB - prospekA;
                }
                return a.nama.localeCompare(b.nama);
            });
        },

        get paginatedKecamatans() {
            const start = (this.kecamatanPage - 1) * this.perPage;
            return this.filteredKecamatans.slice(start, start + this.perPage);
        },

        get totalKecamatanPages() {
            return Math.ceil(this.filteredKecamatans.length / this.perPage) || 1;
        },

        get filteredSchools() {
            let list = [];
            if (this.currentLevel === 'kecamatan' && this.currentKecamatan) {
                list = [...this.currentKecamatan.sekolahs];
            } else if (this.currentLevel === 'kota' && this.currentKota) {
                this.currentKota.kecamatans.forEach(kc => {
                    list = list.concat(kc.sekolahs);
                });
            } else if (this.currentProvinsi) {
                this.currentProvinsi.kotas.forEach(kt => {
                    kt.kecamatans.forEach(kc => {
                        list = list.concat(kc.sekolahs);
                    });
                });
            }

            const q = this.searchQuery.toLowerCase().trim();
            let filtered = list.filter(s => {
                const matchQ = !q || s.nama.toLowerCase().includes(q) || s.kode.toLowerCase().includes(q) || (s.alamat && s.alamat.toLowerCase().includes(q)) || (s.pic_name && s.pic_name.toLowerCase().includes(q));
                const matchPotensi = this.filterPotensi === 'all' || s.potensi_rating === this.filterPotensi;
                return matchQ && matchPotensi;
            });

            return filtered.sort((a, b) => {
                const closingA = Number(a.total_closing) || 0;
                const closingB = Number(b.total_closing) || 0;
                if (closingB !== closingA) {
                    return closingB - closingA;
                }
                const prospekA = Number(a.total_prospek) || 0;
                const prospekB = Number(b.total_prospek) || 0;
                if (prospekB !== prospekA) {
                    return prospekB - prospekA;
                }
                return a.nama.localeCompare(b.nama);
            });
        },

        get paginatedSchools() {
            const start = (this.schoolPage - 1) * this.perPage;
            return this.filteredSchools.slice(start, start + this.perPage);
        },

        get totalSchoolPages() {
            return Math.ceil(this.filteredSchools.length / this.perPage) || 1;
        },

        // Contextual Stats
        get activeStats() {
            if (this.currentLevel === 'kecamatan' && this.currentKecamatan) {
                return {
                    title: 'Kecamatan ' + this.currentKecamatan.nama,
                    subtitle: this.currentKota?.nama + ', Provinsi ' + this.currentProvinsi?.nama,
                    total_prospek: this.currentKecamatan.total_prospek,
                    total_closing: this.currentKecamatan.total_closing,
                    total_sales: this.currentKecamatan.assigned_sales?.length || 0,
                    potensi_rating: this.currentKecamatan.potensi_rating,
                    potensi_badge: this.currentKecamatan.potensi_badge,
                };
            } else if (this.currentLevel === 'kota' && this.currentKota) {
                return {
                    title: this.currentKota.nama,
                    subtitle: 'Provinsi ' + this.currentProvinsi?.nama + ' • Head Marketing (HM): ' + this.currentKota.hm_name,
                    total_prospek: this.currentKota.total_prospek,
                    total_closing: this.currentKota.total_closing,
                    total_sales: this.currentKota.total_sales || 0,
                    potensi_rating: this.currentKota.potensi_rating,
                    potensi_badge: this.currentKota.potensi_badge,
                };
            } else if (this.currentProvinsi) {
                return {
                    title: 'Provinsi ' + this.currentProvinsi.nama,
                    subtitle: 'Cakupan Teritori Marketing CRM UCIC',
                    total_prospek: this.currentProvinsi.total_prospek,
                    total_closing: this.currentProvinsi.total_closing,
                    total_sales: this.currentProvinsi.total_sales || 0,
                    potensi_rating: 'Wilayah Utama',
                    potensi_badge: 'bg-purple-50 text-purple-700 border-purple-200',
                };
            }
            return {};
        }
    }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Potensi Wilayah</span>
                    </span>
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">Ciayumajakuning & Jawa Tengah</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight mt-2">Potensi Wilayah & Penugasan Marketing</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Eksplorasi wilayah: <strong>Kota / Kabupaten &rsaquo; Kecamatan &rsaquo; Sales Ditugaskan, Prospek, & Closing</strong>.</p>
            </div>

            <!-- View Switcher Tabs -->
            <div class="flex items-center bg-slate-100/80 p-1 rounded-xl text-xs font-semibold shrink-0">
                <button type="button" @click="activeTab = 'peta'" :class="activeTab === 'peta' ? 'bg-white text-purple-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-3.5 py-2 rounded-lg transition flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    <span>Peta Wilayah</span>
                </button>
                <button type="button" @click="activeTab = 'ranking'" :class="activeTab === 'ranking' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-3.5 py-2 rounded-lg transition flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>Ranking Wilayah</span>
                </button>
            </div>
        </div>

        <!-- TAB 1: PETA & HIRARKI WILAYAH -->
        <div x-show="activeTab === 'peta'" class="space-y-6">

            <!-- Breadcrumb Navigation & Quick Cascading Filter -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                
                <!-- Breadcrumbs -->
                <div class="flex flex-wrap items-center gap-2 text-xs sm:text-sm">
                    <button type="button" @click="resetToNational()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold transition hover:bg-slate-100 cursor-pointer" :class="currentLevel === 'provinsi' && !selectedKotaId ? 'text-purple-700 bg-purple-50 border border-purple-200' : 'text-slate-600'">
                        <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/></svg>
                        <span x-text="selectedProvinsiName"></span>
                    </button>

                    <template x-if="selectedKotaId">
                        <div class="flex items-center gap-2">
                            <span class="text-slate-300 font-bold">&rsaquo;</span>
                            <button type="button" @click="selectKota(selectedKotaId)" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-bold transition hover:bg-slate-100 cursor-pointer" :class="currentLevel === 'kota' ? 'text-blue-700 bg-blue-50 border border-blue-200' : 'text-slate-600'">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                                <span x-text="currentKota?.nama"></span>
                            </button>
                        </div>
                    </template>

                    <template x-if="selectedKecamatanId">
                        <div class="flex items-center gap-2">
                            <span class="text-slate-300 font-bold">&rsaquo;</span>
                            <button type="button" @click="selectKecamatan(selectedKecamatanId)" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-bold transition bg-emerald-50 text-emerald-700 border border-emerald-200 cursor-pointer">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                <span>Kec. <span x-text="currentKecamatan?.nama"></span></span>
                            </button>
                        </div>
                    </template>

                    <template x-if="currentLevel === 'kecamatan'">
                        <div class="flex items-center gap-2 text-slate-400 text-xs">
                            <span class="text-slate-300 font-bold">&rsaquo;</span>
                            <span class="font-semibold text-slate-500">Daftar Sekolah</span>
                        </div>
                    </template>
                </div>

                <!-- Cascading Selectors with Search Plugin & Instant Search -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2 border-t border-slate-100 text-xs" @click.away="closeAllDropdowns()">
                    
                    <!-- 1. Searchable Select: Provinsi -->
                    <div class="relative">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">1. Provinsi</label>
                        <button 
                            type="button" 
                            @click="openDropdown('provinsi')" 
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl border transition font-semibold text-xs bg-white text-slate-800 hover:bg-slate-50 focus:outline-none cursor-pointer shadow-2xs"
                            :class="openProvinsiDropdown ? 'border-purple-500 ring-2 ring-purple-100' : 'border-slate-200'"
                        >
                            <span class="truncate flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                <span x-text="selectedProvinsiName || 'Pilih Provinsi'"></span>
                            </span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="openProvinsiDropdown ? 'rotate-180 text-purple-600' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown Panel -->
                        <div 
                            x-show="openProvinsiDropdown" 
                            x-cloak 
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute left-0 right-0 z-50 mt-1.5 bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden text-xs"
                        >
                            <!-- Search Input -->
                            <div class="p-2 border-b border-slate-100 bg-slate-50/80">
                                <div class="relative flex items-center">
                                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input 
                                        x-ref="searchProvinsiInput"
                                        type="text" 
                                        x-model="searchProvinsi" 
                                        placeholder="Cari provinsi..." 
                                        class="w-full pl-8 pr-3 py-1.5 rounded-lg text-xs bg-white border border-slate-200 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-200 focus:border-purple-500"
                                        @keydown.escape="openProvinsiDropdown = false"
                                    >
                                </div>
                            </div>
                            <!-- Options List -->
                            <div class="max-h-52 overflow-y-auto divide-y divide-slate-50 p-1">
                                <template x-for="p in searchableProvinces" :key="p.nama">
                                    <div 
                                        @click="selectProvinsi(p.nama)"
                                        class="px-3 py-2 rounded-lg cursor-pointer flex items-center justify-between transition hover:bg-purple-50/80 group"
                                        :class="selectedProvinsiName === p.nama ? 'bg-purple-50 font-bold text-purple-800' : 'text-slate-700'"
                                    >
                                        <div class="min-w-0 pr-2">
                                            <div class="truncate font-semibold group-hover:text-purple-700" x-text="p.nama"></div>
                                            <div class="text-[10px] text-slate-400" x-text="(p.kotas?.length || 0) + ' Kota/Kab • ' + (p.total_closing || 0) + ' Closing • ' + (p.total_prospek || 0) + ' Prospek'"></div>
                                        </div>
                                        <template x-if="selectedProvinsiName === p.nama">
                                            <svg class="w-4 h-4 text-purple-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="searchableProvinces.length === 0">
                                    <div class="p-3 text-center text-slate-400 text-[11px]">Provinsi tidak ditemukan</div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Searchable Select: Kota / Kabupaten -->
                    <div class="relative">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">2. Kota / Kabupaten</label>
                        <button 
                            type="button" 
                            @click="openDropdown('kota')" 
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl border transition font-semibold text-xs bg-white text-slate-800 hover:bg-slate-50 focus:outline-none cursor-pointer shadow-2xs"
                            :class="openKotaDropdown ? 'border-blue-500 ring-2 ring-blue-100' : 'border-slate-200'"
                        >
                            <span class="truncate flex items-center gap-1.5" :class="selectedKotaId ? 'text-blue-800 font-bold' : 'text-slate-600'">
                                <span class="w-2 h-2 rounded-full" :class="selectedKotaId ? 'bg-blue-600' : 'bg-slate-300'"></span>
                                <span x-text="currentKota ? currentKota.nama : '-- Semua Kota / Kab --'"></span>
                            </span>
                            <div class="flex items-center gap-1 shrink-0">
                                <template x-if="selectedKotaId">
                                    <span @click.stop="resetToNational()" class="p-0.5 rounded-full hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition" title="Reset Kota">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </span>
                                </template>
                                <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="openKotaDropdown ? 'rotate-180 text-blue-600' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>

                        <!-- Dropdown Panel -->
                        <div 
                            x-show="openKotaDropdown" 
                            x-cloak 
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute left-0 right-0 z-50 mt-1.5 bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden text-xs"
                        >
                            <!-- Search Input -->
                            <div class="p-2 border-b border-slate-100 bg-slate-50/80">
                                <div class="relative flex items-center">
                                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input 
                                        x-ref="searchKotaInput"
                                        type="text" 
                                        x-model="searchKota" 
                                        placeholder="Cari Kota / Kabupaten..." 
                                        class="w-full pl-8 pr-3 py-1.5 rounded-lg text-xs bg-white border border-slate-200 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                                        @keydown.escape="openKotaDropdown = false"
                                    >
                                </div>
                            </div>
                            <!-- Options List -->
                            <div class="max-h-56 overflow-y-auto divide-y divide-slate-50 p-1">
                                <!-- Reset option -->
                                <div 
                                    @click="resetToNational()"
                                    class="px-3 py-2 rounded-lg cursor-pointer flex items-center justify-between transition hover:bg-slate-100"
                                    :class="!selectedKotaId ? 'bg-slate-100 font-bold text-slate-900' : 'text-slate-600'"
                                >
                                    <span class="font-medium italic">-- Semua Kota / Kabupaten --</span>
                                    <template x-if="!selectedKotaId">
                                        <svg class="w-4 h-4 text-slate-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    </template>
                                </div>
                                <!-- Kotas -->
                                <template x-for="k in searchableKotas" :key="k.id">
                                    <div 
                                        @click="selectKota(k.id)"
                                        class="px-3 py-2 rounded-lg cursor-pointer flex items-center justify-between transition hover:bg-blue-50/80 group"
                                        :class="selectedKotaId === k.id ? 'bg-blue-50 font-bold text-blue-800' : 'text-slate-700'"
                                    >
                                        <div class="min-w-0 pr-2">
                                            <div class="truncate font-semibold flex items-center gap-1.5 group-hover:text-blue-700">
                                                <span x-text="k.nama"></span>
                                                <span class="text-[10px] text-slate-400 font-mono" x-text="k.kode"></span>
                                            </div>
                                            <div class="text-[10px] text-slate-500 flex items-center gap-2 mt-0.5">
                                                <span class="text-emerald-700 font-bold" x-text="k.total_closing + ' Closing'"></span>
                                                <span>•</span>
                                                <span class="text-blue-700 font-bold" x-text="k.total_prospek + ' Prospek'"></span>
                                                <template x-if="k.hm_name && k.hm_name !== '-'">
                                                    <span class="text-slate-400 truncate" x-text="'• HM: ' + k.hm_name"></span>
                                                </template>
                                            </div>
                                        </div>
                                        <template x-if="selectedKotaId === k.id">
                                            <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="searchableKotas.length === 0">
                                    <div class="p-3 text-center text-slate-400 text-[11px]">Kota / Kabupaten tidak ditemukan</div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Searchable Select: Kecamatan -->
                    <div class="relative">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">3. Kecamatan</label>
                        <button 
                            type="button" 
                            @click="if(selectedKotaId) { openDropdown('kecamatan'); }" 
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl border transition font-semibold text-xs text-slate-800 focus:outline-none shadow-2xs"
                            :class="!selectedKotaId 
                                ? 'bg-slate-50 border-slate-200 text-slate-400 cursor-not-allowed' 
                                : (openKecamatanDropdown ? 'border-emerald-500 ring-2 ring-emerald-100 bg-white cursor-pointer' : 'border-slate-200 bg-white hover:bg-slate-50 cursor-pointer')"
                        >
                            <span class="truncate flex items-center gap-1.5" :class="selectedKecamatanId ? 'text-emerald-800 font-bold' : (selectedKotaId ? 'text-slate-600' : 'text-slate-400')">
                                <span class="w-2 h-2 rounded-full" :class="selectedKecamatanId ? 'bg-emerald-600' : (selectedKotaId ? 'bg-slate-300' : 'bg-slate-200')"></span>
                                <span x-text="currentKecamatan ? 'Kec. ' + currentKecamatan.nama : (selectedKotaId ? '-- Semua Kecamatan --' : 'Pilih Kota Dahulu')"></span>
                            </span>
                            <div class="flex items-center gap-1 shrink-0">
                                <template x-if="selectedKecamatanId">
                                    <span @click.stop="selectKota(selectedKotaId)" class="p-0.5 rounded-full hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition" title="Reset Kecamatan">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </span>
                                </template>
                                <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="openKecamatanDropdown ? 'rotate-180 text-emerald-600' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>

                        <!-- Dropdown Panel -->
                        <div 
                            x-show="openKecamatanDropdown && selectedKotaId" 
                            x-cloak 
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute left-0 right-0 z-50 mt-1.5 bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden text-xs"
                        >
                            <!-- Search Input -->
                            <div class="p-2 border-b border-slate-100 bg-slate-50/80">
                                <div class="relative flex items-center">
                                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input 
                                        x-ref="searchKecamatanInput"
                                        type="text" 
                                        x-model="searchKecamatan" 
                                        placeholder="Cari Kecamatan / Sales..." 
                                        class="w-full pl-8 pr-3 py-1.5 rounded-lg text-xs bg-white border border-slate-200 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500"
                                        @keydown.escape="openKecamatanDropdown = false"
                                    >
                                </div>
                            </div>
                            <!-- Options List -->
                            <div class="max-h-56 overflow-y-auto divide-y divide-slate-50 p-1">
                                <!-- Reset option -->
                                <div 
                                    @click="selectKota(selectedKotaId)"
                                    class="px-3 py-2 rounded-lg cursor-pointer flex items-center justify-between transition hover:bg-slate-100"
                                    :class="!selectedKecamatanId ? 'bg-slate-100 font-bold text-slate-900' : 'text-slate-600'"
                                >
                                    <span class="font-medium italic">-- Semua Kecamatan --</span>
                                    <template x-if="!selectedKecamatanId">
                                        <svg class="w-4 h-4 text-slate-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    </template>
                                </div>
                                <!-- Kecamatans -->
                                <template x-for="kc in searchableKecamatans" :key="kc.id">
                                    <div 
                                        @click="selectKecamatan(kc.id)"
                                        class="px-3 py-2 rounded-lg cursor-pointer flex items-center justify-between transition hover:bg-emerald-50/80 group"
                                        :class="selectedKecamatanId === kc.id ? 'bg-emerald-50 font-bold text-emerald-800' : 'text-slate-700'"
                                    >
                                        <div class="min-w-0 pr-2">
                                            <div class="truncate font-semibold flex items-center gap-1.5 group-hover:text-emerald-700">
                                                <span>Kec. </span><span x-text="kc.nama"></span>
                                                <span class="text-[10px] text-slate-400 font-mono" x-text="kc.kode"></span>
                                            </div>
                                            <div class="text-[10px] text-slate-500 flex flex-wrap items-center gap-1.5 mt-0.5">
                                                <span class="text-emerald-700 font-bold" x-text="kc.total_closing + ' Closing'"></span>
                                                <span>•</span>
                                                <span class="text-blue-700 font-bold" x-text="kc.total_prospek + ' Prospek'"></span>
                                                <template x-if="kc.assigned_sales && kc.assigned_sales.length > 0">
                                                    <span class="text-purple-700 font-semibold" x-text="'• Sales: ' + kc.assigned_sales.map(s => s.name).join(', ')"></span>
                                                </template>
                                            </div>
                                        </div>
                                        <template x-if="selectedKecamatanId === kc.id">
                                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="searchableKecamatans.length === 0">
                                    <div class="p-3 text-center text-slate-400 text-[11px]">Kecamatan tidak ditemukan</div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Cari Instan Cepat -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Pencarian Tabel</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" x-model="searchQuery" placeholder="Cari nama, sales, sekolah..." class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 outline-none font-medium text-xs bg-white shadow-2xs">
                        </div>
                    </div>
                </div>
            </div>

            <!-- STREAMLINED SUMMARY CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4">
                
                <!-- Card 1: Wilayah Terpilih -->
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Wilayah Aktif</span>
                        <span class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-xs">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                        </span>
                    </div>
                    <div class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-2 truncate" x-text="activeStats.title"></div>
                    <p class="text-xs text-slate-500 mt-1 truncate" x-text="activeStats.subtitle"></p>
                </div>

                <!-- Card 2: Jumlah Prospek -->
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Jumlah Prospek</span>
                        <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-blue-700 mt-2" x-text="activeStats.total_prospek || 0"></div>
                    <p class="text-xs text-slate-500 mt-1">Calon Mahasiswa Terdata</p>
                </div>

                <!-- Card 3: Total Closing -->
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Closing</span>
                        <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-emerald-600 mt-2" x-text="activeStats.total_closing || 0"></div>
                    <p class="text-xs text-slate-500 mt-1">Mahasiswa Resmi Lunas</p>
                </div>
            </div>

            <!-- LEVEL 1: DAFTAR KOTA / KABUPATEN (Auto-sorted by Closing & Prospek Terbanyak) -->
            <div x-show="currentLevel === 'provinsi'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-purple-50/40">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <span>Daftar Kota & Kabupaten di <strong class="text-purple-800" x-text="selectedProvinsiName"></strong></span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800" x-text="filteredKotas.length + ' Kota/Kab'"></span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Urutan otomatis berdasarkan perolehan Closing & Prospek tertinggi.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <select x-model="filterPotensi" class="text-xs px-3 py-1.5 rounded-xl border border-slate-200 bg-white font-semibold text-slate-700 outline-none">
                            <option value="all">Semua Status Potensi</option>
                            <option value="Sangat Tinggi">Sangat Tinggi</option>
                            <option value="Tinggi">Tinggi</option>
                            <option value="Sedang">Sedang</option>
                            <option value="Potensial">Potensial</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4">Kota / Kabupaten</th>
                                <th class="py-3 px-4">Head Marketing (HM)</th>
                                <th class="py-3 px-4 text-center">Jumlah Prospek</th>
                                <th class="py-3 px-4 text-center">Total Closing</th>
                                <th class="py-3 px-4 text-center">Status Potensi</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(k, idx) in paginatedKotas" :key="k.id">
                                <tr @click="selectKota(k.id)" class="hover:bg-purple-50/60 transition cursor-pointer group">
                                    <td class="py-3.5 px-4 text-center font-bold text-slate-400 group-hover:text-purple-600" x-text="(kotaPage - 1) * perPage + idx + 1"></td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900 group-hover:text-purple-700 text-xs sm:text-sm flex items-center gap-1.5">
                                            <span x-text="k.nama"></span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono" x-text="'Kode: ' + k.kode"></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-semibold text-slate-800" x-text="k.hm_name"></div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="font-extrabold text-blue-700 text-sm" x-text="k.total_prospek"></span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="font-extrabold text-emerald-600 text-sm" x-text="k.total_closing"></span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border" :class="k.potensi_badge" x-text="k.potensi_rating"></span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button type="button" @click.stop="selectKota(k.id)" class="px-3.5 py-1.5 rounded-lg bg-purple-50 group-hover:bg-purple-600 text-purple-700 group-hover:text-white text-xs font-bold transition flex items-center gap-1 ml-auto cursor-pointer">
                                            <span>Buka Kecamatan</span>
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <x-table-pagination total="filteredKotas.length" page="kotaPage" perPage="perPage" totalPages="totalKotaPages" color="purple" />
            </div>

            <!-- LEVEL 2: DAFTAR KECAMATAN DI KOTA INI (Auto-sorted by Closing & Prospek Terbanyak) -->
            <div x-show="currentLevel === 'kota'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-blue-50/40">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                                <span>Daftar Kecamatan di <strong class="text-blue-800" x-text="currentKota?.nama"></strong></span>
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800" x-text="filteredKecamatans.length + ' Kecamatan'"></span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Daftar nama kecamatan beserta personil Sales yang ditugaskan, jumlah prospek, dan total closing (diurutkan dari closing & prospek tertinggi).</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="resetToNational()" class="px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs font-bold transition flex items-center gap-1 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            <span>Kembali ke Semua Kota</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4">Nama Kecamatan</th>
                                <th class="py-3 px-4">Sales Ditugaskan</th>
                                <th class="py-3 px-4 text-center">Jumlah Prospek</th>
                                <th class="py-3 px-4 text-center">Total Closing</th>
                                <th class="py-3 px-4 text-center">Status Potensi</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(kc, idx) in paginatedKecamatans" :key="kc.id">
                                <tr @click="selectKecamatan(kc.id)" class="hover:bg-blue-50/60 transition cursor-pointer group">
                                    <td class="py-3.5 px-4 text-center font-bold text-slate-400 group-hover:text-blue-600" x-text="(kecamatanPage - 1) * perPage + idx + 1"></td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900 group-hover:text-blue-800 text-xs sm:text-sm flex items-center gap-1.5">
                                            <span>Kec. </span><span x-text="kc.nama"></span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono" x-text="'Kode: ' + kc.kode"></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex flex-wrap gap-1">
                                            <template x-if="kc.assigned_sales && kc.assigned_sales.length > 0">
                                                <template x-for="s in kc.assigned_sales" :key="s.id">
                                                    <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 text-[10px] font-bold border border-blue-100 flex items-center gap-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                        <span x-text="s.name"></span>
                                                    </span>
                                                </template>
                                            </template>
                                            <template x-if="!kc.assigned_sales || kc.assigned_sales.length === 0">
                                                <span class="text-[11px] text-slate-400 italic">Belum teralokasi</span>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="font-extrabold text-blue-700 text-sm" x-text="kc.total_prospek"></span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="font-extrabold text-emerald-600 text-sm" x-text="kc.total_closing"></span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border" :class="kc.potensi_badge" x-text="kc.potensi_rating"></span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button type="button" @click.stop="selectKecamatan(kc.id)" class="px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white text-xs font-bold transition flex items-center gap-1 ml-auto cursor-pointer" title="Buka Daftar Sekolah">
                                            <span>Sekolah</span>
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <x-table-pagination total="filteredKecamatans.length" page="kecamatanPage" perPage="perPage" totalPages="totalKecamatanPages" color="blue" />
            </div>

            <!-- LEVEL 3: DAFTAR SEKOLAH DI KECAMATAN INI (Auto-sorted by Closing & Prospek Terbanyak) -->
            <div x-show="currentLevel === 'kecamatan'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-emerald-50/40">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                                <span>Daftar Sekolah di Kec. <strong class="text-emerald-800" x-text="currentKecamatan?.nama"></strong> (<span x-text="currentKota?.nama"></span>)</span>
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800" x-text="filteredSchools.length + ' Sekolah'"></span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Daftar sekolah dan perolehan prospek & closing (diurutkan dari closing & prospek tertinggi).</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="selectKota(selectedKotaId)" class="px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs font-bold transition flex items-center gap-1 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            <span>Kembali ke Kecamatan</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4">Nama Sekolah</th>
                                <th class="py-3 px-4">Nama</th>
                                <th class="py-3 px-4 text-center">Jumlah Prospek</th>
                                <th class="py-3 px-4 text-center">Total Closing</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(s, idx) in paginatedSchools" :key="s.id">
                                <tr @click="openSchoolDetail(s)" class="hover:bg-emerald-50/50 transition cursor-pointer group">
                                    <td class="py-3.5 px-4 text-center font-bold text-slate-400 group-hover:text-emerald-600" x-text="(schoolPage - 1) * perPage + idx + 1"></td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900 group-hover:text-emerald-700 text-xs sm:text-sm" x-text="s.nama"></div>
                                        <div class="text-[10px] text-slate-400" x-text="s.alamat"></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-800" x-text="s.pic_name"></div>
                                        <div class="text-[10px] text-slate-500" x-text="s.pic_phone"></div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-bold text-blue-700 text-sm" x-text="s.total_prospek"></td>
                                    <td class="py-3.5 px-4 text-center font-extrabold text-emerald-600 text-sm" x-text="s.total_closing"></td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button type="button" @click.stop="openSchoolDetail(s)" class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white font-bold text-xs transition cursor-pointer">
                                            Profil
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="filteredSchools.length === 0">
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400">Tidak ada sekolah yang sesuai pencarian.</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <x-table-pagination total="filteredSchools.length" page="schoolPage" perPage="perPage" totalPages="totalSchoolPages" color="emerald" />
            </div>

        </div>

        <!-- TAB 2: RANKING WILAYAH (Auto-sorted by Closing & Prospek Terbanyak) -->
        <div x-show="activeTab === 'ranking'" class="space-y-6">
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>Peringkat Wilayah Kota & Kabupaten</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800">Semua Kota & Kabupaten</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Perbandingan jumlah prospek dan total closing per kota/kabupaten.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                                <th class="py-3 px-3 w-10 text-center">Rank</th>
                                <th class="py-3 px-4">Kota / Kabupaten</th>
                                <th class="py-3 px-4">Provinsi</th>
                                <th class="py-3 px-4">Head Marketing (HM)</th>
                                <th class="py-3 px-4 text-center">Jumlah Prospek</th>
                                <th class="py-3 px-4 text-center">Total Closing</th>
                                <th class="py-3 px-4 text-center">Status Potensi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @php $rank = 1; @endphp
                            @foreach($provinces as $prv)
                                @foreach($prv['kotas'] as $kt)
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-3.5 px-3 text-center font-extrabold text-purple-700">{{ $rank++ }}</td>
                                        <td class="py-3.5 px-4 font-bold text-slate-900">
                                            {{ $kt['nama'] }}
                                            <span class="text-slate-400 text-[10px] font-mono block">Kode: {{ $kt['kode'] }}</span>
                                        </td>
                                        <td class="py-3.5 px-4 text-slate-600 font-medium">{{ $kt['provinsi'] }}</td>
                                        <td class="py-3.5 px-4 font-semibold text-slate-800">{{ $kt['hm_name'] }}</td>
                                        <td class="py-3.5 px-4 text-center font-bold text-blue-700 text-sm">{{ $kt['total_prospek'] }}</td>
                                        <td class="py-3.5 px-4 text-center font-extrabold text-emerald-700 text-sm">{{ $kt['total_closing'] }}</td>
                                        <td class="py-3.5 px-4 text-center">
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $kt['potensi_badge'] }}">{{ $kt['potensi_rating'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL DETAIL SEKOLAH -->
        <div x-show="schoolDetailModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="schoolDetailModal = false"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-xl p-6 sm:p-7 z-10 border border-slate-100 animate-scale-up space-y-5" x-if="selectedSchool">
                    <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900" x-text="selectedSchool?.nama"></h3>
                            <p class="text-xs text-slate-500 mt-0.5" x-text="'Kecamatan ' + selectedSchool?.kecamatan + ', ' + selectedSchool?.kota_nama"></p>
                        </div>
                        <button type="button" @click="schoolDetailModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div class="p-3.5 rounded-xl bg-blue-50 border border-blue-100">
                            <span class="text-[10px] text-blue-600 block font-semibold uppercase tracking-wider">Jumlah Prospek</span>
                            <span class="text-2xl font-black text-blue-800 mt-1 block" x-text="selectedSchool?.total_prospek"></span>
                        </div>
                        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-100">
                            <span class="text-[10px] text-emerald-600 block font-semibold uppercase tracking-wider">Total Closing (Lunas)</span>
                            <span class="text-2xl font-black text-emerald-800 mt-1 block" x-text="selectedSchool?.total_closing"></span>
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div class="space-y-2.5 text-xs">
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2">
                            <span class="font-bold text-slate-800 block text-[11px] uppercase tracking-wider">Informasi Kontak:</span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-600">
                                <div><strong>Nama:</strong> <span x-text="selectedSchool?.pic_name || '-'"></span></div>
                                <div><strong>No. WhatsApp:</strong> <span class="font-mono text-purple-700 font-bold" x-text="selectedSchool?.pic_phone || '-'"></span></div>
                                <div class="col-span-1 sm:col-span-2"><strong>Alamat:</strong> <span x-text="selectedSchool?.alamat || '-'"></span></div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                        <template x-if="selectedSchool?.pic_phone && selectedSchool?.pic_phone !== '-'">
                            <a :href="'https://wa.me/' + selectedSchool.pic_phone.replace(/[^0-9]/g, '')" target="_blank" class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                <span>Hubungi via WA</span>
                            </a>
                        </template>
                        <button type="button" @click="schoolDetailModal = false" class="px-5 py-2 text-xs font-bold rounded-xl bg-slate-900 hover:bg-slate-800 text-white shadow-xs transition cursor-pointer ml-auto">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
