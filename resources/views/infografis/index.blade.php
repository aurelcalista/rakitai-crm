@php $pageTitle = 'Infografis & Sebaran Wilayah'; @endphp

<x-app-layout :title="'Infografis & Sebaran Wilayah - CRM UCIC'">
    <div class="space-y-6" x-data="{
        provinces: {{ json_encode($provinces) }},
        currentLevel: 'provinsi', // 'provinsi', 'kota', 'kecamatan', 'sekolah'
        selectedProvinsiName: 'Jawa Barat',
        selectedKotaId: null,
        selectedKecamatanId: null,
        selectedSchool: null,
        schoolDetailModal: false,
        searchQuery: '',
        filterBentuk: 'all', // 'all', 'SMA', 'SMK', 'MA'
        filterStatus: 'all', // 'all', 'Negeri', 'Swasta'
        filterTier: 'all',   // 'all', 'A', 'B', 'C'
        activeTab: 'dapodik', // 'dapodik', 'indikator', 'summary'
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

        // Drilldown Actions
        selectProvinsi(provName) {
            this.selectedProvinsiName = provName;
            this.selectedKotaId = null;
            this.selectedKecamatanId = null;
            this.currentLevel = 'provinsi';
            this.searchQuery = '';
            this.kotaPage = 1;
            this.kecamatanPage = 1;
            this.schoolPage = 1;
        },

        selectKota(kotaId) {
            this.selectedKotaId = kotaId;
            this.selectedKecamatanId = null;
            this.currentLevel = 'kota';
            this.searchQuery = '';
            this.kecamatanPage = 1;
            this.schoolPage = 1;
        },

        selectKecamatan(kecId) {
            this.selectedKecamatanId = kecId;
            this.currentLevel = 'kecamatan';
            this.searchQuery = '';
            this.schoolPage = 1;
        },

        resetToNational() {
            this.selectedProvinsiName = 'Jawa Barat';
            this.selectedKotaId = null;
            this.selectedKecamatanId = null;
            this.currentLevel = 'provinsi';
            this.searchQuery = '';
            this.kotaPage = 1;
            this.kecamatanPage = 1;
            this.schoolPage = 1;
        },

        openSchoolDetail(school) {
            this.selectedSchool = school;
            this.schoolDetailModal = true;
        },

        // Dynamic Filtering
        get filteredKotas() {
            if (!this.currentProvinsi) return [];
            const q = this.searchQuery.toLowerCase().trim();
            return this.currentProvinsi.kotas.filter(k => {
                return !q || k.nama.toLowerCase().includes(q) || k.kode.toLowerCase().includes(q);
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
            return this.currentKota.kecamatans.filter(kc => {
                return !q || kc.nama.toLowerCase().includes(q) || kc.kode.toLowerCase().includes(q);
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
                list = this.currentKecamatan.sekolahs;
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
            return list.filter(s => {
                const matchQ = !q || s.nama.toLowerCase().includes(q) || s.kode.toLowerCase().includes(q) || (s.alamat && s.alamat.toLowerCase().includes(q)) || (s.pic_name && s.pic_name.toLowerCase().includes(q));
                const matchBentuk = this.filterBentuk === 'all' || s.bentuk === this.filterBentuk;
                const matchStatus = this.filterStatus === 'all' || s.status_sekolah === this.filterStatus;
                const matchTier = this.filterTier === 'all' || s.tier === this.filterTier;
                return matchQ && matchBentuk && matchStatus && matchTier;
            });
        },

        get paginatedSchools() {
            const start = (this.schoolPage - 1) * this.perPage;
            return this.filteredSchools.slice(start, start + this.perPage);
        },

        get totalSchoolPages() {
            return Math.ceil(this.filteredSchools.length / this.perPage) || 1;
        },

        // Dynamic KPI Stats depending on active level
        get currentStats() {
            if (this.currentLevel === 'kecamatan' && this.currentKecamatan) {
                return {
                    title: 'Kecamatan ' + this.currentKecamatan.nama,
                    subtitle: this.currentKota?.nama + ', ' + this.currentProvinsi?.nama,
                    total_sekolah: this.currentKecamatan.total_sekolah,
                    total_sma: this.currentKecamatan.total_sma,
                    total_smk: this.currentKecamatan.total_smk,
                    total_ma: this.currentKecamatan.total_ma,
                    total_negeri: this.currentKecamatan.total_negeri,
                    total_swasta: this.currentKecamatan.total_swasta,
                    total_kunjungan: this.currentKecamatan.total_kunjungan,
                    total_prospek: this.currentKecamatan.total_prospek,
                    total_lunas: this.currentKecamatan.total_lunas,
                    total_berkas: this.currentKecamatan.total_berkas,
                    total_cancel: this.currentKecamatan.total_cancel,
                };
            } else if (this.currentLevel === 'kota' && this.currentKota) {
                return {
                    title: this.currentKota.nama,
                    subtitle: 'Provinsi ' + this.currentProvinsi?.nama,
                    total_sekolah: this.currentKota.total_sekolah,
                    total_sma: this.currentKota.total_sma,
                    total_smk: this.currentKota.total_smk,
                    total_ma: this.currentKota.total_ma,
                    total_negeri: this.currentKota.total_negeri,
                    total_swasta: this.currentKota.total_swasta,
                    total_kunjungan: this.currentKota.total_kunjungan,
                    total_prospek: this.currentKota.total_prospek,
                    total_lunas: this.currentKota.total_lunas,
                    total_berkas: this.currentKota.total_berkas,
                    total_cancel: this.currentKota.total_cancel,
                };
            } else if (this.currentProvinsi) {
                return {
                    title: 'Provinsi ' + this.currentProvinsi.nama,
                    subtitle: 'Cakupan Teritori Marketing CRM UCIC',
                    total_sekolah: this.currentProvinsi.total_sekolah,
                    total_sma: this.currentProvinsi.total_sma,
                    total_smk: this.currentProvinsi.total_smk,
                    total_ma: this.currentProvinsi.total_ma,
                    total_negeri: this.currentProvinsi.total_negeri,
                    total_swasta: this.currentProvinsi.total_swasta,
                    total_kunjungan: this.currentProvinsi.total_kunjungan,
                    total_prospek: this.currentProvinsi.total_prospek,
                    total_lunas: this.currentProvinsi.total_lunas,
                    total_berkas: this.currentProvinsi.total_berkas,
                    total_cancel: this.currentProvinsi.total_cancel,
                };
            }
            return {};
        }
    }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Data Pokok Pendidikan & Wilayah</span>
                    </span>
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200">Ciayumajakuning</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight mt-2">Infografis & Sebaran Sekolah</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Eksplorasi berjenjang data teritori: <strong>Provinsi &rsaquo; Kota / Kabupaten &rsaquo; Kecamatan &rsaquo; Sekolah</strong> (Referensi Data Pokok Pendidikan Dapodik Kemendikdasmen).</p>
            </div>

            <!-- View Switcher Tabs -->
            <div class="flex items-center bg-slate-100/80 p-1 rounded-xl text-xs font-semibold shrink-0">
                <button type="button" @click="activeTab = 'dapodik'" :class="activeTab === 'dapodik' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-3.5 py-2 rounded-lg transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Peta Wilayah & Sekolah</span>
                </button>
                <button type="button" @click="activeTab = 'indikator'" :class="activeTab === 'indikator' ? 'bg-white text-purple-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-3.5 py-2 rounded-lg transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>Skor & Bobot Wilayah</span>
                </button>
            </div>
        </div>

        <!-- MAIN SECTION: DAPODIK STYLE DRILLDOWN -->
        <div x-show="activeTab === 'dapodik'" class="space-y-6">

            <!-- Breadcrumb Navigation & Quick Cascading Filter -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                
                <!-- Breadcrumbs -->
                <div class="flex flex-wrap items-center gap-2 text-xs sm:text-sm">
                    <button type="button" @click="resetToNational()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold transition hover:bg-slate-100" :class="currentLevel === 'provinsi' && !selectedKotaId ? 'text-blue-700 bg-blue-50 border border-blue-200' : 'text-slate-600'">
                        <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/></svg>
                        <span x-text="selectedProvinsiName"></span>
                    </button>

                    <template x-if="selectedKotaId">
                        <div class="flex items-center gap-2">
                            <span class="text-slate-300 font-bold">&rsaquo;</span>
                            <button type="button" @click="selectKota(selectedKotaId)" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-bold transition hover:bg-slate-100" :class="currentLevel === 'kota' ? 'text-purple-700 bg-purple-50 border border-purple-200' : 'text-slate-600'">
                                <svg class="w-3.5 h-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                                <span x-text="currentKota?.nama"></span>
                            </button>
                        </div>
                    </template>

                    <template x-if="selectedKecamatanId">
                        <div class="flex items-center gap-2">
                            <span class="text-slate-300 font-bold">&rsaquo;</span>
                            <button type="button" @click="selectKecamatan(selectedKecamatanId)" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-bold transition bg-emerald-50 text-emerald-700 border border-emerald-200">
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

                <!-- Cascading Selectors & Instant Search -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2 border-t border-slate-100 text-xs">
                    
                    <!-- 1. Pilih Provinsi -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">1. Provinsi</label>
                        <select :value="selectedProvinsiName" @change="selectProvinsi($event.target.value)" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white font-medium text-slate-800 focus:ring-2 focus:ring-blue-200 outline-none">
                            <template x-for="p in provinces" :key="p.nama">
                                <option :value="p.nama" x-text="p.nama"></option>
                            </template>
                        </select>
                    </div>

                    <!-- 2. Pilih Kota / Kabupaten -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">2. Kota / Kabupaten</label>
                        <select :value="selectedKotaId || ''" @change="$event.target.value ? selectKota(Number($event.target.value)) : resetToNational()" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white font-medium text-slate-800 focus:ring-2 focus:ring-purple-200 outline-none">
                            <option value="">-- Semua Kota / Kabupaten --</option>
                            <template x-for="k in currentProvinsi?.kotas || []" :key="k.id">
                                <option :value="k.id" x-text="k.nama + ' (' + k.total_sekolah + ' Sekolah)'"></option>
                            </template>
                        </select>
                    </div>

                    <!-- 3. Pilih Kecamatan -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">3. Kecamatan</label>
                        <select :value="selectedKecamatanId || ''" :disabled="!selectedKotaId" @change="$event.target.value ? selectKecamatan(Number($event.target.value)) : selectKota(selectedKotaId)" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white font-medium text-slate-800 focus:ring-2 focus:ring-emerald-200 outline-none disabled:bg-slate-50 disabled:text-slate-400">
                            <option value="">-- Semua Kecamatan --</option>
                            <template x-for="kc in currentKota?.kecamatans || []" :key="kc.id">
                                <option :value="kc.id" x-text="kc.nama + ' (' + kc.total_sekolah + ' Sekolah)'"></option>
                            </template>
                        </select>
                    </div>

                    <!-- 4. Cari Instan -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Pencarian Cepat</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" x-model="searchQuery" placeholder="Cari nama sekolah, wilayah, PIC..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-200 outline-none font-medium">
                        </div>
                    </div>
                </div>
            </div>

            <!-- DYNAMIC KPI STAT SUMMARY CARDS -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
                
                <!-- Card 1: Total Sekolah & Jenjang -->
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Sekolah</span>
                        <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2" x-text="currentStats.total_sekolah || 0"></div>
                    <div class="flex flex-wrap items-center gap-1.5 mt-2.5">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100">SMA: <span x-text="currentStats.total_sma || 0"></span></span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-100">SMK: <span x-text="currentStats.total_smk || 0"></span></span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-100">MA: <span x-text="currentStats.total_ma || 0"></span></span>
                    </div>
                </div>

                <!-- Card 2: Negeri vs Swasta -->
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status Sekolah</span>
                        <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                        </span>
                    </div>
                    <div class="flex items-baseline gap-2 mt-2">
                        <span class="text-2xl sm:text-3xl font-extrabold text-indigo-900" x-text="currentStats.total_negeri || 0"></span>
                        <span class="text-xs text-slate-500 font-semibold">Negeri</span>
                        <span class="text-slate-300">/</span>
                        <span class="text-xl font-bold text-slate-700" x-text="currentStats.total_swasta || 0"></span>
                        <span class="text-xs text-slate-500 font-semibold">Swasta</span>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full mt-3 overflow-hidden flex">
                        <div class="bg-indigo-600 h-full" :style="'width: ' + ((currentStats.total_negeri / (currentStats.total_sekolah || 1)) * 100) + '%'"></div>
                        <div class="bg-amber-400 h-full" :style="'width: ' + ((currentStats.total_swasta / (currentStats.total_sekolah || 1)) * 100) + '%'"></div>
                    </div>
                </div>

                <!-- Card 3: Prospek & Kunjungan Sales -->
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Prospek & Kunjungan</span>
                        <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-emerald-700 mt-2" x-text="currentStats.total_prospek || 0"></div>
                    <div class="flex items-center gap-2 mt-2.5 text-xs text-slate-500 font-medium">
                        <span class="inline-flex items-center gap-1 font-bold text-slate-700">
                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            <span x-text="currentStats.total_kunjungan || 0"></span> Kunjungan
                        </span>
                        <span>&bull;</span>
                        <span class="text-purple-600 font-semibold"><span x-text="currentStats.total_berkas || 0"></span> Berkas</span>
                    </div>
                </div>

                <!-- Card 4: Mahasiswa Closing Lunas -->
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Closing (Lunas Pendaftaran)</span>
                        <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-emerald-600 mt-2" x-text="currentStats.total_lunas || 0"></div>
                    <div class="flex items-center gap-2 mt-2.5 text-[11px] text-slate-500">
                        <span class="text-rose-600 font-semibold">Cancel: <span x-text="currentStats.total_cancel || 0"></span></span>
                        <span>&bull;</span>
                        <span class="text-slate-400">Wilayah: <span class="font-bold text-slate-700" x-text="currentStats.title"></span></span>
                    </div>
                </div>
            </div>

            <!-- LEVEL 1: LIST OF KOTA / KABUPATEN (When viewing Provinsi level) -->
            <div x-show="currentLevel === 'provinsi'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <span>Daftar Kota & Kabupaten</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800" x-text="filteredKotas.length + ' Kota/Kab'"></span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Klik pada salah satu baris kota/kabupaten untuk melihat rincian kecamatan dan sebaran sekolah di dalamnya.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4">Kota / Kabupaten</th>
                                <th class="py-3 px-3 text-center">Jml Kecamatan</th>
                                <th class="py-3 px-3 text-center">Total Sekolah</th>
                                <th class="py-3 px-3 text-center">SMA / SMK / MA</th>
                                <th class="py-3 px-3 text-center">Negeri / Swasta</th>
                                <th class="py-3 px-3 text-center">Kunjungan</th>
                                <th class="py-3 px-3 text-center">Prospek</th>
                                <th class="py-3 px-3 text-center">Closing</th>
                                <th class="py-3 px-3 text-center">Skor & Grade</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(k, idx) in paginatedKotas" :key="k.id">
                                <tr @click="selectKota(k.id)" class="hover:bg-blue-50/60 transition cursor-pointer group">
                                    <td class="py-3.5 px-4 text-center font-bold text-slate-400 group-hover:text-blue-600" x-text="(kotaPage - 1) * perPage + idx + 1"></td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900 group-hover:text-blue-700 text-xs sm:text-sm flex items-center gap-1.5">
                                            <span x-text="k.nama"></span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono" x-text="'Kode: ' + k.kode"></div>
                                    </td>
                                    <td class="py-3.5 px-3 text-center font-bold text-slate-700" x-text="k.total_kecamatan"></td>
                                    <td class="py-3.5 px-3 text-center font-extrabold text-blue-700 text-sm" x-text="k.total_sekolah"></td>
                                    <td class="py-3.5 px-3 text-center">
                                        <span class="inline-flex items-center gap-1 font-mono text-[11px] text-slate-600">
                                            <span class="text-blue-700 font-bold" x-text="k.total_sma"></span> /
                                            <span class="text-teal-700 font-bold" x-text="k.total_smk"></span> /
                                            <span class="text-amber-700 font-bold" x-text="k.total_ma"></span>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <span class="inline-flex items-center gap-1 font-mono text-[11px]">
                                            <span class="text-indigo-700 font-bold" x-text="k.total_negeri"></span> N /
                                            <span class="text-slate-600 font-bold" x-text="k.total_swasta"></span> S
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center font-bold text-slate-700" x-text="k.total_kunjungan"></td>
                                    <td class="py-3.5 px-3 text-center font-bold text-emerald-700" x-text="k.total_prospek"></td>
                                    <td class="py-3.5 px-3 text-center font-extrabold text-emerald-600" x-text="k.total_lunas"></td>
                                    <td class="py-3.5 px-3 text-center">
                                        <template x-if="k.skor_wilayah !== null">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold" :class="k.grade_badge">
                                                <span x-text="k.grade !== 'N/A' ? 'Grade ' + k.grade : 'N/A'"></span>
                                                <span class="text-[9px] opacity-75" x-text="'(' + k.skor_wilayah + '%)'"></span>
                                            </span>
                                        </template>
                                        <template x-if="k.skor_wilayah === null">
                                            <span class="text-slate-400 text-[11px]">-</span>
                                        </template>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button type="button" @click.stop="selectKota(k.id)" class="px-3 py-1.5 rounded-lg bg-blue-50 group-hover:bg-blue-600 text-blue-700 group-hover:text-white text-xs font-bold transition flex items-center gap-1 ml-auto">
                                            <span>Lihat Kecamatan</span>
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <x-table-pagination total="filteredKotas.length" page="kotaPage" perPage="perPage" totalPages="totalKotaPages" color="blue" />
            </div>

            <!-- LEVEL 2: LIST OF KECAMATAN (When viewing Kota level) -->
            <div x-show="currentLevel === 'kota'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-purple-50/40">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                                <span>Daftar Kecamatan di <strong class="text-purple-800" x-text="currentKota?.nama"></strong></span>
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800" x-text="filteredKecamatans.length + ' Kecamatan'"></span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Pilih kecamatan untuk membuka direktori daftar sekolah, kontak PIC, dan riwayat kunjungan.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="resetToNational()" class="px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs font-bold transition flex items-center gap-1">
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
                                <th class="py-3 px-3 text-center">Total Sekolah</th>
                                <th class="py-3 px-3 text-center">SMA / SMK / MA</th>
                                <th class="py-3 px-3 text-center">Negeri / Swasta</th>
                                <th class="py-3 px-3 text-center">Kunjungan</th>
                                <th class="py-3 px-3 text-center">Prospek</th>
                                <th class="py-3 px-3 text-center">Closing</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(kc, idx) in paginatedKecamatans" :key="kc.id">
                                <tr @click="selectKecamatan(kc.id)" class="hover:bg-purple-50/60 transition cursor-pointer group">
                                    <td class="py-3.5 px-4 text-center font-bold text-slate-400 group-hover:text-purple-600" x-text="(kecamatanPage - 1) * perPage + idx + 1"></td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900 group-hover:text-purple-800 text-xs sm:text-sm flex items-center gap-1.5">
                                            <span>Kec. </span><span x-text="kc.nama"></span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono" x-text="'Kode: ' + kc.kode"></div>
                                    </td>
                                    <td class="py-3.5 px-3 text-center font-extrabold text-purple-700 text-sm" x-text="kc.total_sekolah"></td>
                                    <td class="py-3.5 px-3 text-center">
                                        <span class="inline-flex items-center gap-1 font-mono text-[11px] text-slate-600">
                                            <span class="text-blue-700 font-bold" x-text="kc.total_sma"></span> /
                                            <span class="text-teal-700 font-bold" x-text="kc.total_smk"></span> /
                                            <span class="text-amber-700 font-bold" x-text="kc.total_ma"></span>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <span class="inline-flex items-center gap-1 font-mono text-[11px]">
                                            <span class="text-indigo-700 font-bold" x-text="kc.total_negeri"></span> N /
                                            <span class="text-slate-600 font-bold" x-text="kc.total_swasta"></span> S
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center font-bold text-slate-700" x-text="kc.total_kunjungan"></td>
                                    <td class="py-3.5 px-3 text-center font-bold text-emerald-700" x-text="kc.total_prospek"></td>
                                    <td class="py-3.5 px-3 text-center font-extrabold text-emerald-600" x-text="kc.total_lunas"></td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button type="button" @click.stop="selectKecamatan(kc.id)" class="px-3 py-1.5 rounded-lg bg-purple-50 group-hover:bg-purple-600 text-purple-700 group-hover:text-white text-xs font-bold transition flex items-center gap-1 ml-auto">
                                            <span>Lihat Sekolah</span>
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <x-table-pagination total="filteredKecamatans.length" page="kecamatanPage" perPage="perPage" totalPages="totalKecamatanPages" color="purple" />
            </div>

            <!-- LEVEL 3: LIST OF SEKOLAH (When viewing Kecamatan level OR Filtered) -->
            <div x-show="currentLevel === 'kecamatan'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-emerald-50/40">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                                <span>Daftar Sekolah di Kec. <strong class="text-emerald-800" x-text="currentKecamatan?.nama"></strong> (<span x-text="currentKota?.nama"></span>)</span>
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800" x-text="filteredSchools.length + ' Sekolah'"></span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Daftar SMA, SMK, dan MA. Klik nama sekolah untuk melihat detail profil, kontak PIC, dan riwayat CRM.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Filters -->
                        <select x-model="filterBentuk" class="text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white font-semibold text-slate-700 outline-none">
                            <option value="all">Semua Jenjang</option>
                            <option value="SMA">SMA</option>
                            <option value="SMK">SMK</option>
                            <option value="MA">MA</option>
                        </select>
                        <select x-model="filterStatus" class="text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white font-semibold text-slate-700 outline-none">
                            <option value="all">Semua Status</option>
                            <option value="Negeri">Negeri</option>
                            <option value="Swasta">Swasta</option>
                        </select>
                        <select x-model="filterTier" class="text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white font-semibold text-slate-700 outline-none">
                            <option value="all">Semua Tier</option>
                            <option value="A">Tier A</option>
                            <option value="B">Tier B</option>
                            <option value="C">Tier C</option>
                        </select>
                        <button type="button" @click="selectKota(selectedKotaId)" class="px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs font-bold transition flex items-center gap-1">
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
                                <th class="py-3 px-4">Nama Sekolah & Kode</th>
                                <th class="py-3 px-3 text-center">Bentuk</th>
                                <th class="py-3 px-3 text-center">Status</th>
                                <th class="py-3 px-3 text-center">Tier</th>
                                <th class="py-3 px-4">Alamat & Kontak</th>
                                <th class="py-3 px-4">Nama</th>
                                <th class="py-3 px-3 text-center">Kunjungan</th>
                                <th class="py-3 px-3 text-center">Prospek</th>
                                <th class="py-3 px-3 text-center">Lunas</th>
                                <th class="py-3 px-4 text-right">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(s, idx) in paginatedSchools" :key="s.id">
                                <tr @click="openSchoolDetail(s)" class="hover:bg-emerald-50/50 transition cursor-pointer group">
                                    <td class="py-3.5 px-4 text-center font-bold text-slate-400 group-hover:text-emerald-600" x-text="(schoolPage - 1) * perPage + idx + 1"></td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900 group-hover:text-emerald-700 text-xs sm:text-sm" x-text="s.nama"></div>
                                        <div class="text-[10px] text-slate-400 font-mono" x-text="s.kode"></div>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold"
                                              :class="{
                                                  'bg-blue-50 text-blue-700 border border-blue-200': s.bentuk === 'SMA',
                                                  'bg-teal-50 text-teal-700 border border-teal-200': s.bentuk === 'SMK',
                                                  'bg-amber-50 text-amber-700 border border-amber-200': s.bentuk === 'MA'
                                              }"
                                              x-text="s.bentuk"></span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold"
                                              :class="s.status_sekolah === 'Negeri' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-700 border border-slate-200'"
                                              x-text="s.status_sekolah"></span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <span class="w-6 h-6 rounded-full inline-flex items-center justify-center text-xs font-extrabold"
                                              :class="{
                                                  'bg-amber-100 text-amber-800 border border-amber-300': s.tier === 'A',
                                                  'bg-blue-100 text-blue-800 border border-blue-300': s.tier === 'B',
                                                  'bg-slate-100 text-slate-700 border border-slate-300': s.tier === 'C'
                                              }"
                                              x-text="s.tier"></span>
                                    </td>
                                    <td class="py-3.5 px-4 max-w-[200px]">
                                        <div class="text-slate-700 truncate font-medium" x-text="s.alamat"></div>
                                        <div class="text-[11px] text-slate-400 font-mono" x-text="s.telepon"></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-800" x-text="s.pic_name"></div>
                                        <div class="text-[10px] text-slate-500" x-text="s.pic_jabatan"></div>
                                    </td>
                                    <td class="py-3.5 px-3 text-center font-bold text-slate-700" x-text="s.total_kunjungan"></td>
                                    <td class="py-3.5 px-3 text-center font-bold text-emerald-700" x-text="s.total_prospek"></td>
                                    <td class="py-3.5 px-3 text-center font-extrabold text-emerald-600" x-text="s.total_lunas"></td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button type="button" @click.stop="openSchoolDetail(s)" class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white font-bold text-xs transition">
                                            Buka Profil
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="filteredSchools.length === 0">
                                <tr>
                                    <td colspan="11" class="py-8 text-center text-slate-400">Tidak ada sekolah yang sesuai filter.</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <x-table-pagination total="filteredSchools.length" page="schoolPage" perPage="perPage" totalPages="totalSchoolPages" color="emerald" />
            </div>

        </div>

        <!-- TAB 2: SKOR & BOBOT INDIKATOR WILAYAH -->
        <div x-show="activeTab === 'indikator'" class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <span>Indikator & Skor Wilayah Teritori</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-100 text-purple-800">
                                Bobot: Kontak {{ $bobotWeights['bobot_kontak'] ?? 40 }}% | Closing {{ $bobotWeights['bobot_closing'] ?? 60 }}%
                            </span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Penilaian performa gabungan (Skor Volume Kontak 40% + Skor Closing 60%), Grade, dan Matriks Performa per Wilayah Teritori.</p>
                    </div>

                    @if(strtolower(auth()->user()->role) === 'admin')
                        <form action="{{ route('admin.master-data.update-weights') }}" method="POST" class="flex items-center gap-2 text-xs bg-slate-50 p-2 rounded-xl border border-slate-200">
                            @csrf
                            <span class="font-bold text-slate-700">Ubah Bobot:</span>
                            <input type="number" name="bobot_kontak" value="{{ $bobotWeights['bobot_kontak'] ?? 40 }}" min="0" max="100" class="w-14 h-7 text-xs border-slate-300 rounded px-1.5 font-bold text-slate-900" title="Bobot Kontak (%)">
                            <span class="text-slate-400">:</span>
                            <input type="number" name="bobot_closing" value="{{ $bobotWeights['bobot_closing'] ?? 60 }}" min="0" max="100" class="w-14 h-7 text-xs border-slate-300 rounded px-1.5 font-bold text-slate-900" title="Bobot Closing (%)">
                            <button type="submit" class="px-2.5 py-1 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg transition text-[11px]">Simpan</button>
                        </form>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                                <th class="py-3 px-3 w-10 text-center">No</th>
                                <th class="py-3 px-4">Wilayah (Kode)</th>
                                <th class="py-3 px-3 text-center">Skor Kontak (Vol / Target)</th>
                                <th class="py-3 px-3 text-center">Skor Closing (LUNAS / Target)</th>
                                <th class="py-3 px-3 text-center">Skor Wilayah</th>
                                <th class="py-3 px-3 text-center">Grade</th>
                                <th class="py-3 px-4">Matriks Performa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($wilayahIndicators ?? [] as $ind)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3.5 px-3 text-center font-bold text-slate-400">{{ $loop->iteration }}</td>
                                    <td class="py-3.5 px-4 font-bold text-slate-900">
                                        {{ $ind['nama_wilayah'] }}
                                        <span class="text-slate-400 text-[11px] font-normal">({{ $ind['kode_wilayah'] }})</span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <div class="font-bold text-slate-800">{{ $ind['skor_kontak'] !== null ? $ind['skor_kontak'] . '%' : 'N/A' }}</div>
                                        <div class="text-[10px] text-slate-400">({{ number_format($ind['realisasi_kontak']) }} / {{ number_format($ind['target_kontak']) }})</div>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <div class="font-bold text-emerald-700">{{ $ind['skor_closing'] !== null ? $ind['skor_closing'] . '%' : 'N/A' }}</div>
                                        <div class="text-[10px] text-slate-400">({{ number_format($ind['realisasi_lunas']) }} / {{ number_format($ind['target_lunas']) }})</div>
                                    </td>
                                    <td class="py-3.5 px-3 text-center font-extrabold text-purple-700 text-sm">
                                        {{ $ind['skor_wilayah'] !== null ? $ind['skor_wilayah'] . '%' : 'N/A' }}
                                    </td>
                                    <td class="py-3.5 px-3 text-center align-middle">
                                        <div class="flex flex-col items-center justify-center gap-1.5 mt-1">
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold border {{ $ind['grade']['badge'] }}">
                                                {{ $ind['grade']['code'] === 'N/A' ? 'N/A' : 'Grade ' . $ind['grade']['code'] }}
                                            </span>
                                            @if($ind['grade']['code'] !== 'N/A')
                                                <span class="text-[10px] text-slate-500 font-medium max-w-[100px] leading-tight break-words text-center">
                                                    {{ trim(str_replace($ind['grade']['code'] . ' — ', '', $ind['grade']['label'])) }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 max-w-[220px] align-middle">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="font-bold text-slate-800 text-xs leading-tight whitespace-normal">{{ $ind['matrix']['label'] }}</span>
                                            <span class="text-[10px] text-slate-500 leading-tight whitespace-normal">{{ $ind['matrix']['desc'] }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-400">Belum ada data indikator wilayah.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL DETAIL SEKOLAH (Dapodik Profile) -->
        <div x-show="schoolDetailModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="schoolDetailModal = false"></div>
                
                <div class="inline-block w-full max-w-2xl p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10">
                    <template x-if="selectedSchool">
                        <div class="space-y-5 text-xs">
                            <!-- Header Modal -->
                            <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                        <span class="px-2.5 py-0.5 rounded-md font-bold text-[11px]"
                                              :class="{
                                                  'bg-blue-50 text-blue-700 border border-blue-200': selectedSchool.bentuk === 'SMA',
                                                  'bg-teal-50 text-teal-700 border border-teal-200': selectedSchool.bentuk === 'SMK',
                                                  'bg-amber-50 text-amber-700 border border-amber-200': selectedSchool.bentuk === 'MA'
                                              }"
                                              x-text="selectedSchool.bentuk"></span>
                                        <span class="px-2.5 py-0.5 rounded-md font-bold text-[11px] bg-indigo-50 text-indigo-700 border border-indigo-200" x-text="selectedSchool.status_sekolah"></span>
                                        <span class="px-2.5 py-0.5 rounded-md font-extrabold text-[11px] bg-amber-100 text-amber-800 border border-amber-300" x-text="'Tier ' + selectedSchool.tier"></span>
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-900" x-text="selectedSchool.nama"></h3>
                                    <p class="text-xs text-slate-400 mt-0.5 font-mono" x-text="'Kode: ' + selectedSchool.kode + ' &bull; Kec. ' + selectedSchool.kecamatan + ', ' + selectedSchool.kota_nama"></p>
                                </div>
                                <button @click="schoolDetailModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <!-- Info Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 space-y-2">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Informasi Kontak & Lokasi</span>
                                    <div>
                                        <span class="text-slate-400 text-[10px] block">Alamat</span>
                                        <span class="font-semibold text-slate-800" x-text="selectedSchool.alamat"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 text-[10px] block">Telepon / Website</span>
                                        <span class="font-semibold text-slate-800" x-text="selectedSchool.telepon + ' / ' + selectedSchool.email"></span>
                                    </div>
                                </div>

                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 space-y-2">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">PIC / Hubin Sekolah</span>
                                    <div>
                                        <span class="text-slate-400 text-[10px] block">Nama PIC</span>
                                        <span class="font-bold text-slate-900 text-sm" x-text="selectedSchool.pic_name"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 text-[10px] block">Jabatan & Kontak</span>
                                        <span class="font-medium text-slate-700" x-text="selectedSchool.pic_jabatan + ' (' + selectedSchool.pic_phone + ')'"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- CRM Performance Summary -->
                            <div class="p-4 rounded-xl bg-gradient-to-tr from-slate-900 to-slate-800 text-white space-y-3">
                                <div class="flex items-center justify-between border-b border-slate-700 pb-2">
                                    <span class="font-bold text-xs">Statistik CRM & Pendaftaran Mahasiswa</span>
                                    <span class="text-[10px] text-slate-400">Universitas Catur Insan Cendekia</span>
                                </div>
                                <div class="grid grid-cols-4 gap-2 text-center">
                                    <div class="p-2 rounded-lg bg-slate-800/60">
                                        <span class="text-[10px] text-slate-400 block">Kunjungan</span>
                                        <span class="text-base font-extrabold text-blue-400" x-text="selectedSchool.total_kunjungan"></span>
                                    </div>
                                    <div class="p-2 rounded-lg bg-slate-800/60">
                                        <span class="text-[10px] text-slate-400 block">Prospek Siswa</span>
                                        <span class="text-base font-extrabold text-emerald-400" x-text="selectedSchool.total_prospek"></span>
                                    </div>
                                    <div class="p-2 rounded-lg bg-slate-800/60">
                                        <span class="text-[10px] text-slate-400 block">Pemberkasan</span>
                                        <span class="text-base font-extrabold text-purple-400" x-text="selectedSchool.total_berkas"></span>
                                    </div>
                                    <div class="p-2 rounded-lg bg-slate-800/60">
                                        <span class="text-[10px] text-slate-400 block">Closing Lunas</span>
                                        <span class="text-base font-extrabold text-amber-400" x-text="selectedSchool.total_lunas"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center justify-end gap-2 pt-2">
                                <template x-if="selectedSchool.pic_phone && selectedSchool.pic_phone !== '-'">
                                    <a :href="'https://wa.me/' + selectedSchool.pic_phone.replace(/[^0-9]/g, '')" target="_blank" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold flex items-center gap-1.5 transition">
                                        <span>Chat WhatsApp PIC</span>
                                    </a>
                                </template>
                                <button type="button" @click="schoolDetailModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold transition">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
