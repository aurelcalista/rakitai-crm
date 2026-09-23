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
    filterWilayah: '{{ $selectedWilayah?->id ?? '' }}',
    isRealtime: {{ ($periode['is_realtime'] ?? false) ? 'true' : 'false' }},
    refreshing: false,
    refreshRealtime() {
        this.refreshing = true;
        window.location.href = '{{ $actionUrl }}?periode=realtime' + (this.filterWilayah ? '&wilayah_id=' + this.filterWilayah : '');
    }
}">

    <!-- Filter Bar Card -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">{{ $title }}</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                        {{ $level === 'Sales' ? 'bg-blue-50 text-blue-700 border border-blue-200' : '' }}
                        {{ $level === 'SPV' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : '' }}
                        {{ $level === 'HM' ? 'bg-purple-50 text-purple-700 border border-purple-200' : '' }}
                        {{ $level === 'Global' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                    ">
                        Level {{ $level }}
                    </span>
                    @if($periode['is_realtime'] ?? false)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 animate-pulse">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                            Live Realtime
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-1">{{ $subtitle }}</p>
            </div>

            <!-- Periode & Wilayah Quick Controls -->
            <form method="GET" action="{{ $actionUrl }}" class="flex flex-wrap items-center gap-2" id="filterTargetAchievementForm">
                <!-- Wilayah Filter (if applicable) -->
                @if(in_array($level, ['SPV', 'HM', 'Global']) && count($wilayahOptions) > 1)
                    <div class="relative">
                        <select name="wilayah_id" onchange="this.form.submit()" class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 focus:ring-2 focus:ring-blue-500/20">
                            <option value="">-- Semua Wilayah Teritori --</option>
                            @foreach($wilayahOptions as $w)
                                <option value="{{ $w->id }}" {{ request('wilayah_id') == $w->id ? 'selected' : '' }}>
                                    📍 {{ $w->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Refresh Button for Realtime -->
                @if($periode['is_realtime'] ?? false)
                    <button type="button" @click="refreshRealtime()" :disabled="refreshing" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-slate-600" :class="refreshing ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Refresh Data</span>
                    </button>
                @endif
            </form>
        </div>

        <!-- 5 Tombol Filter Periode Wajib PRD Bab 6.2 -->
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
                    @if($opt['key'] === 'realtime')
                        <span class="w-2 h-2 rounded-full {{ $isActive ? 'bg-amber-300' : 'bg-rose-500' }}"></span>
                    @endif
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
                </span>
            </div>
        </div>
    </div>

    <!-- TABEL 1: METRIK INDIKATOR UTAMA (Per Baris Metrik) -->
    <div class="crm-card bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>Ringkasan Indikator Kunci — {{ $periode['label'] }}</span>
                </h4>
                <p class="text-[11px] text-slate-500 mt-0.5">Metrik baris: Target | Pencapaian | Kekurangan | % Achievement | Sisa Hari | Target Harian Berjalan</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200">
                        <th class="py-3 px-4">Indikator Kinerja</th>
                        <th class="py-3 px-3 text-center">Target</th>
                        <th class="py-3 px-3 text-center">Pencapaian</th>
                        <th class="py-3 px-3 text-center">Kekurangan</th>
                        <th class="py-3 px-4 text-center min-w-[140px]">% Achievement</th>
                        <th class="py-3 px-3 text-center">Sisa Hari</th>
                        <th class="py-3 px-4 text-right">Target Harian Berjalan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($kpiRows as $row)
                        @php
                            $achieve = $row['achievement_pct'];
                            $colorClass = $achieve >= 100 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : ($achieve >= 70 ? 'text-blue-700 bg-blue-50 border-blue-200' : ($achieve >= 40 ? 'text-amber-700 bg-amber-50 border-amber-200' : 'text-rose-700 bg-rose-50 border-rose-200'));
                            $barColor = $achieve >= 100 ? 'bg-emerald-500' : ($achieve >= 70 ? 'bg-blue-600' : ($achieve >= 40 ? 'bg-amber-500' : 'bg-rose-500'));
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
                            <td class="py-3.5 px-3 text-center font-bold text-xs {{ $row['kekurangan'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                {{ $row['kekurangan'] > 0 ? '-' . number_format($row['kekurangan']) : '0 (Tercapai)' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-extrabold border {{ $colorClass }}">
                                        {{ $row['achievement_pct'] }}%
                                    </span>
                                    <div class="w-16 h-2 bg-slate-100 rounded-full overflow-hidden shrink-0 hidden sm:block">
                                        <div class="h-full {{ $barColor }}" style="width: {{ min(100, $achieve) }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-3 text-center text-slate-600 font-semibold">
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px]">
                                    {{ $row['sisa_hari'] }} Hari
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                @if($row['kekurangan'] > 0)
                                    <div class="font-black text-rose-600 text-xs">
                                        {{ $row['target_harian_berjalan'] }} / hari
                                    </div>
                                    <span class="text-[10px] text-slate-400">untuk capai target</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800">
                                        Target Terpenuhi ✓
                                    </span>
                                @endif
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
                            Breakdown Anggota Tim Sales & CS (Alokasi SPV vs HM)
                        @elseif($level === 'HM')
                            Breakdown Target & Performa Per-Wilayah Teritori & SPV
                        @elseif($level === 'Global')
                            Breakdown Per-Wilayah Kampus UCIC
                        @else
                            Ringkasan Tanggung Jawab Lead
                        @endif
                    </span>
                </h4>
                <p class="text-[11px] text-slate-500 mt-0.5">Pengawasan berjenjang Target Maba Lunas, kekurangan, dan defisit berjalan per entitas</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200">
                        <th class="py-3 px-4">Nama Personil / Teritori</th>
                        <th class="py-3 px-3">Role & Wilayah</th>
                        <th class="py-3 px-3 text-center">Target Lunas</th>
                        <th class="py-3 px-3 text-center">Pencapaian</th>
                        <th class="py-3 px-3 text-center">Kekurangan</th>
                        <th class="py-3 px-4 text-center min-w-[140px]">% Achievement</th>
                        <th class="py-3 px-3 text-center">Sisa Hari</th>
                        <th class="py-3 px-4 text-right">Target Harian Berjalan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($hierarchyRows as $hRow)
                        @php
                            $isTotal = !empty($hRow['is_total']);
                            $hAchieve = $hRow['achievement_pct'];
                            $hColorClass = $hAchieve >= 100 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : ($hAchieve >= 70 ? 'text-blue-700 bg-blue-50 border-blue-200' : ($hAchieve >= 40 ? 'text-amber-700 bg-amber-50 border-amber-200' : 'text-rose-700 bg-rose-50 border-rose-200'));
                            $hBarColor = $hAchieve >= 100 ? 'bg-emerald-500' : ($hAchieve >= 70 ? 'bg-blue-600' : ($hAchieve >= 40 ? 'bg-amber-500' : 'bg-rose-500'));
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
                            <td class="py-3.5 px-3 text-center font-bold text-xs {{ $hRow['kekurangan'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                {{ $hRow['kekurangan'] > 0 ? '-' . number_format($hRow['kekurangan']) : '0' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-extrabold border {{ $hColorClass }}">
                                        {{ $hRow['achievement_pct'] }}%
                                    </span>
                                    <div class="w-14 h-2 bg-slate-100 rounded-full overflow-hidden shrink-0 hidden sm:block">
                                        <div class="h-full {{ $hBarColor }}" style="width: {{ min(100, $hAchieve) }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-3 text-center text-slate-600 font-semibold">
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px]">
                                    {{ $hRow['sisa_hari'] }} Hari
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                @if($hRow['kekurangan'] > 0)
                                    <div class="font-black text-rose-600 text-xs">
                                        {{ $hRow['target_harian_berjalan'] }} / hari
                                    </div>
                                    <span class="text-[10px] text-slate-400">sisa beban berjalan</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800">
                                        Tercapai ✓
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
