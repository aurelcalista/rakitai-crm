@php
    $pageTitle = 'Dashboard Supervisor (SPV)';
    $pageSubtitle = 'Monitoring Performa Tim Sales & Evaluasi Pipeline';
@endphp

<x-app-layout :title="'Dashboard SPV - CRM UCIC'">

    <!-- State Simulation Wrappers -->
    <div x-show="$store.crm.activeState === 'loading'" x-cloak>
        <x-loading-skeleton type="cards" />
        <div class="mt-6">
            <x-loading-skeleton type="table" />
        </div>
    </div>

    <div x-show="$store.crm.activeState === 'empty'" x-cloak>
        <x-empty-state 
            title="Data performa tim belum tersedia" 
            description="Pilih filter bulan atau tambahkan aktivitas sales untuk memulai evaluasi tim."
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak>
        <x-error-state />
    </div>

    <!-- NORMAL DATA STATE -->
    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6">

        <!-- Header Greeting & Filters -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Halo, {{ auth()->user()->name ?? 'Supervisor' }} 👋</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Supervisor Marketing</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">TA {{ $activeTa }}</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Monitoring performa seluruh tim Sales & CS, alokasi target wilayah, dan pipeline konversi.</p>
            </div>
            
            <!-- Quick Link Actions -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('spv.performa.index') }}" class="px-3.5 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-xs transition flex items-center gap-1.5 border border-indigo-200">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>Target & Whiteboard Tracker</span>
                </a>
                <a href="{{ route('spv.prospek.create') }}" class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition shadow-xs flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Tambah Prospek Tim</span>
                </a>
            </div>
        </div>

        <!-- SPV Statistic Cards P0 Focus -->
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <x-stat-card 
                title="Tim Sales & CS" 
                :value="$stats['total_sales'] . ' Sales • ' . $stats['total_cs'] . ' CS'" 
                subtitle="Personil Wilayah Aktif" 
                color="blue"
            />
            <x-stat-card 
                title="Prospek & Follow-up" 
                :value="$stats['total_prospek'] . ' / ' . $stats['total_follow_up']" 
                subtitle="Prospek Tim / Aktivitas FU" 
                color="indigo"
            />
            <x-stat-card 
                title="Closing & Lost/Dingin" 
                :value="$stats['closing_count'] . ' Lunas • ' . $stats['lost_count'] . ' Dingin'" 
                subtitle="Maba Lunas vs Lost" 
                color="emerald"
            />
            <x-stat-card 
                title="Target HM & Sisa Target" 
                :value="$stats['realisasi_tim'] . '/' . $stats['target_tim'] . ' (' . $stats['persentase_tim'] . '%)'" 
                :subtitle="'Sisa Target: ' . $stats['sisa_target'] . ' Mhs'" 
                color="amber"
            />
        </div>

        <!-- Section: TARGET SPV DARI HEAD MANAGER (HM) — READ ONLY -->
        <div class="crm-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/90 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-bold tracking-tight text-slate-900">Target SPV dari Head Manager (HM)</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 uppercase tracking-wider">Read-Only</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">TA {{ $activeTa }}</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Pagu maksimal alokasi target dari HM untuk wilayah binaan SPV. Tidak dapat diubah oleh SPV.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                        Sumber: {{ $targetHm['sumber'] ?? ($targetHm['has_hm_target'] ? 'Target Resmi HM (DB)' : 'Default P0 (Belum dialokasikan HM)') }}
                    </span>
                    <a href="{{ route('spv.performa.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white transition shadow-xs flex items-center gap-1">
                        <span>Alokasikan ke Tim</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
                <!-- Target Kontak SPV -->
                <div class="bg-slate-50/80 rounded-xl p-4 border border-slate-200/80 space-y-2">
                    <div class="flex items-center justify-between text-xs text-slate-500 font-semibold">
                        <span>Target Kontak Masuk</span>
                        <span class="text-slate-700 font-bold">{{ $targetHm['target_kontak'] }} Kontak</span>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight">{{ $targetHm['target_kontak'] }}</div>
                    <div class="flex items-center justify-between text-[11px] pt-2 border-t border-slate-200/80">
                        <span class="text-slate-500">Teralokasi ke Tim: <strong class="text-slate-800">{{ $targetHm['allocated_kontak'] }}</strong></span>
                        <span class="text-indigo-700 font-bold bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100">Sisa Pagu: {{ $targetHm['sisa_kontak'] }}</span>
                    </div>
                </div>

                <!-- Target Formulir SPV -->
                <div class="bg-slate-50/80 rounded-xl p-4 border border-slate-200/80 space-y-2">
                    <div class="flex items-center justify-between text-xs text-slate-500 font-semibold">
                        <span>Target Formulir</span>
                        <span class="text-slate-700 font-bold">{{ $targetHm['target_formulir'] }} Form</span>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight">{{ $targetHm['target_formulir'] }}</div>
                    <div class="flex items-center justify-between text-[11px] pt-2 border-t border-slate-200/80">
                        <span class="text-slate-500">Teralokasi ke Tim: <strong class="text-slate-800">{{ $targetHm['allocated_formulir'] }}</strong></span>
                        <span class="text-indigo-700 font-bold bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100">Sisa Pagu: {{ $targetHm['sisa_formulir'] }}</span>
                    </div>
                </div>

                <!-- Target Maba Lunas SPV -->
                <div class="bg-emerald-50/60 rounded-xl p-4 border border-emerald-200/90 space-y-2">
                    <div class="flex items-center justify-between text-xs text-emerald-800 font-semibold">
                        <span>Target Maba Lunas (Closing)</span>
                        <span class="text-emerald-700 font-bold">{{ $targetHm['realisasi_lunas'] }} / {{ $targetHm['target_lunas'] }} Mhs</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-black text-emerald-950 tracking-tight">{{ $targetHm['target_lunas'] }}</span>
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md">({{ $targetHm['achieve_pct'] }}% Tercapai)</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] pt-2 border-t border-emerald-200/80">
                        <span class="text-slate-600">Teralokasi ke Tim: <strong class="text-slate-800">{{ $targetHm['allocated_lunas'] }}</strong></span>
                        <span class="text-emerald-800 font-bold bg-white px-2 py-0.5 rounded-md border border-emerald-200">Sisa Target: {{ $targetHm['sisa_lunas'] }} Mhs</span>
                    </div>
                </div>
            </div>
        </div>

        @php
            $displayTeam = isset($teamPerformance) ? $teamPerformance->map(function($t, $idx) {
                $target = (int) ($t['target'] ?? 0);
                $achieved = (float) ($t['achieved_pct'] ?? 0);

                if ($target <= 0) {
                    $statusLabel = 'Belum Ada Target';
                    $statusClass = 'bg-slate-50 text-slate-600 border-slate-200';
                    $dotClass = 'bg-slate-400';
                } elseif ($achieved >= 100) {
                    $statusLabel = 'Target Tercapai';
                    $statusClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    $dotClass = 'bg-emerald-500';
                } elseif ($achieved >= 50) {
                    $statusLabel = 'On Track';
                    $statusClass = 'bg-blue-50 text-blue-700 border-blue-200';
                    $dotClass = 'bg-blue-500';
                } else {
                    $statusLabel = 'Perlu Akselerasi';
                    $statusClass = 'bg-amber-50 text-amber-700 border-amber-200';
                    $dotClass = 'bg-amber-500';
                }

                return [
                    'rank' => $idx + 1,
                    'name' => $t['user']->name,
                    'role' => $t['role'] ?? $t['user']->role,
                    'avatar' => strtoupper(substr($t['user']->name, 0, 2)),
                    'target' => $t['target'],
                    'prospects' => $t['prospects'],
                    'follow_up' => $t['visits'],
                    'closing' => $t['closing'],
                    'lost' => 0,
                    'achievement' => $t['achieved_pct'],
                    'wilayah' => $t['wilayah_nama'] ?? ($t['user']->role === 'CS' ? 'Centralized' : ($t['user']->wilayah?->nama ?? 'Belum Ditugaskan')),
                    'status_label' => $statusLabel,
                    'status_class' => $statusClass,
                    'dot_class'    => $dotClass,
                ];
            })->toArray() : ($team ?? []);
        @endphp

        <!-- Section: PERFORMANCE TIM -->
        <div class="crm-card bg-white p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 border-b border-slate-100 mb-5">
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">Monitoring & Performance Tim (Sales & CS)</h3>
                    <p class="text-xs text-slate-500">Evaluasi performa berkala untuk mendukung pembinaan dan akselerasi closing target wilayah SPV</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('spv.tim.index') }}" class="text-xs text-slate-600 hover:text-slate-800 font-semibold flex items-center gap-1">
                        <span>Direktori Tim</span>
                    </a>
                    <span class="text-slate-300">•</span>
                    <a href="{{ route('spv.performa.index') }}" class="text-xs text-blue-600 hover:text-blue-700 font-semibold flex items-center gap-1">
                        <span>Whiteboard Tracker</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            <!-- Team Performance Table (Desktop) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3 px-4">Peringkat & Anggota Tim</th>
                            <th class="py-3 px-3">Role & Penugasan Wilayah</th>
                            <th class="py-3 px-3 text-center">Target</th>
                            <th class="py-3 px-3 text-center">Prospek</th>
                            <th class="py-3 px-3 text-center">Kunjungan / FU</th>
                            <th class="py-3 px-3 text-center">Closing</th>
                            <th class="py-3 px-4 text-center w-44">Achievement Progress</th>
                            <th class="py-3 px-4 text-center w-44">Status Evaluasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($displayTeam as $member)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    <div class="flex items-center gap-3">
                                        <span class="w-6 h-6 rounded-full {{ ($member['rank'] ?? 1) == 1 ? 'bg-amber-100 text-amber-800 font-bold' : 'bg-slate-100 text-slate-600' }} flex items-center justify-center text-xs">
                                            #{{ $member['rank'] ?? ($loop->iteration) }}
                                        </span>
                                        <div class="w-8 h-8 rounded-full {{ $member['role'] === 'CS' ? 'bg-amber-600' : 'bg-blue-600' }} text-white font-bold flex items-center justify-center text-xs">
                                            {{ $member['avatar'] ?? 'TM' }}
                                        </div>
                                        <div>
                                            <div class="text-slate-900 font-bold">{{ $member['name'] }}</div>
                                            <div class="text-[11px] text-slate-400 font-normal">ID: #{{ $member['rank'] ?? $loop->iteration }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <div class="space-y-1">
                                        @if($member['role'] === 'CS')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                Customer Service
                                            </span>
                                            <div class="text-[11px] text-slate-500 font-medium">🏢 Centralized (Tanpa Wilayah)</div>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                Sales Wilayah
                                            </span>
                                            <div class="text-[11px] text-slate-600 font-medium flex items-center gap-1">
                                                <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                <span>{{ $member['wilayah'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center font-semibold text-slate-700">{{ $member['target'] }}</td>
                                <td class="py-3.5 px-3 text-center text-slate-700 font-medium">{{ $member['prospects'] }}</td>
                                <td class="py-3.5 px-3 text-center text-amber-700 font-semibold">
                                    {{ $member['role'] === 'CS' ? '-' : $member['follow_up'] }}
                                </td>
                                <td class="py-3.5 px-3 text-center text-emerald-700 font-bold text-sm">{{ $member['closing'] }}</td>
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        <div class="flex justify-between text-[11px] font-semibold text-slate-700">
                                            <span>{{ $member['achievement'] }}%</span>
                                            <span class="text-slate-400">{{ $member['closing'] }}/{{ $member['target'] }}</span>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 rounded-full {{ $member['achievement'] >= 70 ? 'bg-emerald-500' : ($member['achievement'] >= 50 ? 'bg-blue-500' : 'bg-amber-500') }}" style="width: {{ min(100, $member['achievement']) }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold whitespace-nowrap border {{ $member['status_class'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $member['dot_class'] }}"></span>
                                        <span>{{ $member['status_label'] }}</span>
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400 text-xs">Belum ada data anggota tim dalam naungan SPV.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards List -->
            <div class="md:hidden divide-y divide-slate-100">
                @forelse($displayTeam as $member)
                    <div class="py-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold flex items-center justify-center text-xs">
                                    #{{ $member['rank'] ?? $loop->iteration }}
                                </span>
                                <div>
                                    <h4 class="font-bold text-xs text-slate-900">{{ $member['name'] }}</h4>
                                    <span class="text-[11px] text-slate-400">{{ $member['role'] }} • {{ $member['wilayah'] }}</span>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold whitespace-nowrap border {{ $member['status_class'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $member['dot_class'] }}"></span>
                                <span>{{ $member['status_label'] }}</span>
                            </span>
                        </div>

                        <div class="grid grid-cols-4 gap-2 text-center text-xs py-2 bg-slate-50/70 rounded-xl">
                            <div>
                                <span class="text-slate-400 text-[10px] block">Target</span>
                                <span class="font-semibold text-slate-800">{{ $member['target'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] block">Prospek</span>
                                <span class="font-semibold text-slate-700">{{ $member['prospects'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] block">Closing</span>
                                <span class="font-bold text-emerald-600">{{ $member['closing'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] block">Capaian</span>
                                <span class="font-semibold text-blue-600">{{ $member['achievement'] }}%</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-slate-400 text-xs">Belum ada data anggota tim dalam naungan SPV.</div>
                @endforelse
        </div>

    </div>

</x-app-layout>

