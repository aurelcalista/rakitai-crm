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
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Executive Dashboard Marketing 📊</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">Head Marketing</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Ringkasan cepat konversi lead inbound, performa unit sales, dan kemitraan UCIC 2026/2027.</p>
            </div>
            
            <div class="flex items-center gap-2">
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
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Tren Bulanan (2026)</h3>
                        <p class="text-xs text-slate-500">Pertumbuhan registrasi mahasiswa baru</p>
                    </div>

                    <!-- Visual Mini Bar Trend -->
                    <div class="space-y-3 pt-2">
                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-1">
                                <span class="text-slate-600">Juli 2026</span>
                                <span class="text-slate-900 font-bold">84 Closing</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2">
                                <div class="h-2 rounded-full bg-slate-400" style="width: 58%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-1">
                                <span class="text-slate-600">Agustus 2026</span>
                                <span class="text-slate-900 font-bold">116 Closing</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2">
                                <div class="h-2 rounded-full bg-blue-500" style="width: 80%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-1">
                                <span class="text-slate-900 font-bold">September 2026 (Berjalan)</span>
                                <span class="text-emerald-600 font-bold">142 Closing</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full bg-emerald-500" style="width: 98%"></div>
                            </div>
                        </div>
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
