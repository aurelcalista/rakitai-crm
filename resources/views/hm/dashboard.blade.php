@php
    $pageTitle = 'Dashboard Head of Marketing (HM)';
    $pageSubtitle = 'Executive Marketing KPI & Conversion Overview';
@endphp

<x-app-layout :title="'Dashboard Head Marketing - CRM UCIC'">

    <!-- State Simulation Wrappers -->
    <div x-show="$store.crm.activeState === 'loading'" x-cloak>
        <x-loading-skeleton type="cards" />
        <div class="mt-6">
            <x-loading-skeleton type="table" />
        </div>
    </div>

    <div x-show="$store.crm.activeState === 'empty'" x-cloak>
        <x-empty-state 
            title="Laporan eksekutif belum tersedia" 
            description="Belum ada data rekonsiliasi penerimaan mahasiswa baru pada periode ini."
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak>
        <x-error-state />
    </div>

    <!-- NORMAL DATA STATE -->
    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6">

        <!-- Executive Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white px-5 py-4 sm:px-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Executive Dashboard Marketing </h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">Head Marketing</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Ringkasan cepat konversi lead inbound, performa unit sales, dan kemitraan UCIC 2026/2027.</p>
            </div>
            
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Unified Filter Form -->
                <form action="{{ route('dashboard.hm') }}" method="GET" id="filterDashboardForm">
                    <input type="hidden" name="spv_id" id="form_spv_id" value="{{ request('spv_id', '') }}">
                    <input type="hidden" name="sales_id" id="form_sales_id" value="{{ request('sales_id', '') }}">
                    <input type="hidden" name="wilayah_id" id="form_wilayah_id" value="{{ request('wilayah_id', '') }}">
                </form>

                <!-- 1. FILTER SPV DROPDOWN -->
                @php
                    $selectedSpvObj = $spvs->firstWhere('id', request('spv_id'));
                    $selectedSpvNama = $selectedSpvObj ? $selectedSpvObj->name : 'Semua SPV';
                @endphp
                <div class="relative" x-data="{
                    open: false,
                    search: '',
                    selectedId: '{{ request('spv_id', '') }}',
                    selectedNama: '{{ addslashes($selectedSpvNama) }}',
                    items: [
                        { id: '', nama: 'Semua SPV' },
                        @foreach($spvs as $spv)
                            { id: '{{ $spv->id }}', nama: '{{ addslashes($spv->name) }}' },
                        @endforeach
                    ],
                    get filteredItems() {
                        if (!this.search.trim()) return this.items;
                        return this.items.filter(item => item.nama.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    selectSpv(item) {
                        this.selectedId = item.id;
                        this.selectedNama = item.nama;
                        this.open = false;
                        document.getElementById('form_spv_id').value = item.id;
                        document.getElementById('filterDashboardForm').submit();
                    }
                }" @click.outside="open = false">

                    <button type="button" @click="open = !open" class="text-xs font-semibold px-3.5 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 focus:ring-2 focus:ring-purple-500/20 flex items-center gap-2 cursor-pointer transition shadow-xs">
                        <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
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
                         class="absolute right-0 mt-1.5 w-64 bg-white rounded-2xl shadow-xl border border-slate-200/90 z-50 p-2.5 overflow-hidden"
                         style="display: none;">
                        
                        <div class="relative mb-2">
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" 
                                   x-model="search" 
                                   placeholder="Cari Nama SPV..." 
                                   class="w-full text-xs pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 text-slate-800 font-medium"
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
                            
                            <div x-show="filteredItems.length === 0" class="px-3 py-3 text-xs text-slate-400 italic text-center">
                                SPV tidak ditemukan
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. FILTER TIM / SALES DROPDOWN -->
                @php
                    $selectedSalesObj = isset($salesList) ? $salesList->firstWhere('id', request('sales_id')) : null;
                    $selectedSalesNama = $selectedSalesObj ? $selectedSalesObj->name . ' (' . $selectedSalesObj->role . ')' : 'Semua Tim / Sales';
                @endphp
                <div class="relative" x-data="{
                    open: false,
                    search: '',
                    selectedId: '{{ request('sales_id', '') }}',
                    selectedNama: '{{ addslashes($selectedSalesNama) }}',
                    items: [
                        { id: '', nama: 'Semua Tim / Sales' },
                        @if(isset($salesList))
                            @foreach($salesList as $sUser)
                                { id: '{{ $sUser->id }}', nama: '{{ addslashes($sUser->name) }} ({{ $sUser->role }})' },
                            @endforeach
                        @endif
                    ],
                    get filteredItems() {
                        if (!this.search.trim()) return this.items;
                        return this.items.filter(item => item.nama.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    selectSales(item) {
                        this.selectedId = item.id;
                        this.selectedNama = item.nama;
                        this.open = false;
                        document.getElementById('form_sales_id').value = item.id;
                        document.getElementById('filterDashboardForm').submit();
                    }
                }" @click.outside="open = false">

                    <button type="button" @click="open = !open" class="text-xs font-semibold px-3.5 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 focus:ring-2 focus:ring-blue-500/20 flex items-center gap-2 cursor-pointer transition shadow-xs">
                        <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span x-text="selectedId ? selectedNama : 'Semua Tim / Sales'"></span>
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
                         class="absolute right-0 mt-1.5 w-64 bg-white rounded-2xl shadow-xl border border-slate-200/90 z-50 p-2.5 overflow-hidden"
                         style="display: none;">
                        
                        <div class="relative mb-2">
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" 
                                   x-model="search" 
                                   placeholder="Cari Nama Tim / Sales..." 
                                   class="w-full text-xs pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-slate-800 font-medium"
                                   @keydown.escape="open = false">
                        </div>

                        <div class="max-h-52 overflow-y-auto space-y-0.5 custom-scrollbar">
                            <template x-for="item in filteredItems" :key="item.id">
                                <button type="button" 
                                        @click="selectSales(item)" 
                                        class="w-full text-left px-3 py-1.5 text-xs rounded-xl transition flex items-center justify-between font-semibold"
                                        :class="selectedId == item.id ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-700 hover:bg-slate-50'">
                                    <span x-text="item.nama"></span>
                                    <svg x-show="selectedId == item.id" class="w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </template>
                            
                            <div x-show="filteredItems.length === 0" class="px-3 py-3 text-xs text-slate-400 italic text-center">
                                Anggota tim tidak ditemukan
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. FILTER WILAYAH / KECAMATAN DROPDOWN -->
                @php
                    $selectedWilayahObj = isset($wilayahList) ? $wilayahList->firstWhere('id', request('wilayah_id')) : null;
                    $selectedWilayahNama = $selectedWilayahObj ? $selectedWilayahObj->nama : 'Semua Kecamatan / Wilayah';
                @endphp
                <div class="relative" x-data="{
                    open: false,
                    search: '',
                    selectedId: '{{ request('wilayah_id', '') }}',
                    selectedNama: '{{ addslashes($selectedWilayahNama) }}',
                    items: [
                        { id: '', nama: 'Semua Kecamatan / Wilayah' },
                        @if(isset($wilayahList))
                            @foreach($wilayahList as $w)
                                { id: '{{ $w->id }}', nama: '{{ addslashes($w->nama) }}' },
                            @endforeach
                        @endif
                    ],
                    get filteredItems() {
                        if (!this.search.trim()) return this.items;
                        return this.items.filter(item => item.nama.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    selectWilayah(item) {
                        this.selectedId = item.id;
                        this.selectedNama = item.nama;
                        this.open = false;
                        document.getElementById('form_wilayah_id').value = item.id;
                        document.getElementById('filterDashboardForm').submit();
                    }
                }" @click.outside="open = false">

                    <button type="button" @click="open = !open" class="text-xs font-semibold px-3.5 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 focus:ring-2 focus:ring-emerald-500/20 flex items-center gap-2 cursor-pointer transition shadow-xs">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span x-text="selectedId ? selectedNama : 'Semua Wilayah'"></span>
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
                         class="absolute right-0 mt-1.5 w-64 bg-white rounded-2xl shadow-xl border border-slate-200/90 z-50 p-2.5 overflow-hidden"
                         style="display: none;">
                        
                        <div class="relative mb-2">
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" 
                                   x-model="search" 
                                   placeholder="Cari Wilayah / Kecamatan..." 
                                   class="w-full text-xs pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-slate-800 font-medium"
                                   @keydown.escape="open = false">
                        </div>

                        <div class="max-h-52 overflow-y-auto space-y-0.5 custom-scrollbar">
                            <template x-for="item in filteredItems" :key="item.id">
                                <button type="button" 
                                        @click="selectWilayah(item)" 
                                        class="w-full text-left px-3 py-1.5 text-xs rounded-xl transition flex items-center justify-between font-semibold"
                                        :class="selectedId == item.id ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-700 hover:bg-slate-50'">
                                    <span x-text="item.nama"></span>
                                    <svg x-show="selectedId == item.id" class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </template>
                            
                            <div x-show="filteredItems.length === 0" class="px-3 py-3 text-xs text-slate-400 italic text-center">
                                Wilayah tidak ditemukan
                            </div>
                        </div>
                    </div>
                </div>
                <a 
                    href="{{ route('admin.target.index') }}"
                    class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4 text-purple-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>Kelola & Beri Target SPV</span>
                </a>
                <a 
                    href="{{ route('laporan.index') }}"
                    class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Laporan Lengkap & Export</span>
                </a>
            </div>
        </div>

        <!-- DASHBOARD TARGET & PENCAPAIAN BERJENJANG (PRD 6.2) -->
        @if(isset($targetAchievementData))
            <x-target-achievement-table 
                :data="$targetAchievementData" 
                title="Dashboard Target & Pencapaian Head of Marketing (HM)" 
                subtitle="Monitoring berjenjang seluruh wilayah teritori, kekurangan, sisa hari, dan target harian berjalan"
            />
        @endif

        <!-- TABEL PER WILAYAH (HM DASHBOARD) -->
        @if(isset($targetAchievementData['territory_table']) && count($targetAchievementData['territory_table']) > 0)
            <div class="crm-card bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b pb-3 border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 tracking-tight">Tabel Breakdown Per Wilayah Teritori</h3>
                        <p class="text-xs text-slate-500">Pemetaan SPV, Sales Aktif, CS Aktif, Target, dan Pencapaian Maba Lunas per Wilayah</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200 self-start sm:self-auto">
                        Scope Head Marketing
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left text-slate-700">
                        <thead class="text-[11px] font-bold uppercase text-slate-500 bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-3 py-3 w-10 text-center">No</th>
                                <th class="px-4 py-3">Wilayah</th>
                                <th class="px-4 py-3">SPV</th>
                                <th class="px-4 py-3">Sales Aktif</th>
                                <th class="px-4 py-3">CS Aktif</th>
                                <th class="px-4 py-3 text-right">Target</th>
                                <th class="px-4 py-3 text-right">Achievement</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($targetAchievementData['territory_table'] as $row)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-3 py-3.5 text-center font-bold text-slate-400">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3.5 font-bold text-slate-900">
                                        {{ $row['wilayah_nama'] ?? $row['label'] }}
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-700 font-medium">
                                        {{ $row['spv_name'] ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-semibold border border-blue-100 text-[11px]">
                                            {{ $row['sales_names'] ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-700 font-semibold border border-purple-100 text-[11px]">
                                            {{ $row['cs_names'] ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-mono font-bold text-slate-700">
                                        {{ number_format($row['target']) }}
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-mono font-bold text-emerald-600">
                                        {{ number_format($row['pencapaian']) }} ({{ $row['achievement_pct'] }}%)
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $row['status_badge'] }}">
                                            {{ $row['status_label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Executive Statistic Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
            <x-stat-card 
                title="Total Prospek" 
                :value="$stats['total_prospek']" 
                subtitle="All Inbound" 
                color="blue"
            />
            <x-stat-card 
                title="Active Lead" 
                :value="$stats['active_prospek']" 
                subtitle="In Pipeline" 
                color="indigo"
            />
            <x-stat-card 
                title="Total Closing" 
                :value="$stats['closing']" 
                subtitle="Mahasiswa Baru" 
                color="emerald"
            />
            <x-stat-card 
                title="Total Lost" 
                :value="$stats['lost']" 
                subtitle="Historical Archive" 
                color="rose"
            />
            <x-stat-card 
                title="Conversion Rate" 
                :value="$stats['conversion_rate'] . '%'" 
                subtitle="Lead to Closing" 
                color="purple"
            />
            <x-stat-card 
                title="Total Sales" 
                :value="$stats['total_sales']" 
                subtitle="Field Force" 
                color="slate"
            />
            <x-stat-card 
                title="Total CS" 
                :value="$stats['total_cs']" 
                subtitle="Follow-up Support" 
                color="teal"
            />
        </div>

        <!-- PIPELINE OVERVIEW & MONTHLY TREND -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Pipeline Overview (Executive Bar Visualization) -->
            <div class="crm-card p-6 bg-white lg:col-span-2">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-5">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Pipeline Conversion Overview</h3>
                        <p class="text-xs text-slate-500">Distribusi volume prospek berdasarkan tahapan seleksi</p>
                    </div>
                    <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-100">
                        Conversion: 29.3%
                    </span>
                </div>

                <!-- Pipeline Stages Progress Bars -->
                <div class="space-y-4">
                    @foreach($pipelineStages as $stage)
                        <div>
                            <div class="flex justify-between items-center text-xs mb-1.5 font-medium">
                                <span class="font-bold text-slate-800">{{ $stage['name'] }}</span>
                                <span class="text-slate-600"><strong>{{ $stage['count'] }}</strong> prospek ({{ $stage['pct'] }}%)</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                                <div class="h-3 rounded-full bg-blue-600 transition-all duration-500" style="width: {{ $stage['pct'] * 3 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Monthly Performance Summary -->
            <div class="crm-card p-6 bg-white flex flex-col justify-between">
                <div>
                    <div class="pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Tren Bulanan ({{ $currentYear ?? date('Y') }})</h3>
                        <p class="text-xs text-slate-500">Pertumbuhan registrasi mahasiswa baru</p>
                    </div>

                    <!-- Visual Mini Bar Trend -->
                    <div class="space-y-3 pt-2 max-h-64 overflow-y-auto pr-2">
                        @foreach($monthlyTrend as $trend)
                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-1">
                                @if($trend['is_current'])
                                    <span class="text-slate-900 font-bold">{{ $trend['month_name'] }} {{ $currentYear }} (Berjalan)</span>
                                    <span class="text-emerald-600 font-bold">{{ $trend['count'] }} Closing</span>
                                @else
                                    <span class="text-slate-600">{{ $trend['month_name'] }} {{ $currentYear }}</span>
                                    <span class="text-slate-900 font-bold">{{ $trend['count'] }} Closing</span>
                                @endif
                            </div>
                            <div class="w-full bg-slate-100 rounded-full {{ $trend['is_current'] ? 'h-2.5' : 'h-2' }}">
                                <div class="{{ $trend['is_current'] ? 'h-2.5' : 'h-2' }} rounded-full {{ $trend['is_current'] ? 'bg-emerald-500' : ($trend['count'] > 0 ? 'bg-blue-500' : 'bg-slate-300') }}" style="width: {{ max(1, $trend['percentage']) }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 bg-emerald-50/50 -mx-6 -mb-6 p-4 rounded-b-2xl border-t">
                    <div class="text-xs font-bold text-emerald-900">Performa Kuartal Q3: +24% YoY</div>
                    <div class="text-[11px] text-emerald-700 mt-0.5">Pertumbuhan tertinggi berasal dari Program Beasiswa AI & Kelas Karyawan Corporate.</div>
                </div>
            </div>

        </div>

        <!-- Sales Ranking Performance Section -->
        <div class="crm-card bg-white p-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Top Sales Performance Ranking</h3>
                    <p class="text-xs text-slate-500">Peringkat kontribusi pencapaian target pendaftaran</p>
                </div>
                <a href="{{ route('performa.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                    Buka Matriks Lengkap &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($team as $member)
                    <div class="p-4 rounded-2xl border border-slate-200/80 bg-slate-50/40 hover:bg-white hover:border-blue-300 transition space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="w-6 h-6 rounded-full {{ $loop->first ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }} font-bold text-xs flex items-center justify-center">
                                #{{ $member['rank'] }}
                            </span>
                            <span class="text-xs font-bold text-emerald-600">{{ $member['achievement'] }}% Target</span>
                        </div>

                        <div>
                            <h4 class="font-bold text-sm text-slate-900">{{ $member['name'] }}</h4>
                            <p class="text-[11px] text-slate-400">{{ $member['role'] }}</p>
                        </div>

                        <div class="flex justify-between items-center text-xs pt-2 border-t border-slate-200/60 font-semibold">
                            <span class="text-slate-500">Closing: <strong class="text-slate-900">{{ $member['closing'] }}</strong></span>
                            <span class="text-slate-500">Prospek: <strong class="text-slate-900">{{ $member['prospects'] }}</strong></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

</x-app-layout>
