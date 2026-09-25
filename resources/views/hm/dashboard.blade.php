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
                <form action="{{ route('dashboard.hm') }}" method="GET" class="flex items-center" id="filterSpvForm">
                    <select name="spv_id" onchange="document.getElementById('filterSpvForm').submit()" class="text-xs rounded-xl border-slate-200 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 transition py-2 pl-3 pr-8">
                        <option value="">Semua SPV</option>
                        @foreach($spvs as $spv)
                            <option value="{{ $spv->id }}" {{ request('spv_id') == $spv->id ? 'selected' : '' }}>{{ $spv->name }}</option>
                        @endforeach
                    </select>
                </form>
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
