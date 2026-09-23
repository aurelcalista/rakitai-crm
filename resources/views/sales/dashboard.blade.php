@php
    $pageTitle = 'Dashboard Sales';
    $pageSubtitle = 'Aktivitas Pribadi & Ringkasan Pipeline';
@endphp

<x-app-layout :title="'Dashboard Sales - CRM UCIC'">
    
    <!-- State Simulation Wrappers -->
    <div x-show="$store.crm.activeState === 'loading'" x-cloak>
        <x-loading-skeleton type="cards" />
        <div class="mt-6">
            <x-loading-skeleton type="table" />
        </div>
    </div>

    <div x-show="$store.crm.activeState === 'empty'" x-cloak>
        <x-empty-state 
            title="Belum ada aktivitas prospek" 
            description="Mulai tambahkan prospek baru untuk melihat statistik pipeline dan target bulan ini."
            actionLabel="Tambah Prospek Sekarang"
            actionClick="modalTambahProspek = true"
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak>
        <x-error-state />
    </div>

    <!-- NORMAL DATA STATE -->
    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6">

        <!-- Header Greeting -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Halo, {{ auth()->user()->name }} 👋</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">Sales Inbound</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Berikut ringkasan aktivitas dan performa kamu bulan ini.</p>
            </div>
            <div class="flex items-center gap-2">
                <button 
                    type="button"
                    @click="modalTambahKunjungan = true"
                    class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition flex items-center gap-1.5 cursor-pointer bg-white"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span>Lapor Kunjungan</span>
                </button>
                <button 
                    type="button"
                    @click="modalTambahProspek = true"
                    class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Prospek Baru</span>
                </button>
            </div>
        </div>

        <!-- DASHBOARD TARGET & PENCAPAIAN (PRD 6.2) -->
        @if(isset($targetAchievementData))
            <x-target-achievement-table 
                :data="$targetAchievementData" 
                title="Dashboard Target & Pencapaian Sales Pribadi" 
                subtitle="Filter periode Harian, Mingguan, Bulanan, Tahunan, dan Realtime dengan rincian metrik per baris"
            />
        @endif

        <!-- Statistic Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
            <x-stat-card 
                title="Total Prospek" 
                :value="$stats['total_prospek']" 
                subtitle="Semua data masuk" 
                trend="+12%" 
                :trendUp="true"
                color="blue"
            />
            <x-stat-card 
                title="Active Prospek" 
                :value="$stats['active_prospek']" 
                subtitle="Dalam interaksi" 
                trend="+8%" 
                :trendUp="true"
                color="indigo"
            />
            <x-stat-card 
                title="Follow Up" 
                :value="$stats['follow_up']" 
                subtitle="Jadwal aktif" 
                trend="+5%" 
                :trendUp="true"
                color="amber"
            />
            <x-stat-card 
                title="Closing" 
                :value="$stats['closing']" 
                subtitle="Terdaftar resmi" 
                trend="+18%" 
                :trendUp="true"
                color="emerald"
            />
            <x-stat-card 
                title="Lost" 
                :value="$stats['lost']" 
                subtitle="Arsip data" 
                trend="-2%" 
                :trendUp="false"
                color="rose"
            />
        </div>

        <!-- TARGET BULAN INI & PIPELINE OVERVIEW -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Card Target Bulan Ini -->
            <div class="crm-card p-6 bg-white flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Target Bulan Ini</h3>
                        <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">{{ $stats['bulan_label'] ?? now()->locale('id')->isoFormat('MMMM Y') }}</span>
                    </div>

                    <!-- Circular / Big Progress -->
                    <div class="my-5 flex items-center justify-between">
                        <div>
                            <div class="text-4xl font-extrabold text-slate-900">{{ $stats['percentage'] }}%</div>
                            @if($stats['target_bulan_ini'] > 0)
                                <div class="text-xs font-semibold text-slate-500 mt-1">{{ $stats['realisasi_closing'] }} / {{ $stats['target_bulan_ini'] }} Closing Prospek</div>
                            @else
                                <div class="text-xs font-semibold text-rose-500 mt-1">Belum ada target</div>
                            @endif
                        </div>
                        <div class="w-16 h-16 rounded-full border-4 border-blue-600 border-t-blue-100 flex items-center justify-center font-bold text-xs text-blue-700 bg-blue-50/50">
                            {{ $stats['percentage'] }}%
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                        <div class="bg-blue-600 h-3 rounded-full transition-all duration-500" style="width: {{ $stats['percentage'] }}%"></div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                    <div>
                        <span class="text-slate-400 block font-medium">Sisa Target</span>
                        <span class="text-base font-bold text-slate-800">{{ $stats['sisa_target'] }} Prospek</span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-400 block font-medium">Realisasi</span>
                        <span class="text-base font-bold text-emerald-600">{{ $stats['realisasi_closing'] }} Closing</span>
                    </div>
                </div>
            </div>

            <!-- Target Harian Pribadi -->
            <div class="crm-card p-6 bg-white flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Target Harian (Snowball)</h3>
                        <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">Hari Ini</span>
                    </div>

                    <div class="mt-5 space-y-6">
                        <!-- Kontak Baru -->
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h4 class="font-bold text-slate-800">Kontak Baru</h4>
                                <div class="text-[11px] text-slate-500 mt-1">
                                    Target hari ini: <strong class="text-slate-700">{{ $dailyTarget['target_hari_ini_kontak'] }}</strong><br>
                                    <span class="text-slate-400">(Sisa Akumulasi Kemarin: {{ $dailyTarget['sisa_akumulasi_kontak'] }})</span>
                                </div>
                            </div>
                            @php $pctKontak = $dailyTarget['target_hari_ini_kontak'] > 0 ? min(100, round(($dailyTarget['pencapaian_hari_ini_kontak'] / $dailyTarget['target_hari_ini_kontak']) * 100)) : 0; @endphp
                            <div class="relative w-14 h-14 shrink-0">
                                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                    <!-- Background (Belum terpenuhi / Abu) -->
                                    <path class="text-slate-200" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3"></path>
                                    <!-- Progress (Terpenuhi / Biru) -->
                                    <path class="text-blue-500 transition-all duration-1000 ease-out" stroke-dasharray="{{ $pctKontak }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3"></path>
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-xs font-bold text-slate-800">{{ $dailyTarget['pencapaian_hari_ini_kontak'] }}/{{ $dailyTarget['target_hari_ini_kontak'] }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-slate-100"></div>

                        <!-- Follow Up -->
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h4 class="font-bold text-slate-800">Follow Up</h4>
                                <div class="text-[11px] text-slate-500 mt-1">
                                    Target hari ini: <strong class="text-slate-700">{{ $dailyTarget['target_hari_ini_followup'] }}</strong><br>
                                    <span class="text-slate-400">(Sisa Akumulasi Kemarin: {{ $dailyTarget['sisa_akumulasi_followup'] }})</span>
                                </div>
                            </div>
                            @php $pctFollowup = $dailyTarget['target_hari_ini_followup'] > 0 ? min(100, round(($dailyTarget['pencapaian_hari_ini_followup'] / $dailyTarget['target_hari_ini_followup']) * 100)) : 0; @endphp
                            <div class="relative w-14 h-14 shrink-0">
                                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                    <!-- Background (Belum terpenuhi / Abu) -->
                                    <path class="text-slate-200" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3"></path>
                                    <!-- Progress (Terpenuhi / Biru) -->
                                    <path class="text-blue-500 transition-all duration-1000 ease-out" stroke-dasharray="{{ $pctFollowup }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3"></path>
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-xs font-bold text-slate-800">{{ $dailyTarget['pencapaian_hari_ini_followup'] }}/{{ $dailyTarget['target_hari_ini_followup'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visual Pipeline Stages -->
            <div class="crm-card p-6 bg-white lg:col-span-1">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Visual Pipeline Prospek</h3>
                        <p class="text-xs text-slate-500">Pergerakan prospek aktif berdasarkan tahapan inbound</p>
                    </div>
                    <a href="{{ route('pipeline.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                        Board Detail &rarr;
                    </a>
                </div>

                <!-- Pipeline Grid / Stepper cards -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach($pipelineStages as $stage)
                        <div class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/50 hover:bg-white hover:border-blue-300 transition flex flex-col justify-center items-center gap-2 text-center">
                            <x-status-badge :status="$stage['name']" class="scale-90" />
                            <span class="text-2xl font-extrabold text-slate-900">{{ $stage['count'] }}</span>
                        </div>
                    @endforeach
                </div>

                <!-- Lost Note -->
                <div class="mt-4 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-status-badge status="Lost" />
                        <span class="text-slate-600">Arsip prospek yang belum berminat: <strong>{{ $stats['lost'] }} prospek</strong></span>
                    </div>
                    <span class="text-[11px] text-slate-400">Tetap tersimpan di historis</span>
                </div>
            </div>

        </div>

        <!-- RECENT PROSPECTS & RECENT ACTIVITY -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Recent Prospects (Desktop Table vs Mobile Cards) -->
            <div class="crm-card bg-white lg:col-span-2 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Prospek Terbaru</h3>
                        <p class="text-xs text-slate-500">Daftar prospek yang baru ditangani atau diperbarui</p>
                    </div>
                    <a href="{{ route('prospek.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                        Lihat Semua &rarr;
                    </a>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                                <th class="py-3 px-4">Nama Prospek</th>
                                <th class="py-3 px-3">Tipe</th>
                                <th class="py-3 px-3">Status</th>
                                <th class="py-3 px-3">Takeover</th>
                                <th class="py-3 px-3">Last Activity</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($recentProspects as $prospect)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3.5 px-4 font-semibold text-slate-900">
                                        <a href="{{ route('prospek.show', $prospect['id']) }}" class="hover:text-blue-600">
                                            {{ $prospect['name'] }}
                                        </a>
                                        <div class="text-[11px] text-slate-400 font-normal">{{ $prospect['pic'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-3 text-slate-600">
                                        <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium">{{ $prospect['type'] }}</span>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <x-status-badge :status="$prospect['status']" />
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <x-takeover-badge :type="str_contains($prospect['active_takeover'], 'CS') ? 'cs' : 'sales'" :name="explode('—', $prospect['active_takeover'])[1] ?? ''" />
                                    </td>
                                    <td class="py-3.5 px-3 text-slate-500 text-[11px]">
                                        {{ $prospect['last_activity'] }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button 
                                                @click='selectedProspect = @json($prospect); modalFollowUp = true'
                                                class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold text-[11px] transition cursor-pointer"
                                                title="Follow Up"
                                            >
                                                Follow Up
                                            </button>
                                            <a 
                                                href="{{ route('prospek.show', $prospect['id']) }}"
                                                class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
                                                title="Lihat Detail"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card List View -->
                <div class="md:hidden divide-y divide-slate-100">
                    @foreach($recentProspects as $prospect)
                        <div class="p-4 space-y-2.5">
                            <div class="flex items-start justify-between">
                                <div>
                                    <a href="{{ route('prospek.show', $prospect['id']) }}" class="font-bold text-xs text-slate-900 hover:text-blue-600 block">
                                        {{ $prospect['name'] }}
                                    </a>
                                    <span class="text-[11px] text-slate-500">{{ $prospect['type'] }} &bull; {{ $prospect['pic'] }}</span>
                                </div>
                                <x-status-badge :status="$prospect['status']" />
                            </div>

                            <div class="flex items-center justify-between pt-1 text-[11px]">
                                <x-takeover-badge :type="str_contains($prospect['active_takeover'], 'CS') ? 'cs' : 'sales'" :name="explode('—', $prospect['active_takeover'])[1] ?? ''" />
                                <span class="text-slate-400">{{ $prospect['last_activity'] }}</span>
                            </div>

                            <div class="pt-2 flex items-center gap-2">
                                <button 
                                    @click='selectedProspect = @json($prospect); modalFollowUp = true'
                                    class="flex-1 py-1.5 px-3 rounded-lg bg-blue-50 text-blue-700 font-semibold text-xs text-center border border-blue-200/80"
                                >
                                    Follow Up
                                </button>
                                <a 
                                    href="{{ route('prospek.show', $prospect['id']) }}"
                                    class="py-1.5 px-3 rounded-lg bg-slate-100 text-slate-700 font-semibold text-xs text-center hover:bg-slate-200"
                                >
                                    Detail
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Activity Timeline (Compact) — Data Real dari DB -->
            <div class="crm-card p-5 bg-white">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Aktivitas Terbaru</h3>
                    <span class="text-[10px] text-slate-400 font-semibold">Live Log</span>
                </div>

                <div class="space-y-4">
                    @forelse($recentActivity as $activity)
                        @php
                            $colors = ['blue', 'indigo', 'amber', 'teal', 'emerald', 'rose'];
                            $color  = $colors[$loop->index % count($colors)];
                        @endphp
                        <div class="flex items-start gap-3 text-xs">
                            <span class="text-[11px] font-bold text-{{ $color }}-600 shrink-0 w-12 pt-0.5">{{ $activity['time'] }}</span>
                            <div class="border-l-2 border-{{ $color }}-500 pl-3 pb-1">
                                <p class="font-semibold text-slate-800">
                                    {{ $activity['title'] }}
                                    @if($activity['prospek_name'] !== '-')
                                        : <span class="text-{{ $color }}-600">{{ Str::limit($activity['prospek_name'], 30) }}</span>
                                    @endif
                                </p>
                                <p class="text-[11px] text-slate-400 mt-0.5">{{ Str::limit($activity['notes'], 80) }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6">
                            <p class="text-xs text-slate-400">Belum ada aktivitas terbaru.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</x-app-layout>
