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
            :actionLabel="in_array(auth()->user()->role ?? '', ['Sales', 'CS']) ? 'Tambah Prospek Baru' : null"
            :actionClick="in_array(auth()->user()->role ?? '', ['Sales', 'CS']) ? 'modalTambahProspek = true' : null"
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak>
        <x-error-state />
    </div>

    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6" x-data="{
        searchQuery: '',
        selectedStatus: 'all',
        selectedType: 'all',
        selectedTakeover: 'all',
        currentPage: 1,
        perPage: 25,
        prospectsList: {{ json_encode($prospects) }},
        
        get filteredProspects() {
            return this.prospectsList.filter(p => {
                const matchSearch = !this.searchQuery || 
                    p.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                    p.pic.toLowerCase().includes(this.searchQuery.toLowerCase());
                
                const matchStatus = this.selectedStatus === 'all' || p.status === this.selectedStatus;
                const matchType = this.selectedType === 'all' || p.type === this.selectedType;
                const matchTakeover = this.selectedTakeover === 'all' || 
                    (this.selectedTakeover === 'sales' && p.active_takeover.includes('Sales')) ||
                    (this.selectedTakeover === 'cs' && p.active_takeover.includes('CS'));
                
                return matchSearch && matchStatus && matchType && matchTakeover;
            });
        },
        get paginatedProspects() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredProspects.slice(start, start + this.perPage);
        },
        get totalPages() {
            return Math.ceil(this.filteredProspects.length / this.perPage) || 1;
        }
    }" x-effect="searchQuery; selectedStatus; selectedType; selectedTakeover; currentPage = 1">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Manajemen Prospek</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola data calon mahasiswa, sekolah mitra, dan instansi inbound UCIC.</p>
            </div>
            @if(in_array(auth()->user()->role ?? '', ['Sales', 'CS']))
            <div>
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
            </div>
            @endif
        </div>

        <!-- Search & Filter Controls -->
        <div class="crm-card bg-white p-4 sm:p-5 space-y-4">
            
            <!-- Big Search Bar -->
            <div class="relative">
                <input 
                    type="text" 
                    x-model="searchQuery"
                    placeholder="Cari nama sekolah, instansi, kontak PIC, atau nomor telepon..." 
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


                <!-- Reset Filter Button -->
                <button 
                    type="button" 
                    @click="selectedStatus = 'all'; selectedType = 'all'; selectedTakeover = 'all'; searchQuery = ''; @if(isset($isHm) && $isHm && (request('spv_id') || request('sales_id'))) window.location.href = '{{ route('prospek.index') }}'; @endif"
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
                            <th class="py-3.5 px-4">Nama Prospek</th>
                            <th class="py-3.5 px-3">Tipe</th>
                            <th class="py-3.5 px-3">WhatsApp</th>
                            <th class="py-3.5 px-3">Status Pipeline</th>
                            <th class="py-3.5 px-3">Takeover Aktif</th>
                            <th class="py-3.5 px-3">Owner</th>
                            <th class="py-3.5 px-3">Aktivitas</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(prospect, index) in paginatedProspects" :key="prospect.id">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-4 px-3 text-center font-bold text-slate-400 text-xs" x-text="(currentPage - 1) * perPage + index + 1"></td>
                                <td class="py-4 px-4 font-semibold text-slate-900">
                                    <a :href="'{{ auth()->user()->role === 'Sales' ? '/sales' : (auth()->user()->role === 'SPV' ? '/spv' : '') }}/prospek/' + prospect.id" class="hover:text-blue-600 text-xs font-bold block" x-text="(prospect.pic && prospect.pic !== '-') ? prospect.pic : prospect.name"></a>
                                </td>
                                <td class="py-4 px-3">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium" x-text="prospect.type"></span>
                                    <div class="text-[10px] text-slate-500 mt-1 font-semibold" x-show="prospect.sekolah_name && prospect.sekolah_name !== '-'" x-text="prospect.sekolah_name"></div>
                                    <div class="text-[10px] text-slate-500 font-semibold" x-show="prospect.asal_kelas && prospect.asal_kelas !== '-'" x-text="'Kelas: ' + prospect.asal_kelas"></div>
                                    <div class="text-[10px] text-slate-500 font-semibold" x-show="prospect.sales_name && prospect.sales_name !== '-'" x-text="'Sales: ' + prospect.sales_name"></div>
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
                                        :class="prospect.active_takeover.includes('CS') ? 'bg-teal-50 text-teal-800 border-teal-200' : 'bg-blue-50 text-blue-700 border-blue-200'"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-semibold border"
                                        x-text="prospect.active_takeover"
                                    ></span>
                                </td>
                                <td class="py-4 px-3 text-slate-600 font-medium" x-text="prospect.owner"></td>
                                <td class="py-4 px-3 text-slate-500 text-[11px]" x-text="prospect.last_activity"></td>
                                <td class="py-4 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button 
                                            @click="selectedProspect = prospect; modalFollowUp = true"
                                            class="p-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition cursor-pointer"
                                            title="Follow Up"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                        </button>
                                        <a 
                                            :href="'{{ auth()->user()->role === 'Sales' ? '/sales' : (auth()->user()->role === 'SPV' ? '/spv' : '') }}/prospek/' + prospect.id"
                                            class="p-1.5 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition"
                                            title="Lihat Detail"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                        </a>
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
                                    <a :href="'{{ auth()->user()->role === 'Sales' ? '/sales' : (auth()->user()->role === 'SPV' ? '/spv' : '') }}/prospek/' + prospect.id" class="font-bold text-xs text-slate-900 block" x-text="(prospect.pic && prospect.pic !== '-') ? prospect.pic : prospect.name"></a>
                                    <span class="text-[11px] text-slate-500 block" x-text="prospect.type"></span>
                                    <span class="text-[10px] text-slate-400 block font-semibold mt-0.5" x-show="prospect.sekolah_name && prospect.sekolah_name !== '-'" x-text="prospect.sekolah_name"></span>
                                    <span class="text-[10px] text-slate-400 block font-semibold" x-show="prospect.sales_name && prospect.sales_name !== '-'" x-text="'Sales: ' + prospect.sales_name"></span>
                                </div>
                            </div>
                            <span 
                                :class="'badge-' + prospect.status.toLowerCase().replace(/ /g, '-')"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                                x-text="prospect.status"
                            ></span>
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
                                class="flex-1 py-2 rounded-xl bg-blue-50 text-blue-700 border border-blue-200/80 text-xs font-semibold text-center"
                            >
                                Follow Up
                            </button>
                            <button 
                                @click="selectedProspect = prospect; modalUpdateStatus = true"
                                class="py-2 px-3 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold"
                            >
                                Status
                            </button>
                            <a 
                                :href="'{{ auth()->user()->role === 'Sales' ? '/sales' : '' }}/prospek/' + prospect.id"
                                class="py-2 px-3 rounded-xl bg-blue-600 text-white text-xs font-semibold shadow-xs"
                            >
                                Detail
                            </a>
                        </div>
                    </div>
                </template>
            </div>
            <x-table-pagination total="filteredProspects.length" page="currentPage" perPage="perPage" totalPages="totalPages" color="blue" />
        </div>

    </div>

</x-app-layout>
