@php
    $pageTitle = 'Daftar Prospek Tim (SPV)';
    $pageSubtitle = 'Monitoring & Pengelolaan Database Prospek Tim Sales';
@endphp

<x-app-layout :title="'Prospek Tim - Supervisor CRM'">

    <div class="space-y-6" x-data="{
        searchQuery: '',
        selectedScope: '{{ request('scope', 'all') }}',
        selectedStatus: 'all',
        selectedType: 'all',
        selectedSales: 'all',
        currentPage: 1,
        perPage: 25,
        authUserId: {{ auth()->id() ?? 0 }},
        userRole: '{{ strtolower(auth()->user()->role ?? '') }}',
        prospectsList: {{ json_encode($prospects) }},
        modalReassign: false,
        reassignTarget: null,
        openReassign(p) { this.reassignTarget = p; this.modalReassign = true; },
        modalDeleteProspek: false,
        deleteProspectTarget: null,
        openDeleteProspectModal(p) { this.deleteProspectTarget = p; this.modalDeleteProspek = true; },

        isOwnProspect(p) {
            if (p.is_own === true) return true;
            if (!this.authUserId) return false;
            return Boolean(p.sales_id == this.authUserId || p.owner_id == this.authUserId || p.takeover_by_id == this.authUserId);
        },
        isOtherProspect(p) {
            if (this.isOwnProspect(p)) return false;
            return Boolean(p.is_other === true || p.sales_id || p.takeover_by_id || (p.owner_id && p.owner_id !== '-'));
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
                const matchSearch = !this.searchQuery ||
                    (p.name && p.name.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (p.pic && p.pic.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (p.whatsapp && p.whatsapp.toLowerCase().includes(this.searchQuery.toLowerCase()));

                const matchStatus = this.selectedStatus === 'all' || p.status === this.selectedStatus;
                const matchType   = this.selectedType   === 'all' || p.type   === this.selectedType;
                const matchSales  = this.selectedSales  === 'all' || String(p.sales_id) === String(this.selectedSales);

                let matchScope = true;
                if (this.selectedScope === 'own') {
                    matchScope = this.isOwnProspect(p);
                } else if (this.selectedScope === 'others') {
                    matchScope = this.isOtherProspect(p);
                } else if (this.selectedScope === 'presensi') {
                    matchScope = this.isPresensiSiswa(p);
                }

                return matchSearch && matchStatus && matchType && matchSales && matchScope;
            });
        },
        get paginatedProspects() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredProspects.slice(start, start + this.perPage);
        },
        get totalPages() {
            return Math.ceil(this.filteredProspects.length / this.perPage) || 1;
        }
    }" x-effect="searchQuery; selectedStatus; selectedType; selectedSales; selectedScope; currentPage = 1" @keydown.escape.window="modalReassign = false">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Prospek Tim Sales</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Pengawasan SPV</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Monitoring dan evaluasi seluruh database prospek anggota tim Sales di bawah wilayah Anda.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a 
                    :href="'{{ route('export.prospek') }}?' + new URLSearchParams(Object.fromEntries(Object.entries({
                        scope: selectedScope,
                        status: selectedStatus !== 'all' ? selectedStatus : '',
                        type: selectedType !== 'all' ? selectedType : '',
                        sales_id: selectedSales !== 'all' ? selectedSales : '',
                        search: searchQuery || '',
                        start_date: '{{ request('start_date', '') }}',
                        end_date: '{{ request('end_date', '') }}'
                    }).filter(([_, v]) => v !== '' && v !== null && v !== undefined)))"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs sm:text-sm font-semibold shadow-xs transition whitespace-nowrap shrink-0 cursor-pointer"
                    title="Unduh Spreadsheet Data Prospek Tim (.xlsx)"
                >
                    <svg class="w-4 h-4 text-emerald-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Unduh Spreadsheet</span>
                </a>
                <a 
                    href="{{ route('spv.prospek.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs sm:text-sm font-bold shadow-xs transition whitespace-nowrap shrink-0 cursor-pointer"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Prospek</span>
                </a>
            </div>
        </div>

        <!-- Search & Filter Controls -->
        <form method="GET" action="{{ route('spv.prospek.index') }}" class="crm-card bg-white p-4 sm:p-5 space-y-4">
            
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
                    <span>Prospek User Lain</span>
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
            <!-- Search Bar -->
            <div class="relative">
                <input 
                    type="text" 
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari nama prospek/siswa, kontak PIC, atau nomor WhatsApp..." 
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 text-xs sm:text-sm transition bg-slate-50/50 focus:bg-white"
                >
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <!-- Typeahead Searchable Select Filters -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Dropdown Typeahead: Sekolah -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Nama Sekolah
                    </label>
                    <x-searchable-select 
                        name="sekolah_id" 
                        :options="$sekolahs" 
                        :value="request('sekolah_id')" 
                        placeholder="Semua Sekolah..." 
                    />
                </div>

                <!-- Dropdown Typeahead: Prodi -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                        Minat Prodi
                    </label>
                    <x-searchable-select 
                        name="prodi_id" 
                        :options="$prodis" 
                        :value="request('prodi_id')" 
                        placeholder="Semua Program Studi..." 
                    />
                </div>

                <!-- Dropdown Typeahead: Wilayah -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Wilayah / Teritori
                    </label>
                    <x-searchable-select 
                        name="wilayah_id" 
                        :options="$wilayahs" 
                        :value="request('wilayah_id')" 
                        placeholder="Semua Wilayah..." 
                    />
                </div>

                <!-- Dropdown Typeahead: Pemilik Lead (Sales/CS) -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Pemilik Lead (Sales / CS)
                    </label>
                    <x-searchable-select 
                        name="sales_id" 
                        :options="$teamMembers" 
                        :value="request('sales_id')" 
                        placeholder="Semua Handler..." 
                    />
                </div>
            </div>

            <!-- Row 3: Status & Tipe & Actions -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-end justify-between gap-3 pt-1 border-t border-slate-100">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 flex-1">
                    <!-- Filter Status (8 Status Pipeline PMB TA 2027/2028) -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Status Pipeline (TA 2027/2028)</label>
                        <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:ring-2 focus:ring-blue-500/20">
                            <option value="">Semua Status (8 Tahapan)</option>
                            @foreach($statuses as $st)
                                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>
                                    {{ $st }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Sumber / Source -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Sumber Prospek</label>
                        <select name="source" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:ring-2 focus:ring-blue-500/20">
                            <option value="">Semua Sumber</option>
                            @foreach($sources as $src)
                                <option value="{{ $src }}" {{ request('source') === $src ? 'selected' : '' }}>{{ $src }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @if(request()->hasAny(['q', 'sekolah_id', 'prodi_id', 'wilayah_id', 'sales_id', 'status', 'source']))
                        <a 
                            href="{{ route('spv.prospek.index') }}" 
                            class="px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition"
                        >
                            Reset
                        </a>
                    @endif
                    <button 
                        type="submit" 
                        class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        <span>Terapkan Filter</span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Prospects Table -->
        <div class="crm-card bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3.5 px-3 w-10 text-center">No</th>
                            <th class="py-3.5 px-4">Nama Siswa / PIC</th>
                            <th class="py-3.5 px-3">Asal Sekolah / Wilayah</th>
                            <th class="py-3.5 px-3 whitespace-nowrap">Kelas Kuliah</th>
                            <th class="py-3.5 px-3">WhatsApp</th>
                            <th class="py-3.5 px-3">Status Pipeline</th>
                            <th class="py-3.5 px-3">Status Takeover</th>
                            <th class="py-3.5 px-3">Pemilik Lead</th>
                            <th class="py-3.5 px-3">Aktivitas</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(p, index) in paginatedProspects" :key="p.id + '-' + currentPage">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-4 px-3 text-center font-bold text-slate-400 text-xs" x-text="(currentPage - 1) * perPage + index + 1"></td>

                                {{-- Kolom 1: Nama Siswa / PIC --}}
                                <td class="py-4 px-4 font-semibold text-slate-900">
                                    <a :href="'/spv/prospek/' + p.id" class="hover:text-blue-600 text-xs font-bold block"
                                       x-text="(p.pic && p.pic !== '-') ? p.pic : p.name"></a>
                                    <div class="flex items-center gap-1.5 flex-wrap mt-1">
                                        <template x-if="p.no_registrasi">
                                            <span class="inline-flex items-center gap-1 font-mono text-[10px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200 shadow-2xs" title="No. Registrasi">
                                                <svg class="w-3 h-3 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                                <span x-text="p.no_registrasi"></span>
                                            </span>
                                        </template>
                                        <template x-if="p.gelombang">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-teal-700 bg-teal-50 px-1.5 py-0.5 rounded border border-teal-200 shadow-2xs" title="Gelombang Pendaftaran">
                                                <span x-text="p.gelombang"></span>
                                            </span>
                                        </template>
                                        <div class="text-[10px] text-slate-400 font-medium" x-show="p.is_presensi">
                                            <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold bg-emerald-50 px-1.5 py-0.2 rounded border border-emerald-200 text-[9px]">Presensi Siswa</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Kolom 2: Asal Sekolah / Wilayah --}}
                                <td class="py-4 px-3">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium" x-text="p.type"></span>
                                    <div class="font-semibold text-slate-800 text-[11px] mt-1" x-show="p.sekolah_nama && p.sekolah_nama !== '-'" x-text="p.sekolah_nama"></div>
                                    <div class="text-[10px] text-slate-400 font-medium mt-0.5" x-show="p.wilayah_nama" x-text="p.wilayah_nama"></div>
                                    <div class="text-[10px] text-slate-500 font-semibold" x-show="p.takeover_sales && p.takeover_sales !== '-'" x-text="'Sales: ' + p.takeover_sales"></div>
                                    <div class="text-[10px] text-teal-600 font-semibold" x-show="p.takeover_cs && p.takeover_cs !== '-'" x-text="'CS: ' + p.takeover_cs"></div>
                                </td>

                                {{-- Kolom 3: Kelas --}}
                                <td class="py-4 px-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                          :class="(p.kelas && p.kelas.toLowerCase().includes('karyawan')) ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-blue-50 text-blue-700 border-blue-200'"
                                          x-text="p.kelas || 'Reguler'"></span>
                                </td>

                                {{-- Kolom 4: WhatsApp --}}
                                <td class="py-4 px-3">
                                    <a :href="'https://wa.me/' + (p.whatsapp || '').replace(/[^0-9]/g, '')" target="_blank"
                                       x-show="p.whatsapp && p.whatsapp !== '-'"
                                       class="text-[11px] text-emerald-600 hover:text-emerald-700 flex items-center gap-1 font-medium">
                                        <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                        <span x-text="p.whatsapp"></span>
                                    </a>
                                    <span x-show="!p.whatsapp || p.whatsapp === '-'" class="text-slate-400 text-[11px]">-</span>
                                </td>

                                {{-- Kolom 5: Status Pipeline --}}
                                <td class="py-4 px-3">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-block"
                                        :class="{
                                            'bg-slate-100 text-slate-700 border border-slate-200': p.status === 'BARU',
                                            'bg-blue-50 text-blue-700 border border-blue-200': p.status === 'KONTAK',
                                            'bg-amber-50 text-amber-700 border border-amber-200': p.status === 'PROSPEK' || p.status === 'HANGAT',
                                            'bg-orange-50 text-orange-700 border border-orange-200': p.status === 'HOT PROSPEK' || p.status === 'PANAS',
                                            'bg-purple-50 text-purple-700 border border-purple-200': p.status === 'FORMULIR',
                                            'bg-cyan-50 text-cyan-700 border border-cyan-200': p.status === 'BERKAS',
                                            'bg-emerald-100 text-emerald-800 border border-emerald-300 font-extrabold shadow-2xs': p.status === 'LUNAS',
                                            'bg-gray-100 text-gray-600 border border-gray-200': p.status === 'NO RESPON' || p.status === 'DINGIN'
                                        }"
                                        x-text="p.status">
                                    </span>
                                </td>

                                {{-- Kolom 6: Status Takeover --}}
                                <td class="py-4 px-3">
                                    <span
                                        :class="p.status_takeover === 'Sudah Di-takeover' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200'"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-semibold border"
                                        x-text="p.status_takeover || 'Belum Di-takeover'"
                                    ></span>
                                    <div class="text-[10px] text-slate-500 font-medium mt-0.5" x-show="p.takeover_by_name" x-text="'Oleh: ' + p.takeover_by_name"></div>
                                    <div class="text-[9px] text-slate-400 mt-0.5" x-show="p.takeover_at" x-text="p.takeover_at"></div>
                                </td>

                                {{-- Kolom 7: Owner Awal --}}
                                <td class="py-4 px-3 text-slate-600 font-medium">
                                    <span x-text="p.owner || 'Sistem'"></span>
                                    <span x-show="!p.owner_id || p.owner === 'Sistem'" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-100 text-slate-600 border border-slate-200 block mt-0.5 w-fit">Sistem</span>
                                </td>

                                {{-- Kolom 8: Aktivitas --}}
                                <td class="py-4 px-3 text-slate-500 text-[11px]" x-text="p.last_activity"></td>

                                {{-- Kolom 9: Aksi --}}
                                <td class="py-4 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openReassign(p)"
                                            class="px-2.5 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold text-xs transition inline-flex items-center gap-1 border border-amber-200"
                                        >
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                            <span>Realokasi</span>
                                        </button>
                                        <a :href="'/spv/prospek/' + p.id" class="p-1.5 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition" title="Lihat Detail">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                        <button
                                            type="button"
                                            @click="openDeleteProspectModal(p)"
                                            class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition cursor-pointer"
                                            title="Hapus Prospek"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredProspects.length === 0">
                            <td colspan="10" class="py-12 text-center text-slate-400 text-xs">
                                Tidak ada prospek yang cocok dengan kriteria pencarian / filter Anda.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Control -->
            <x-table-pagination
                total="filteredProspects.length"
                page="currentPage"
                perPage="perPage"
                totalPages="totalPages"
                color="blue"
            />
        </div>

        <!-- ── MODAL REALOKASI SALES ── -->
        <div
            x-show="modalReassign"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
        >
            <div
                class="bg-white rounded-2xl w-full max-w-md shadow-2xl border border-slate-200 overflow-hidden"
                @click.away="modalReassign = false"
                x-show="modalReassign"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
            >
                <template x-if="reassignTarget">
                    <div>
                        <!-- Header -->
                        <div class="px-5 py-4 bg-amber-50 border-b border-amber-100 flex items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-amber-100">
                                        <svg class="w-3.5 h-3.5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                    </span>
                                    <span class="text-xs font-bold text-amber-800 uppercase tracking-wider">Realokasi Sales Handler</span>
                                </div>
                                <p class="text-sm font-bold text-slate-900 mt-1" x-text="reassignTarget.name"></p>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Handler saat ini: <strong class="text-slate-700" x-text="reassignTarget.takeover_sales || 'Belum Ditugaskan'"></strong>
                                </p>
                            </div>
                            <button @click="modalReassign = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Form -->
                        <form
                            :action="'/spv/prospek/' + reassignTarget.id + '/reassign'"
                            method="POST"
                            class="p-5 space-y-4"
                        >
                            @csrf

                            <div class="p-3 bg-amber-50/80 rounded-xl border border-amber-200/70 text-[11px] text-amber-800 leading-relaxed flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div>
                                    <strong class="font-bold">Aturan Re-alokasi:</strong> Hitungan interaksi (<code class="bg-amber-100 px-1 py-0.5 rounded text-[10px] font-mono">follow_up_count</code>) akan di-reset ke 0 untuk penangan baru, namun seluruh riwayat aktivitas sebelumnya tetap tersimpan di timeline.
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5">Pilih Petugas Baru (Sales / CS) <span class="text-red-500">*</span></label>
                                <select
                                    name="sales_id"
                                    required
                                    class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition"
                                >
                                    <option value="">-- Pilih Sales atau CS --</option>
                                    @foreach($teamMembers as $m)
                                        <option value="{{ $m->id }}">[{{ $m->role }}] {{ $m->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5">Alasan Realokasi</label>
                                <textarea
                                    name="reason"
                                    rows="3"
                                    placeholder="Contoh: Sales sebelumnya resign, wilayah direstrukturisasi, dll."
                                    class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition resize-none"
                                ></textarea>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-1">
                                <button
                                    type="button"
                                    @click="modalReassign = false"
                                    class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer"
                                >Batal</button>
                                <button
                                    type="submit"
                                    class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-sm transition cursor-pointer flex items-center gap-1.5"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                    Simpan Realokasi
                                </button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>
        <!-- ── END MODAL REALOKASI ── -->

        <!-- ── MODAL KONFIRMASI HAPUS PROSPEK ── -->
        <div
            x-show="modalDeleteProspek"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
        >
            <div
                class="bg-white rounded-2xl w-full max-w-sm shadow-2xl border border-slate-200 p-6 text-center"
                @click.away="modalDeleteProspek = false"
                @keydown.escape.window="modalDeleteProspek = false"
            >
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
                        :action="'/spv/prospek/' + (deleteProspectTarget ? deleteProspectTarget.id : '')"
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

</x-app-layout>
