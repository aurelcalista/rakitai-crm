@php
    $pageTitle = 'Laporan Rekapitulasi Tim (SPV)';
    $pageSubtitle = 'Analisis Konversi & Ekspor Data Prospek Tim Sales';
@endphp

<x-app-layout :title="'Laporan Tim - Supervisor CRM'">

    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center gap-3.5 sm:gap-4">
                <div class="h-12 w-14 sm:h-14 sm:w-16 rounded-2xl bg-indigo-50/80 p-2 border border-indigo-100/80 shrink-0 flex items-center justify-center shadow-xs">
                    <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" class="w-full h-full object-contain">
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Laporan Rekapitulasi Tim Sales</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Pengawasan SPV</span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Ekspor data prospek tim, analisis konversi pendaftaran, dan evaluasi hasil penugasan.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    onclick="window.print()"
                    class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Cetak / PDF</span>
                </button>
            </div>
        </div>

        <!-- Metric Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <x-stat-card 
                title="Total Prospek Masuk" 
                :value="$summary['total_prospek']" 
                subtitle="Data Keseluruhan Tim" 
                color="blue"
            />
            <x-stat-card 
                title="Prospek Aktif / On Progress" 
                :value="$summary['active']" 
                subtitle="Dalam Tahap Follow Up" 
                color="amber"
            />
            <x-stat-card 
                title="Total Closing" 
                :value="$summary['closing']" 
                subtitle="Mahasiswa Terdaftar" 
                color="emerald"
            />
            <x-stat-card 
                title="Konversi Closing Tim" 
                :value="$summary['conversion_rate'] . '%'" 
                subtitle="Rasio Closing Tim" 
                color="indigo"
            />
        </div>

        <!-- Filter Form -->
        <div class="crm-card bg-white p-4 sm:p-5">
            <form action="{{ route('spv.laporan.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Sales Personil</label>
                    <select name="sales_id" class="w-full text-xs px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold">
                        <option value="all">Semua Sales</option>
                        @foreach($teamSales as $s)
                            <option value="{{ $s->id }}" {{ request('sales_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Status Prospek</label>
                    <select name="status" class="w-full text-xs px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold">
                        <option value="all">Semua Status</option>
                        @foreach(\App\Models\Prospek::ACTIVE_STAGES as $st)
                            <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Dari Tanggal</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full text-xs px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold transition">
                        Filter Laporan
                    </button>
                    <a href="{{ route('spv.laporan.index') }}" class="py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition text-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Table Laporan -->
        <div class="crm-card bg-white p-6">
            <div class="pb-4 border-b border-slate-100 mb-5 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Data Rekapitulasi Prospek Tim</h3>
                    <p class="text-xs text-slate-500">Total data ditampilkan: {{ count($prospects) }} baris</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-3">Tanggal</th>
                            <th class="py-3 px-4">Nama Prospek / Sekolah</th>
                            <th class="py-3 px-3">PIC & Kontak</th>
                            <th class="py-3 px-3">Sales Handler</th>
                            <th class="py-3 px-3 text-center">Status</th>
                            <th class="py-3 px-3">Follow Up Terakhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($prospects as $p)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-3 text-slate-500 whitespace-nowrap">{{ $p['created_at'] }}</td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $p['name'] }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $p['type'] }} &bull; {{ $p['source'] }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-medium text-slate-800">{{ $p['pic'] }}</div>
                                    <div class="text-[11px] text-emerald-600 font-semibold">{{ $p['whatsapp'] }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-semibold text-slate-700">{{ $p['sales_name'] }}</div>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <x-status-badge :status="$p['status']" />
                                </td>
                                <td class="py-3 px-3 text-slate-600">
                                    {{ $p['last_contact'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-slate-400 text-xs">Tidak ada data laporan untuk filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</x-app-layout>
