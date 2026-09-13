@php
    $pageTitle = 'Target & Performa';
    $pageSubtitle = 'Capaian Registrasi & Evaluasi Penjualan Inbound';
@endphp

<x-app-layout :title="'Target & Performa - CRM UCIC'">

    <!-- State Simulation Wrappers -->
    <div x-show="$store.crm.activeState === 'loading'" x-cloak>
        <x-loading-skeleton type="cards" />
    </div>

    <div x-show="$store.crm.activeState === 'empty'" x-cloak>
        <x-empty-state 
            title="Data target belum diatur" 
            description="Target periode ini belum dikonfigurasi oleh Head Marketing."
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak>
        <x-error-state />
    </div>

    <!-- NORMAL DATA STATE -->
    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6">

        <!-- Header with Month Filter -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Evaluasi Target & Performa</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Pantau target institusi dan efektivitas closing per individu tim marketing.</p>
            </div>
            
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-500">Pilih Periode:</span>
                <select class="text-xs px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-bold focus:ring-2 focus:ring-blue-500/20">
                    <option value="sep-2026">September 2026 (Semester Ganjil)</option>
                    <option value="aug-2026">Agustus 2026</option>
                    <option value="jul-2026">Juli 2026</option>
                </select>
            </div>
        </div>

        <!-- Big Progress Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <x-stat-card 
                title="Target Kuota Mahasiswa" 
                :value="$summary['target'] . ' Prospek'" 
                subtitle="Target Total September" 
                color="blue"
            />
            <x-stat-card 
                title="Realisasi Closing" 
                :value="$summary['realisasi'] . ' Mhs'" 
                subtitle="Terdaftar & Bayar" 
                trend="+18%"
                :trendUp="true"
                color="emerald"
            />
            <x-stat-card 
                title="Pencapaian (Achievement)" 
                :value="$summary['achievement'] . '%'" 
                subtitle="Progress Terhadap Target" 
                color="indigo"
            />
            <x-stat-card 
                title="Sisa Target" 
                :value="$summary['sisa_target'] . ' Prospek'" 
                subtitle="Perlu Dicapai" 
                color="amber"
            />
        </div>

        <!-- Big Progress Visualization Bar -->
        <div class="crm-card bg-white p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Pencapaian Target Kampus (September 2026)</h3>
                    <p class="text-xs text-slate-500">Realisasi 104 dari total target 180 mahasiswa baru jalur inbound</p>
                </div>
                <span class="text-sm font-extrabold text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-100">
                    58% Tercapai
                </span>
            </div>

            <div class="w-full bg-slate-100 rounded-full h-4 overflow-hidden p-0.5 border border-slate-200/60">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 h-3 rounded-full transition-all duration-500" style="width: 58%"></div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2 text-xs text-slate-500 border-t border-slate-100">
                <div><span class="text-slate-400">Jalur Beasiswa AI:</span> <strong class="text-slate-800">48 Siswa</strong></div>
                <div><span class="text-slate-400">Jalur PMDK Sekolah:</span> <strong class="text-slate-800">32 Siswa</strong></div>
                <div><span class="text-slate-400">Kelas Karyawan S1/S2:</span> <strong class="text-slate-800">18 Peserta</strong></div>
                <div><span class="text-slate-400">Reguler Mandiri:</span> <strong class="text-slate-800">6 Siswa</strong></div>
            </div>
        </div>

        <!-- Table Performance Per Sales -->
        <div class="crm-card bg-white p-6">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 mb-4">
                Rincian Performa Sales Inbound
            </h3>

            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3 px-4">Sales Representative</th>
                            <th class="py-3 px-3 text-center">Target</th>
                            <th class="py-3 px-3 text-center">Total Prospek</th>
                            <th class="py-3 px-3 text-center">Follow Up</th>
                            <th class="py-3 px-3 text-center">Closing</th>
                            <th class="py-3 px-3 text-center">Lost</th>
                            <th class="py-3 px-4 text-center w-40">Pencapaian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($team as $member)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-semibold text-slate-900 flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-xs">
                                        {{ $member['avatar'] }}
                                    </div>
                                    <div>
                                        <div>{{ $member['name'] }}</div>
                                        <div class="text-[11px] text-slate-400 font-normal">{{ $member['role'] }}</div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-800">{{ $member['target'] }}</td>
                                <td class="py-3.5 px-3 text-center text-slate-700 font-medium">{{ $member['prospects'] }}</td>
                                <td class="py-3.5 px-3 text-center text-amber-700 font-semibold">{{ $member['follow_up'] }}</td>
                                <td class="py-3.5 px-3 text-center text-emerald-700 font-bold text-sm">{{ $member['closing'] }}</td>
                                <td class="py-3.5 px-3 text-center text-rose-600 font-medium">{{ $member['lost'] }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold {{ $member['achievement'] >= 70 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                                        {{ $member['achievement'] }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden divide-y divide-slate-100">
                @foreach($team as $member)
                    <div class="py-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <h4 class="font-bold text-xs text-slate-900">{{ $member['name'] }}</h4>
                            <span class="text-xs font-bold text-blue-600">{{ $member['achievement'] }}% Target</span>
                        </div>
                        <div class="flex justify-between text-xs text-slate-600">
                            <span>Target: <strong>{{ $member['target'] }}</strong></span>
                            <span>Closing: <strong class="text-emerald-600">{{ $member['closing'] }}</strong></span>
                            <span>Lost: <strong class="text-rose-600">{{ $member['lost'] }}</strong></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

</x-app-layout>
