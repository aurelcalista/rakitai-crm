@php
    $pageTitle = 'Laporan Inbound & Rekapitulasi';
    $pageSubtitle = 'Export Data, Analisis Konversi & Rekap Pendaftaran';
@endphp

<x-app-layout :title="'Laporan - CRM UCIC'">

    <!-- State Simulation Wrappers -->
    <div x-show="$store.crm.activeState === 'loading'" x-cloak class="no-print">
        <x-loading-skeleton type="table" />
    </div>

    <div x-show="$store.crm.activeState === 'empty'" x-cloak class="no-print">
        <x-empty-state 
            title="Tidak ada data laporan" 
            description="Ubah parameter filter tanggal untuk melihat rekapitulasi data prospek."
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak class="no-print">
        <x-error-state />
    </div>

    <!-- ============================================================== -->
    <!-- PRINT-ONLY: KOP SURAT RESMI UNIVERSITAS CATUR INSAN CENDEKIA   -->
    <!-- ============================================================== -->
    <div class="print-only mb-6">
        <div class="flex items-center justify-between pb-3 border-b-[3px] border-slate-900">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-xl bg-blue-900 text-white flex items-center justify-center font-extrabold text-2xl border-2 border-slate-900">
                    UCIC
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight leading-tight uppercase">Universitas Catur Insan Cendekia</h1>
                    <p class="text-[11px] font-semibold text-slate-700 uppercase tracking-wider">Direktorat Marketing, Admisi & Komunikasi Publik</p>
                    <p class="text-[10px] text-slate-600 mt-0.5">Jl. Kesambi No. 202, Kec. Kesambi, Kota Cirebon, Jawa Barat 45134 | Telp: (0231) 200418</p>
                    <p class="text-[9.5px] text-slate-500">Website: www.cic.ac.id • Email: pmb@cic.ac.id • Hotline CRM: +62 811-2004-180</p>
                </div>
            </div>
            <div class="text-right text-[10px] text-slate-600">
                <div class="font-bold uppercase text-slate-900 text-xs">Form: UCIC-CRM-LAP-01</div>
                <div>Status: Dokumen Resmi Terverifikasi</div>
                <div>Tanggal: {{ date('d F Y') }}</div>
            </div>
        </div>
        <div class="border-b border-slate-800 mt-[2px]"></div>

        <!-- Document Title -->
        <div class="text-center my-4">
            <h2 class="text-base font-extrabold uppercase tracking-wide text-slate-900 underline decoration-slate-900 underline-offset-4">
                Laporan Rekapitulasi Prospek & Inbound Marketing
            </h2>
            <p class="text-xs text-slate-600 mt-1">Periode Laporan: <strong>Bulan September 2026</strong> (Tahun Akademik 2026/2027)</p>
        </div>

        <!-- Document Metadata Box -->
        <div class="grid grid-cols-3 gap-2 p-3 bg-slate-50 border border-slate-300 rounded-lg text-[10.5px] mb-4">
            <div>
                <span class="text-slate-500">Unit Kerja:</span>
                <span class="font-bold text-slate-800 ml-1">Marketing & Inbound Sales</span>
            </div>
            <div>
                <span class="text-slate-500">Dicetak Oleh:</span>
                <span class="font-bold text-slate-800 ml-1">{{ auth()->user()->name ?? 'Supervisor' }} ({{ auth()->user()->role ?? 'SPV' }})</span>
            </div>
            <div class="text-right">
                <span class="text-slate-500">Waktu Cetak:</span>
                <span class="font-bold text-slate-800 ml-1">{{ date('d M Y, H:i') }} WIB</span>
            </div>
        </div>
    </div>

    <!-- NORMAL DATA STATE -->
    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6">

        <!-- Screen Header (Hidden on Print) -->
        <div class="no-print flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
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
                    class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 active:bg-black text-white text-xs font-bold shadow-xs transition flex items-center gap-2 cursor-pointer"
                >
                    <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Cetak / Cetak PDF</span>
                </button>
            </div>
        </div>

        <!-- Filter Bar (Hidden on Print) -->
        <div class="no-print crm-card bg-white p-4 sm:p-5 flex flex-wrap items-center gap-3 text-xs">
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Periode</label>
                <select class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="sep-2026">Bulan September 2026</option>
                    <option value="q3-2026">Kuartal Q3 2026</option>
                    <option value="year-2026">Tahun Akademik 2026/2027</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Filter Sales</label>
                <select class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="all">Semua Sales</option>
                    <option value="aurel">Aurel Calista</option>
                    <option value="rizky">Rizky Pratama</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Filter CS</label>
                <select class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="all">Semua CS</option>
                    <option value="dina">Dina Marlina</option>
                    <option value="rini">Rini Anggraini</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Status Prospek</label>
                <select class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="all">Semua Status</option>
                    <option value="Closing">Closing Only</option>
                    <option value="Lost">Lost Only</option>
                </select>
            </div>
        </div>

        <!-- Summary KPIs (Screen View) -->
        <div class="no-print grid grid-cols-2 sm:grid-cols-5 gap-4">
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
                :value="$summary['closing'] ?? $summary['LUNAS'] ?? 0" 
                subtitle="Mahasiswa Resmi" 
                color="emerald"
            />
            <x-stat-card 
                title="Total Lost" 
                :value="$summary['lost'] ?? $summary['DINGIN'] ?? 0" 
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

        <!-- Summary KPIs (Print View: Clean & Structured Table) -->
        <div class="print-only mb-4">
            <table class="w-full text-center border-collapse border border-slate-400 text-xs">
                <thead>
                    <tr class="bg-slate-100 text-slate-800 font-bold uppercase">
                        <th class="py-2 px-2 border border-slate-400">Total Prospek</th>
                        <th class="py-2 px-2 border border-slate-400">Active Lead</th>
                        <th class="py-2 px-2 border border-slate-400">Total Closing</th>
                        <th class="py-2 px-2 border border-slate-400">Total Lost</th>
                        <th class="py-2 px-2 border border-slate-400">Conversion Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="font-extrabold text-sm text-slate-900 bg-white">
                        <td class="py-2.5 px-2 border border-slate-400">{{ $summary['total_prospek'] }} Prospek</td>
                        <td class="py-2.5 px-2 border border-slate-400 text-blue-700">{{ $summary['active'] }} Lead</td>
                        <td class="py-2.5 px-2 border border-slate-400 text-emerald-700">{{ $summary['closing'] ?? $summary['Closing (Lunas)'] ?? 0 }} Mhs</td>
                        <td class="py-2.5 px-2 border border-slate-400 text-rose-700">{{ $summary['lost'] }} Lead</td>
                        <td class="py-2.5 px-2 border border-slate-400 text-purple-700">{{ $summary['conversion_rate'] }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Report Data Table -->
        <div class="crm-card bg-white overflow-hidden border border-slate-200/80 rounded-2xl">
            <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Tabel Rekapitulasi Data Prospek</h3>
                <span class="text-[11px] text-slate-500 font-medium">Total: {{ count($prospects) }} Data</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs print:border print:border-slate-400">
                    <thead>
                        <tr class="bg-slate-100/80 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200 print:bg-slate-200 print:text-slate-900">
                            <th class="py-3 px-3 w-10 text-center print:border print:border-slate-400">No</th>
                            <th class="py-3 px-4 print:border print:border-slate-400">Nama Prospek / Sekolah</th>
                            <th class="py-3 px-3 print:border print:border-slate-400">Tipe</th>
                            <th class="py-3 px-3 print:border print:border-slate-400">PIC / Kontak</th>
                            <th class="py-3 px-3 print:border print:border-slate-400">Status Terakhir</th>
                            <th class="py-3 px-3 print:border print:border-slate-400">Sales Incharge</th>
                            <th class="py-3 px-3 print:border print:border-slate-400">CS Incharge</th>
                            <th class="py-3 px-4 text-right print:border print:border-slate-400">Tanggal Masuk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 print:divide-slate-400">
                        @forelse($prospects as $index => $prospect)
                            <tr class="hover:bg-slate-50/80 transition print:hover:bg-transparent">
                                <td class="py-3 px-3 text-center text-slate-500 font-medium print:border print:border-slate-400">{{ $index + 1 }}</td>
                                <td class="py-3 px-4 font-semibold text-slate-900 print:border print:border-slate-400">
                                    <span class="no-print">
                                        <a href="{{ route((strtolower(auth()->user()->role ?? '') === 'spv' ? 'spv.' : '') . 'prospek.show', $prospect['id']) }}" class="hover:text-blue-600 hover:underline">
                                            {{ $prospect['name'] }}
                                        </a>
                                    </span>
                                    <span class="print-only font-bold text-slate-900">{{ $prospect['name'] }}</span>
                                </td>
                                <td class="py-3 px-3 text-slate-600 print:border print:border-slate-400">{{ $prospect['type'] }}</td>
                                <td class="py-3 px-3 text-slate-800 font-medium print:border print:border-slate-400">{{ $prospect['pic'] }}</td>
                                <td class="py-3 px-3 print:border print:border-slate-400">
                                    <x-status-badge :status="$prospect['status']" />
                                </td>
                                <td class="py-3 px-3 text-slate-700 font-medium print:border print:border-slate-400">{{ $prospect['takeover_sales'] }}</td>
                                <td class="py-3 px-3 text-slate-700 font-medium print:border print:border-slate-400">{{ $prospect['takeover_cs'] }}</td>
                                <td class="py-3 px-4 text-right text-slate-500 font-medium print:border print:border-slate-400">{{ $prospect['created_at'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-slate-400 italic">Tidak ada data prospek yang ditemukan untuk kriteria filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- PRINT-ONLY: LEMBAR PENGESAHAN & TANDA TANGAN                   -->
        <!-- ============================================================== -->
        <div class="print-only mt-10 pt-4 print-avoid-break">
            <div class="grid grid-cols-2 gap-8 text-xs text-slate-800">
                <!-- Kolom Kiri: Head of Marketing -->
                <div class="text-center">
                    <p class="font-medium text-slate-600">Mengetahui,</p>
                    <p class="font-bold text-slate-900 uppercase mt-0.5">Head of Marketing & Admisi UCIC</p>
                    <div class="h-20 flex items-center justify-center">
                        <span class="text-slate-300 italic text-[11px]">[ Tanda Tangan & Cap ]</span>
                    </div>
                    <p class="font-bold text-slate-900 underline decoration-slate-900">Dr. Ir. Hendra Gunawan, M.M.</p>
                    <p class="text-[10px] text-slate-500 mt-0.5">NIP. 19820715 200812 1 003</p>
                </div>

                <!-- Kolom Kanan: Supervisor Marketing / Pembuat Laporan -->
                <div class="text-center">
                    <p class="font-medium text-slate-600">Cirebon, {{ date('d F Y') }}</p>
                    <p class="font-bold text-slate-900 uppercase mt-0.5">Supervisor Marketing & Sales</p>
                    <div class="h-20 flex items-center justify-center">
                        <span class="text-slate-300 italic text-[11px]">[ Tanda Tangan ]</span>
                    </div>
                    <p class="font-bold text-slate-900 underline decoration-slate-900">{{ auth()->user()->name ?? 'Supervisor Inbound' }}</p>
                    <p class="text-[10px] text-slate-500 mt-0.5">ID Pegawai: {{ auth()->user()->id ? 'UCIC-SPV-00' . auth()->user()->id : 'UCIC-SPV-001' }}</p>
                </div>
            </div>

            <!-- Footer Catatan Sistem -->
            <div class="mt-8 pt-2 border-t border-slate-300 text-[9px] text-slate-400 flex items-center justify-between">
                <span>Dokumen digenerate otomatis melalui Sistem RakitAI CRM Universitas Catur Insan Cendekia (UCIC).</span>
                <span>Halaman 1 dari 1</span>
            </div>
        </div>

    </div>

</x-app-layout>
