@props([
    'data',
    'title' => 'Dashboard Target & Pencapaian',
    'subtitle' => 'Monitoring real-time metrik berjenjang sesuai hak akses wilayah',
    'formAction' => null
])

@php
    $periode = $data['periode'] ?? [];
    $kpiRows = $data['kpi_rows'] ?? [];
    $hierarchyRows = $data['hierarchy_rows'] ?? [];
    $periodOptions = $data['period_options'] ?? [];
    $wilayahOptions = $data['wilayah_options'] ?? collect();
    $selectedWilayah = $data['selected_wilayah'] ?? null;
    $level = $data['level'] ?? 'Sales';
    $currentKey = $periode['key'] ?? 'bulanan';
    $actionUrl = $formAction ?: url()->current();
@endphp

<div class="space-y-5" x-data="{
    currentPeriod: '{{ $currentKey }}',
    filterWilayah: '{{ $selectedWilayah?->id ?? '' }}'
}">

    <!-- Filter Bar Card -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">{{ $title }}</h3>
                </div>
                <p class="text-xs text-slate-500 mt-1">{{ $subtitle }}</p>
            </div>

            <!-- Periode & Wilayah Quick Controls -->
            <form method="GET" action="{{ $actionUrl }}" class="flex flex-wrap items-center gap-2" id="filterTargetAchievementForm">
                <!-- Wilayah Filter Searchable Dropdown Plugin (SPV, HM, Global) -->
                @if(in_array($level, ['SPV', 'HM', 'Global']) && count($wilayahOptions) > 1)
                    @php
                        $selectedWilayahObj = $wilayahOptions->firstWhere('id', request('wilayah_id'));
                        $selectedWilayahNama = $selectedWilayahObj ? $selectedWilayahObj->nama : 'Semua Wilayah Teritori';
                    @endphp
                    <div class="relative" x-data="{
                        open: false,
                        search: '',
                        selectedId: '{{ request('wilayah_id', '') }}',
                        selectedNama: '{{ addslashes($selectedWilayahNama) }}',
                        items: [
                            { id: '', nama: 'Semua Wilayah Teritori' },
                            @foreach($wilayahOptions as $w)
                                { id: '{{ $w->id }}', nama: '{{ addslashes($w->nama) }}' },
                            @endforeach
                        ],
                        get filteredItems() {
                            if (!this.search.trim()) return this.items;
                            return this.items.filter(item => item.nama.toLowerCase().includes(this.search.toLowerCase()));
                        },
                        selectWilayah(item) {
                            this.selectedId = item.id;
                            this.selectedNama = item.nama;
                            this.open = false;
                            $nextTick(() => {
                                $refs.wilayahFormInput.value = item.id;
                                document.getElementById('filterTargetAchievementForm').submit();
                            });
                        }
                    }" @click.outside="open = false">

                        <input type="hidden" name="wilayah_id" x-ref="wilayahFormInput" :value="selectedId">

                        <!-- Trigger Button Plugin -->
                        <button type="button" @click="open = !open" class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 focus:ring-2 focus:ring-blue-500/20 flex items-center gap-2 cursor-pointer transition">
                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 12.414a5 5 0 10-1.414 1.414l4.243 4.243a1 1 0 001.414-1.414zM15 11a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span x-text="selectedId ? selectedNama : 'Semua Wilayah Teritori'"></span>
                            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Searchable Dropdown Popup Menu -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute left-0 lg:right-0 lg:left-auto mt-1.5 w-64 max-w-[calc(100vw-2.5rem)] bg-white rounded-2xl shadow-xl border border-slate-200/90 z-50 p-2.5 overflow-hidden"
                             style="display: none;">
                            
                            <!-- Search Input Box Plugin -->
                            <div class="relative mb-2">
                                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input type="text" 
                                       x-model="search" 
                                       placeholder="Cari Wilayah Teritori..." 
                                       class="w-full text-xs pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-slate-800 font-medium"
                                       @keydown.escape="open = false">
                            </div>

                            <!-- Filtered Options List -->
                            <div class="max-h-52 overflow-y-auto space-y-0.5 custom-scrollbar">
                                <template x-for="item in filteredItems" :key="item.id">
                                    <button type="button" 
                                            @click="selectWilayah(item)" 
                                            class="w-full text-left px-3 py-1.5 text-xs rounded-xl transition flex items-center justify-between font-semibold"
                                            :class="selectedId == item.id ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-700 hover:bg-slate-50'">
                                        <span x-text="item.nama"></span>
                                        <svg x-show="selectedId == item.id" class="w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                @endif
            </form>
        </div>

        <!-- Filter Periode (Harian, Mingguan, Bulanan, Tahunan) -->
        <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-slate-100">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-1">Filter Periode:</span>
            @foreach($periodOptions as $opt)
                @php
                    $isActive = $currentKey === $opt['key'];
                    $btnUrl = $actionUrl . '?periode=' . $opt['key'] . (request('wilayah_id') ? '&wilayah_id=' . request('wilayah_id') : '');
                @endphp
                <a href="{{ $btnUrl }}" class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5
                    {{ $isActive 
                        ? 'bg-blue-600 text-white shadow-xs ring-2 ring-blue-500/20' 
                        : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/80' }}
                ">
                    <span>{{ $opt['label'] }}</span>
                </a>
            @endforeach

            <!-- Periode Active Badge -->
            <div class="ml-auto hidden sm:flex items-center gap-2 text-xs font-semibold text-slate-600">
                <span class="text-slate-400">Rentang:</span>
                <span class="bg-slate-100 px-2.5 py-1 rounded-lg text-slate-800 font-bold">{{ $periode['label'] ?? '-' }}</span>
                <span class="text-slate-300">•</span>
                <span class="bg-indigo-50 text-indigo-700 px-2.5 py-1 rounded-lg font-bold">
                    ⏳ Sisa Waktu: {{ $periode['sisa_hari'] ?? 1 }} Hari
            </div>
        </div>
    </div>

    <!-- CASCADING FLOW INDICATOR & INDIKATOR WARNA DASAR (Tahunan → Bulanan → Mingguan → Harian) -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-4 sm:p-5 rounded-2xl shadow-xs space-y-3.5 border border-slate-800">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-white/10">
            <div>
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-indigo-300">Hierarki Target Berjenjang & Sistem Defisit</span>
                <h4 class="text-sm font-bold text-white flex items-center gap-2 mt-0.5">
                    <span>Target Akumulasi Cascading (Tahunan &rarr; Bulanan &rarr; Mingguan &rarr; Harian)</span>
            </div>
            
            <!-- Indikator Warna Dasar Legend -->
            <div class="flex flex-wrap items-center gap-2 text-[10px] font-bold">
                <span class="text-slate-400 font-medium">Indikator Warna:</span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> &ge;100% Tercapai
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/40">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span> 70-99% On Track
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> 40-69% Perlu Perhatian
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/40">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> &lt;40% Defisit Target
                </span>
            </div>
        </div>

        <!-- 4 Stepper Level Cascading -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
            <!-- 1. Tahunan -->
            <a href="{{ $actionUrl . '?periode=tahunan' . (request('wilayah_id') ? '&wilayah_id=' . request('wilayah_id') : '') }}" 
               class="p-3 rounded-xl border transition relative {{ $currentKey === 'tahunan' ? 'bg-indigo-600/40 border-indigo-400/80 ring-2 ring-indigo-400/40 text-white' : 'bg-white/5 border-white/10 hover:bg-white/10 text-slate-300' }}">
                <div class="flex items-center justify-between">
                    @if($currentKey === 'tahunan')
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    @endif
                </div>
                <div class="font-extrabold text-sm mt-1">Target Tahunan</div>
                <div class="text-[10px] text-slate-300 mt-0.5">Akumulasi TA (Basis &times; 12)</div>
            </a>

            <!-- 2. Bulanan -->
            <a href="{{ $actionUrl . '?periode=bulanan' . (request('wilayah_id') ? '&wilayah_id=' . request('wilayah_id') : '') }}" 
               class="p-3 rounded-xl border transition relative {{ $currentKey === 'bulanan' ? 'bg-indigo-600/40 border-indigo-400/80 ring-2 ring-indigo-400/40 text-white' : 'bg-white/5 border-white/10 hover:bg-white/10 text-slate-300' }}">
                <div class="flex items-center justify-between">
                    @if($currentKey === 'bulanan')
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    @endif
                </div>
                <div class="font-extrabold text-sm mt-1">Target Bulanan</div>
                <div class="text-[10px] text-slate-300 mt-0.5">Basis Evaluasi Utama</div>
            </a>

            <!-- 3. Mingguan -->
            <a href="{{ $actionUrl . '?periode=mingguan' . (request('wilayah_id') ? '&wilayah_id=' . request('wilayah_id') : '') }}" 
               class="p-3 rounded-xl border transition relative {{ $currentKey === 'mingguan' ? 'bg-indigo-600/40 border-indigo-400/80 ring-2 ring-indigo-400/40 text-white' : 'bg-white/5 border-white/10 hover:bg-white/10 text-slate-300' }}">
                <div class="flex items-center justify-between">
                    @if($currentKey === 'mingguan')
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    @endif
                </div>
                <div class="font-extrabold text-sm mt-1">Target Mingguan</div>
                <div class="text-[10px] text-slate-300 mt-0.5">Monitoring Tim (&divide; 4 Mgg)</div>
            </a>

            <!-- 4. Harian -->
            <a href="{{ $actionUrl . '?periode=harian' . (request('wilayah_id') ? '&wilayah_id=' . request('wilayah_id') : '') }}" 
               class="p-3 rounded-xl border transition relative {{ $currentKey === 'harian' ? 'bg-indigo-600/40 border-indigo-400/80 ring-2 ring-indigo-400/40 text-white' : 'bg-white/5 border-white/10 hover:bg-white/10 text-slate-300' }}">
                <div class="flex items-center justify-between">
                    @if($currentKey === 'harian')
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    @endif
                </div>
                <div class="font-extrabold text-sm mt-1">Target Harian</div>
                <div class="text-[10px] text-slate-300 mt-0.5">Operasional Tim (&divide; 25 Hari)</div>
            </a>
        </div>
    </div>

    @php
        $periodeLabelNama = match($currentKey) {
            'tahunan'  => 'Tahunan',
            'bulanan'  => 'Bulanan',
            'mingguan' => 'Mingguan',
            'harian'   => 'Harian',
            default    => 'Periode'
        };
    @endphp

    <!-- TABEL 1: METRIK INDIKATOR UTAMA (Per Baris Metrik) -->
    <div class="crm-card bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>Monitoring Target & Realisasi {{ $periodeLabelNama }} — {{ $periode['label'] }}</span>
                </h4>
                <p class="text-[11px] text-slate-500 mt-0.5">Sistem monitoring berjenjang: Target | Realisasi | Akumulasi Realisasi | Sisa Target | Defisit Target | Indikator Pencapaian</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200">
                        <th class="py-3 px-4">Indikator Kinerja</th>
                        <th class="py-3 px-3 text-center">Target {{ $periodeLabelNama }}</th>
                        <th class="py-3 px-3 text-center">Realisasi {{ $periodeLabelNama }}</th>
                        <th class="py-3 px-3 text-center">Akumulasi Realisasi</th>
                        <th class="py-3 px-3 text-center">Sisa Target</th>
                        <th class="py-3 px-3 text-center">Defisit Target</th>
                        <th class="py-3 px-4 text-center min-w-[160px]">Indikator Pencapaian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($kpiRows as $row)
                        @php
                            $achieve = $row['achievement_pct'];
                            $colorClass = $achieve >= 100 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : ($achieve >= 70 ? 'text-blue-700 bg-blue-50 border-blue-200' : ($achieve >= 40 ? 'text-amber-700 bg-amber-50 border-amber-200' : 'text-rose-700 bg-rose-50 border-rose-200'));
                            $barColor = $achieve >= 100 ? 'bg-emerald-500' : ($achieve >= 70 ? 'bg-blue-600' : ($achieve >= 40 ? 'bg-amber-500' : 'bg-rose-500'));
                            $defisit = $row['defisit_target'] ?? ($row['pencapaian'] < $row['target'] ? ($row['target'] - $row['pencapaian']) : 0);
                            $sisaTgt = $row['sisa_target'] ?? max(0, $row['target'] - $row['pencapaian']);
                            $akumulasi = $row['akumulasi_realisasi'] ?? $row['pencapaian'];
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-slate-900 text-xs">{{ $row['label'] }}</span>
                                    @if(!empty($row['badge']))
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-blue-100 text-blue-800 uppercase tracking-wider">{{ $row['badge'] }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-3 text-center font-bold text-slate-800 text-xs">
                                {{ number_format($row['target']) }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-extrabold text-blue-900 text-xs">
                                {{ number_format($row['pencapaian']) }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-bold text-xs">
                                <span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100 font-extrabold">
                                    {{ number_format($akumulasi) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 text-center font-semibold text-xs text-slate-600">
                                {{ number_format($sisaTgt) }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-bold text-xs">
                                @if($defisit > 0)
                                    <span class="px-2 py-0.5 rounded-md font-extrabold text-[11px] bg-rose-50 text-rose-700 border border-rose-200 inline-block">
                                        -{{ number_format($defisit) }}
                                    </span>
                                @else
                                    <span class="text-emerald-600 font-bold text-[11px]">
                                        0 (Surplus)
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border inline-flex items-center gap-1.5 {{ $row['status_badge'] ?? $colorClass }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $row['status_dot'] ?? 'bg-blue-500' }}"></span>
                                        {{ $row['status_label'] ?? ($achieve . '%') }} ({{ $achieve }}%)
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-400">Belum ada target yang dikonfigurasi pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TABEL 2: BREAKDOWN BERJENJANG (Sales / Tim SPV / Wilayah HM / Global) -->
    @if(count($hierarchyRows) > 1 || (count($hierarchyRows) === 1 && $level === 'Sales'))
    <div class="crm-card bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span>
                        @if($level === 'SPV')
                            Monitoring Target Mingguan Tim Sales & CS (SPV)
                        @elseif($level === 'HM')
                            Breakdown Target & Performa Per-Wilayah Teritori & SPV
                        @elseif($level === 'Global')
                            Breakdown Per-Wilayah Kampus UCIC
                        @else
                            Ringkasan Tanggung Jawab Personal
                        @endif
                    </span>
                </h4>
                <p class="text-[11px] text-slate-500 mt-0.5">Pengawasan berjenjang Target {{ $periodeLabelNama }}, Realisasi, Akumulasi, Sisa, dan Defisit per entitas</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200">
                        <th class="py-3 px-4">Nama Personil / Teritori</th>
                        <th class="py-3 px-3">Role & Wilayah</th>
                        <th class="py-3 px-3 text-center">Target {{ $periodeLabelNama }}</th>
                        <th class="py-3 px-3 text-center">Realisasi {{ $periodeLabelNama }}</th>
                        <th class="py-3 px-3 text-center">Akumulasi Realisasi</th>
                        <th class="py-3 px-3 text-center">Sisa Target</th>
                        <th class="py-3 px-3 text-center">Defisit Target</th>
                        <th class="py-3 px-4 text-center min-w-[160px]">Indikator Pencapaian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($hierarchyRows as $hRow)
                        @php
                            $isTotal = !empty($hRow['is_total']);
                            $hAchieve = $hRow['achievement_pct'];
                            $hColorClass = $hAchieve >= 100 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : ($hAchieve >= 70 ? 'text-blue-700 bg-blue-50 border-blue-200' : ($hAchieve >= 40 ? 'text-amber-700 bg-amber-50 border-amber-200' : 'text-rose-700 bg-rose-50 border-rose-200'));
                            $hBarColor = $hAchieve >= 100 ? 'bg-emerald-500' : ($hAchieve >= 70 ? 'bg-blue-600' : ($hAchieve >= 40 ? 'bg-amber-500' : 'bg-rose-500'));
                            $hDefisit = $hRow['defisit_target'] ?? ($hRow['pencapaian'] < $hRow['target'] ? ($hRow['target'] - $hRow['pencapaian']) : 0);
                            $hSisaTgt = $hRow['sisa_target'] ?? max(0, $hRow['target'] - $hRow['pencapaian']);
                            $hAkumulasi = $hRow['akumulasi_realisasi'] ?? $hRow['pencapaian'];
                        @endphp
                        <tr class="{{ $isTotal ? 'bg-slate-50 font-bold border-y-2 border-slate-200' : 'hover:bg-slate-50/80 transition' }}">
                            <td class="py-3.5 px-4">
                                <div class="font-extrabold {{ $isTotal ? 'text-slate-900 text-sm' : 'text-slate-800 text-xs' }}">
                                    {{ $hRow['label'] }}
                                </div>
                                @if(!empty($hRow['allocated_by']))
                                    <span class="text-[10px] text-slate-400 block mt-0.5">Dialokasikan oleh: {{ $hRow['allocated_by'] }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold
                                        {{ ($hRow['role'] ?? '') === 'Sales' ? 'bg-blue-50 text-blue-700 border border-blue-200' : '' }}
                                        {{ ($hRow['role'] ?? '') === 'CS' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                                        {{ ($hRow['role'] ?? '') === 'SPV' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : '' }}
                                        {{ ($hRow['role'] ?? '') === 'HM' || ($hRow['role'] ?? '') === 'Global' ? 'bg-purple-50 text-purple-700 border border-purple-200' : '' }}
                                    ">
                                        {{ $hRow['role'] ?? 'Sales' }}
                                    </span>
                                </div>
                                <span class="text-[10px] text-slate-500 block mt-0.5">{{ $hRow['wilayah'] ?? '-' }}</span>
                            </td>
                            <td class="py-3.5 px-3 text-center font-bold text-slate-800 text-xs">
                                {{ number_format($hRow['target']) }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-extrabold text-blue-900 text-xs">
                                {{ number_format($hRow['pencapaian']) }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-bold text-xs">
                                <span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100 font-extrabold">
                                    {{ number_format($hAkumulasi) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 text-center font-semibold text-xs text-slate-600">
                                {{ number_format($hSisaTgt) }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-bold text-xs">
                                @if($hDefisit > 0)
                                    <span class="px-2 py-0.5 rounded-md font-extrabold text-[11px] bg-rose-50 text-rose-700 border border-rose-200 inline-block">
                                        -{{ number_format($hDefisit) }}
                                    </span>
                                @else
                                    <span class="text-emerald-600 font-bold text-[11px]">
                                        0 (Surplus)
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border inline-flex items-center gap-1.5 {{ $hRow['status_badge'] ?? $hColorClass }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $hRow['status_dot'] ?? 'bg-blue-500' }}"></span>
                                        {{ $hRow['status_label'] ?? ($hAchieve . '%') }} ({{ $hAchieve }}%)
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
