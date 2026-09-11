@php
    $pageTitle = 'Laporan Inbound & Rekapitulasi';
    $pageSubtitle = 'Export Data, Analisis Konversi & Rekap Pendaftaran';
@endphp

<x-app-layout :title="'Laporan - CRM UCIC'">

    <!-- State Simulation Wrappers -->
    <div x-show="$store.crm.activeState === 'loading'" x-cloak>
        <x-loading-skeleton type="table" />
    </div>

    <div x-show="$store.crm.activeState === 'empty'" x-cloak>
        <x-empty-state 
            title="Tidak ada data laporan" 
            description="Ubah parameter filter tanggal untuk melihat rekapitulasi data prospek."
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak>
        <x-error-state />
    </div>

    <!-- NORMAL DATA STATE -->
    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Laporan & Rekap Inbound UCIC</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Ekspor rekapitulasi prospek, histori follow up, dan konversi registrasi mahasiswa.</p>
            </div>
            
            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    @click="$store.crm.showToast('File CSV laporan berhasil diunduh!')"
                    class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition flex items-center gap-1.5 cursor-pointer bg-white"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Export CSV</span>
                </button>
                <button 
                    type="button" 
                    @click="window.print()"
                    class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Cetak / Print</span>
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="crm-card bg-white p-4 sm:p-5 flex flex-wrap items-center gap-3 text-xs">
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Periode</label>
                <select class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold">
                    <option value="sep-2026">Bulan September 2026</option>
                    <option value="q3-2026">Kuartal Q3 2026</option>
                    <option value="year-2026">Tahun Akademik 2026/2027</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Filter Sales</label>
                <select class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold">
                    <option value="all">Semua Sales</option>
                    <option value="aurel">Aurel Calista</option>
                    <option value="rizky">Rizky Pratama</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Filter CS</label>
                <select class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold">
                    <option value="all">Semua CS</option>
                    <option value="dina">Dina Marlina</option>
                    <option value="rini">Rini Anggraini</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Status Prospek</label>
                <select class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold">
                    <option value="all">Semua Status</option>
                    <option value="Closing">Closing Only</option>
                    <option value="Lost">Lost Only</option>
                </select>
            </div>
        </div>

        <!-- Summary KPIs -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            <x-stat-card 
                title="Total Prospek" 
                :value="$summary['total_prospek']" 
                subtitle="Periode Dipilih" 
                color="blue"
            />
            <x-stat-card 
                title="Active Lead" 
                :value="$summary['active']" 
                subtitle="Dalam Proses" 
                color="indigo"
            />
            <x-stat-card 
                title="Total Closing" 
                :value="$summary['closing']" 
                subtitle="Mahasiswa Resmi" 
                color="emerald"
            />
            <x-stat-card 
                title="Total Lost" 
                :value="$summary['lost']" 
                subtitle="Historis Arsip" 
                color="rose"
            />
            <x-stat-card 
                title="Conversion Rate" 
                :value="$summary['conversion_rate'] . '%'" 
                subtitle="Rasio Keberhasilan" 
                color="purple"
            />
        </div>

        <!-- Report Data Table -->
        <div class="crm-card bg-white overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Rekap Data Prospek</h3>
                <span class="text-xs text-slate-400 font-medium">Diperbarui: {{ date('d M Y, H:i') }} WIB</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3.5 px-4">Nama Prospek</th>
                            <th class="py-3.5 px-3">Tipe</th>
                            <th class="py-3.5 px-3">PIC Kontak</th>
                            <th class="py-3.5 px-3">Status Terakhir</th>
                            <th class="py-3.5 px-3">Sales Incharge</th>
                            <th class="py-3.5 px-3">CS Incharge</th>
                            <th class="py-3.5 px-4 text-right">Tanggal Masuk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($prospects as $prospect)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    <a href="{{ route('prospek.show', $prospect['id']) }}" class="hover:text-blue-600">
                                        {{ $prospect['name'] }}
                                    </a>
                                </td>
                                <td class="py-3.5 px-3 text-slate-600">{{ $prospect['type'] }}</td>
                                <td class="py-3.5 px-3 text-slate-800">{{ $prospect['pic'] }}</td>
                                <td class="py-3.5 px-3">
                                    <x-status-badge :status="$prospect['status']" />
                                </td>
                                <td class="py-3.5 px-3 text-slate-700 font-medium">{{ $prospect['takeover_sales'] }}</td>
                                <td class="py-3.5 px-3 text-slate-700 font-medium">{{ $prospect['takeover_cs'] }}</td>
                                <td class="py-3.5 px-4 text-right text-slate-500">{{ $prospect['created_at'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</x-app-layout>
