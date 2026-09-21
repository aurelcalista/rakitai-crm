@php
    $pageTitle = 'Target & Performa Tim (SPV)';
    $pageSubtitle = 'Evaluasi Pencapaian & Leaderboard Sales';
@endphp

<x-app-layout :title="'Target & Performa Tim - Supervisor CRM'">

    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Evaluasi Target & Performa Tim</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Pengawasan SPV</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Pantau target closing, rasio pencapaian, dan leaderboard seluruh personil Sales dalam tim Anda.</p>
            </div>
        </div>

        <!-- Big Progress Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <x-stat-card 
                title="Target Tim Total" 
                :value="$summary['target'] . ' Closing'" 
                subtitle="Target Periode Ini" 
                color="blue"
            />
            <x-stat-card 
                title="Realisasi Closing" 
                :value="$summary['realisasi'] . ' Mhs'" 
                subtitle="Mahasiswa Terdaftar" 
                color="emerald"
            />
            <x-stat-card 
                title="Rasio Capaian Tim" 
                :value="$summary['achievement'] . '%'" 
                subtitle="Capaian Terhadap Target" 
                color="indigo"
            />
            <x-stat-card 
                title="Sisa Target" 
                :value="$summary['sisa_target'] . ' Mhs'" 
                subtitle="Perlu Dicapai" 
                color="amber"
            />
        </div>

        <!-- Team Breakdown Table -->
        <div class="crm-card bg-white p-6">
            <div class="pb-4 border-b border-slate-100 mb-5">
                <h3 class="text-base font-bold text-slate-900">Leaderboard & Rekapitulasi Personil Sales</h3>
                <p class="text-xs text-slate-500">Rincian aktivitas prospek, follow up, dan konversi closing per individu</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-4">Sales</th>
                            <th class="py-3 px-3 text-center">Target</th>
                            <th class="py-3 px-3 text-center">Prospek</th>
                            <th class="py-3 px-3 text-center">Follow Up</th>
                            <th class="py-3 px-3 text-center">Closing</th>
                            <th class="py-3 px-4 text-center w-52">Capaian Target (%)</th>
                            <th class="py-3 px-4 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($team as $member)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-xs">
                                            {{ $member['avatar'] }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900">{{ $member['name'] }}</div>
                                            <div class="text-[11px] text-slate-400 font-normal">{{ $member['role'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-700">{{ $member['target'] }}</td>
                                <td class="py-3.5 px-3 text-center text-slate-700 font-medium">{{ $member['prospects'] }}</td>
                                <td class="py-3.5 px-3 text-center text-amber-700 font-semibold">{{ $member['follow_up'] }}</td>
                                <td class="py-3.5 px-3 text-center text-emerald-700 font-bold text-sm">{{ $member['closing'] }}</td>
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        <div class="flex justify-between text-[11px] font-semibold text-slate-700">
                                            <span>{{ $member['achievement'] }}%</span>
                                            <span class="text-slate-400">{{ $member['closing'] }}/{{ $member['target'] }}</span>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 rounded-full {{ $member['achievement'] >= 70 ? 'bg-emerald-500' : ($member['achievement'] >= 40 ? 'bg-blue-500' : 'bg-amber-500') }}" style="width: {{ min(100, $member['achievement']) }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $member['achievement'] >= 70 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($member['achievement'] >= 40 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                        {{ $member['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400 text-xs">Belum ada data anggota Sales dalam tim.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</x-app-layout>
