@php
    $pageTitle = 'Rekapitulasi Agent PMB';
    $pageSubtitle = 'Pemantauan Performa Referral dan Jalur Afiliasi Pendaftaran PMB';
@endphp

<x-app-layout :title="'Rekapitulasi Agent PMB - CRM UCIC'">
    <div class="space-y-6">

        <!-- ─── 1. KARTU STATISTIK KPI ────────────────────────────────────────── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Total Seluruh Pendaftar -->
            <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs flex items-center gap-4 transition hover:shadow-md">
                <div class="w-13 h-13 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-2xl font-black text-slate-900 leading-none">{{ number_format($totalPendaftar) }}</div>
                    <div class="text-xs font-bold text-slate-600 mt-1">Total Seluruh Pendaftar</div>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                            {{ $totalPendaftar }} pendaftar
                        </span>
                    </div>
                </div>
            </div>

            <!-- Pendaftar via Agent -->
            <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs flex items-center gap-4 transition hover:shadow-md">
                <div class="w-13 h-13 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-2xl font-black text-slate-900 leading-none">{{ number_format($pendaftarViaAgent) }}</div>
                    <div class="text-xs font-bold text-slate-600 mt-1">Pendaftar via Agent</div>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                            {{ $pctViaAgent }}% dari total
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tanpa Agent -->
            <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs flex items-center gap-4 transition hover:shadow-md">
                <div class="w-13 h-13 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-2xl font-black text-slate-900 leading-none">{{ number_format($tanpaAgent) }}</div>
                    <div class="text-xs font-bold text-slate-600 mt-1">Tanpa Agent</div>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200/60">
                            {{ $pctTanpaAgent }}% mandiri
                        </span>
                    </div>
                </div>
            </div>

            <!-- Total Agent Aktif -->
            <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs flex items-center gap-4 transition hover:shadow-md">
                <div class="w-13 h-13 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-2xl font-black text-slate-900 leading-none">{{ number_format($totalAgentAktif) }}</div>
                    <div class="text-xs font-bold text-slate-600 mt-1">Total Agent Aktif</div>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200/60">
                            {{ $totalAgentAktif }} kode agent terdaftar
                        </span>
                    </div>
                </div>
            </div>

        </div>

        <!-- ─── 2. FILTER & PENCARIAN PERIODE ─────────────────────────────────── -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 sm:p-6 shadow-xs space-y-4">
            <form method="GET" action="{{ route('pmb.agent.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3.5 items-end">
                    
                    <!-- Tanggal Mulai -->
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Tanggal Mulai</span>
                        </label>
                        <input type="date" name="start_date" value="{{ $startDate }}"
                               class="w-full text-xs font-medium px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                    </div>

                    <!-- Tanggal Akhir -->
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span>Tanggal Akhir</span>
                        </label>
                        <input type="date" name="end_date" value="{{ $endDate }}"
                               class="w-full text-xs font-medium px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                    </div>

                    <!-- Cari Agent -->
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span>Cari Agent</span>
                        </label>
                        <input type="text" name="search_agent" value="{{ $search }}" placeholder="Cari nama agent..."
                               class="w-full text-xs font-medium px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                    </div>

                    <!-- Action Buttons -->
                    <div class="md:col-span-3 flex items-center gap-2">
                        <button type="submit"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold shadow-xs transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <span>Filter</span>
                        </button>

                        <a href="{{ route('pmb.agent.index') }}"
                           class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition"
                           title="Reset Filter">
                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Reset</span>
                        </a>

                        <a href="{{ route('pmb.agent.export', request()->all()) }}"
                           class="p-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition"
                           title="Ekspor CSV">
                            <svg class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </a>
                    </div>

                </div>
            </form>

            <!-- Bottom Indicator & Link Data Pendaftar -->
            <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2 text-slate-500">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Periode Pendaftar: <strong class="text-slate-800">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</strong> s/d <strong class="text-slate-800">{{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</strong></span>
                    <span class="px-2 py-0.5 rounded-md font-bold text-[10px] bg-slate-600 text-white">
                        {{ $isCurrentMonth ? 'Bulan Berjalan' : 'Rentang Kustom' }}
                    </span>
                </div>

                <a href="{{ route('pmb.pendaftar.index') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl border border-blue-200 bg-blue-50/70 hover:bg-blue-100/70 text-blue-700 font-bold text-xs transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Buka Data Pendaftar</span>
                </a>
            </div>
        </div>

        <!-- ─── 3. TABEL REKAPITULASI PENDAFTAR PER AGENT ──────────────────────── -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
            
            <!-- Table Header -->
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Daftar Rekapitulasi Pendaftar per Agent</h3>
                </div>
                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700">
                    Total Kelompok: {{ $agentGroups->count() }}
                </span>
            </div>

            <!-- Table Body -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/70 text-slate-700 font-bold">
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4 min-w-[240px]">Nama Agent</th>
                            <th class="py-3.5 px-4">Status Tipe</th>
                            <th class="py-3.5 px-4 text-center">Jumlah Pendaftar</th>
                            <th class="py-3.5 px-4 min-w-[200px]">Proporsi</th>
                            <th class="py-3.5 px-4 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($agentGroups as $index => $g)
                            <tr class="hover:bg-slate-50/70 transition">
                                <!-- No -->
                                <td class="py-3.5 px-4 text-center font-semibold text-slate-500">
                                    {{ $index + 1 }}
                                </td>

                                <!-- Nama Agent -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        @if($g->is_tanpa_agent)
                                            <div class="w-9 h-9 rounded-full bg-slate-500 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
                                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-sm">Tanpa Agent</div>
                                                <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                    <span>Pendaftaran reguler langsung tanpa rujukan</span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
                                                {{ strtoupper(substr($g->agent_name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-sm">{{ $g->agent_name }}</div>
                                                <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                                    </svg>
                                                    <span>Rujukan via URL: <code class="text-blue-600 font-semibold">/register/{{ $g->agent_name }}</code></span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Status Tipe -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($g->is_tanpa_agent)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-600 text-white shadow-2xs">
                                            Tanpa Agent
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-600 text-white shadow-2xs">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>Agent Resmi</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- Jumlah Pendaftar -->
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    @if($g->is_tanpa_agent)
                                        <span class="inline-flex items-center px-3.5 py-1.5 rounded-xl text-xs font-bold bg-slate-600 text-white">
                                            {{ $g->total_pendaftar }} Pendaftar
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3.5 py-1.5 rounded-xl text-xs font-bold bg-blue-600 text-white shadow-xs">
                                            {{ $g->total_pendaftar }} Pendaftar
                                        </span>
                                    @endif
                                </td>

                                <!-- Proporsi Progress Bar -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 bg-slate-200/90 rounded-full h-2 overflow-hidden max-w-[140px]">
                                            <div class="h-full rounded-full transition-all duration-500 {{ $g->is_tanpa_agent ? 'bg-slate-600' : 'bg-blue-600' }}"
                                                 style="width: {{ $g->proporsi }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold text-slate-700 min-w-[45px]">{{ $g->proporsi }}%</span>
                                    </div>
                                </td>

                                <!-- Aksi (Lihat Data) -->
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <a href="{{ route('pmb.pendaftar.index', ['agent' => $g->agent_key]) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 text-xs font-bold shadow-2xs transition">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>Lihat Data</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                    </div>
                                    <p class="font-bold text-slate-600 text-xs">Belum ada data pendaftar pada periode ini.</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Silakan pilih rentang tanggal lain atau gunakan tombol reset filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </div>
</x-app-layout>
