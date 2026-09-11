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
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Halo, Hendra Setiawan, S.Kom 👋</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Supervisor Marketing</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Evaluasi capaian bulanan dan distribusi penugasan tim Sales & Inbound.</p>
            </div>
            
            <!-- Filters Bar -->
            <div class="flex flex-wrap items-center gap-2">
                <select class="text-xs px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:ring-2 focus:ring-blue-500/20">
                    <option value="sep-2026">September 2026</option>
                    <option value="aug-2026">Agustus 2026</option>
                    <option value="jul-2026">Juli 2026</option>
                </select>

                <select class="text-xs px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:ring-2 focus:ring-blue-500/20">
                    <option value="all">Semua Personil Sales</option>
                    <option value="aurel">Aurel Calista</option>
                    <option value="rizky">Rizky Pratama</option>
                    <option value="budi">Budi Santoso</option>
                </select>
            </div>
        </div>

        <!-- SPV Statistic Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
            <x-stat-card 
                title="Total Personil Sales" 
                :value="$stats['total_sales'] . ' Personil'" 
                subtitle="Tim Aktif Lapangan" 
                color="blue"
            />
            <x-stat-card 
                title="Total Prospek Masuk" 
                :value="$stats['total_prospek']" 
                subtitle="Inbound + Kunjungan" 
                trend="+15%"
                :trendUp="true"
                color="indigo"
            />
            <x-stat-card 
                title="Total Follow Up Tim" 
                :value="$stats['total_follow_up']" 
                subtitle="Interaksi tercatat" 
                color="amber"
            />
            <x-stat-card 
                title="Total Closing" 
                :value="$stats['total_closing']" 
                subtitle="Mahasiswa resmi" 
                trend="+22%"
                :trendUp="true"
                color="emerald"
            />
            <x-stat-card 
                title="Total Lost" 
                :value="$stats['total_lost']" 
                subtitle="Arsip prospek" 
                color="rose"
            />
        </div>

        <!-- Section: PERFORMANCE TIM -->
        <div class="crm-card bg-white p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 border-b border-slate-100 mb-5">
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">Performance Tim Sales (September 2026)</h3>
                    <p class="text-xs text-slate-500">Evaluasi performa berkala untuk mendukung pembinaan dan akselerasi closing</p>
                </div>
                <div class="text-xs text-slate-400 font-medium italic">
                    * Evaluasi berbasis capaian periode berjalan (Bukan punishment harian)
                </div>
            </div>

            <!-- Team Performance Table (Desktop) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3 px-4">Peringkat & Sales</th>
                            <th class="py-3 px-3 text-center">Target</th>
                            <th class="py-3 px-3 text-center">Prospek</th>
                            <th class="py-3 px-3 text-center">Follow Up</th>
                            <th class="py-3 px-3 text-center">Closing</th>
                            <th class="py-3 px-3 text-center">Lost</th>
                            <th class="py-3 px-4 text-center w-48">Achievement Progress</th>
                            <th class="py-3 px-4 text-right">Status Evaluasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($team as $member)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    <div class="flex items-center gap-3">
                                        <span class="w-6 h-6 rounded-full {{ $loop->first ? 'bg-amber-100 text-amber-800 font-bold' : 'bg-slate-100 text-slate-600' }} flex items-center justify-center text-xs">
                                            #{{ $member['rank'] }}
                                        </span>
                                        <div class="w-8 h-8 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-xs">
                                            {{ $member['avatar'] }}
                                        </div>
                                        <div>
                                            <div class="text-slate-900 font-bold">{{ $member['name'] }}</div>
                                            <div class="text-[11px] text-slate-400 font-normal">{{ $member['role'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center font-semibold text-slate-700">{{ $member['target'] }}</td>
                                <td class="py-3.5 px-3 text-center text-slate-700 font-medium">{{ $member['prospects'] }}</td>
                                <td class="py-3.5 px-3 text-center text-amber-700 font-semibold">{{ $member['follow_up'] }}</td>
                                <td class="py-3.5 px-3 text-center text-emerald-700 font-bold text-sm">{{ $member['closing'] }}</td>
                                <td class="py-3.5 px-3 text-center text-rose-600 font-medium">{{ $member['lost'] }}</td>
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        <div class="flex justify-between text-[11px] font-semibold text-slate-700">
                                            <span>{{ $member['achievement'] }}%</span>
                                            <span class="text-slate-400">{{ $member['closing'] }}/{{ $member['target'] }}</span>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 rounded-full {{ $member['achievement'] >= 70 ? 'bg-emerald-500' : ($member['achievement'] >= 50 ? 'bg-blue-500' : 'bg-amber-500') }}" style="width: {{ $member['achievement'] }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $member['achievement'] >= 70 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($member['achievement'] >= 50 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                        {{ $member['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards List -->
            <div class="md:hidden divide-y divide-slate-100">
                @foreach($team as $member)
                    <div class="py-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold flex items-center justify-center text-xs">
                                    #{{ $member['rank'] }}
                                </span>
                                <div>
                                    <h4 class="font-bold text-xs text-slate-900">{{ $member['name'] }}</h4>
                                    <span class="text-[11px] text-slate-400">{{ $member['role'] }}</span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                {{ $member['status'] }}
                            </span>
                        </div>

                        <div class="grid grid-cols-4 gap-2 text-center text-xs py-2 bg-slate-50/70 rounded-xl">
                            <div>
                                <span class="text-slate-400 text-[10px] block">Target</span>
                                <span class="font-semibold text-slate-800">{{ $member['target'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] block">Follow Up</span>
                                <span class="font-semibold text-amber-700">{{ $member['follow_up'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] block">Closing</span>
                                <span class="font-bold text-emerald-600">{{ $member['closing'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] block">Lost</span>
                                <span class="font-semibold text-rose-600">{{ $member['lost'] }}</span>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <div class="flex justify-between text-[11px] font-semibold text-slate-700">
                                <span>Capaian: {{ $member['achievement'] }}%</span>
                                <span class="text-slate-400">{{ $member['closing'] }}/{{ $member['target'] }} Closing</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2">
                                <div class="h-2 rounded-full bg-blue-600" style="width: {{ $member['achievement'] }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

</x-app-layout>
