@php
    $pageTitle = 'Dashboard PMB';
    $pageSubtitle = 'Evaluasi Funnel, Konversi Mahasiswa, Pencapaian Target & Performa Tim';
@endphp

<x-app-layout :title="'Dashboard PMB - CRM UCIC'">
    <!-- CDN Chart.js untuk visualisasi grafik interaktif -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- State Simulation Wrappers -->
    <div x-show="$store.crm.activeState === 'loading'" x-cloak class="no-print">
        <x-loading-skeleton type="cards" />
        <div class="mt-6">
            <x-loading-skeleton type="table" />
        </div>
    </div>

    <div x-show="$store.crm.activeState === 'empty'" x-cloak class="no-print">
        <x-empty-state 
            title="Tidak ada data analitik" 
            description="Ubah parameter filter tanggal atau tahun akademik untuk memuat metrik analisis data."
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak class="no-print">
        <x-error-state />
    </div>

    <!-- ============================================================== -->
    <!-- NORMAL ANALYTICAL DATA STATE (REALTIME AJAX & INTERACTIVE)    -->
    <!-- ============================================================== -->
    <div 
        x-show="$store.crm.activeState === 'normal'" 
        x-data="analystDashboardApp({
            apiUrl: '{{ route('dashboard.data-analyst') }}',
            exportBaseUrl: '{{ route('analyst.export') }}',
            initialFilters: {{ json_encode($filters) }},
            initialMetrics: {
                totalProspekUnik: {{ $totalProspekUnik }},
                kontakBaruCount: {{ $kontakBaruCount }},
                formulirCount: {{ $formulirCount }},
                lunasCount: {{ $lunasCount }},
                lostCount: {{ $lostCount }},
                convKontakFormulir: {{ $convKontakFormulir }},
                convKontakLunas: {{ $convKontakLunas }},
                convFormulirLunas: {{ $convFormulirLunas }},
                totalRevenueValid: {{ $totalRevenueValid }},
                transaksiFormulirNominal: {{ $transaksiFormulirNominal }},
                transaksiFormulirCount: {{ $transaksiFormulirCount }},
                transaksiTermin1Nominal: {{ $transaksiTermin1Nominal }},
                transaksiTermin1Count: {{ $transaksiTermin1Count }},
                sudahFollowUpCount: {{ $sudahFollowUpCount }},
                belumFollowUpCount: {{ $belumFollowUpCount }},
                duplicateWaCount: {{ $duplicateWaCount }},
                incompleteDataCount: {{ $incompleteDataCount }},
            },
            initialCharts: {{ json_encode($chartsData) }},
            initialFunnel: {{ json_encode($funnelData) }},
            initialPeriod: {{ json_encode($periodComparison) }},
            initialSales: {{ json_encode($salesList) }},
            initialSumber: {{ json_encode($sumberAnalysis) }},
            initialTopChannel: {{ json_encode($topChannel) }},
            initialTopSekolah: {{ json_encode($topSekolah) }},
            initialTotalSekolah: {{ $totalSekolah }},
            initialTotalPerusahaan: {{ $totalPerusahaan }},
            initialSekolahDihubungi: {{ $sekolahDihubungiCount }},
            initialRekapKunjungan: {{ json_encode($rekapKunjungan) }},
            initialSegProdi: {{ json_encode($segmentasiProdi) }},
            initialSegKelas: {{ json_encode($segmentasiKelas) }},
            initialSegJalur: {{ json_encode($segmentasiJalur) }},
            initialSegWilayah: {{ json_encode($segmentasiWilayah) }},
            initialSegGelombang: {{ json_encode($segmentasiGelombang) }}
        })"
        x-init="initDashboard()"
        class="space-y-6 relative"
    >
        <!-- Subtle Top Realtime Loading Bar -->
        <div x-show="loading" x-cloak class="fixed top-0 left-0 right-0 h-1 bg-gradient-to-r from-sky-500 via-indigo-500 to-emerald-500 z-50 animate-pulse"></div>

        <!-- Header Greeting & Quick Actions -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/90 shadow-xs relative z-30">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-sky-500 via-indigo-500 to-emerald-500 rounded-t-2xl"></div>

            <div class="flex items-start sm:items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-sky-600 to-indigo-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Halo, {{ auth()->user()->name ?? 'Data Analyst' }}</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-50 text-sky-700 border border-sky-200">Data Analyst PMB</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">TA {{ $activeTa }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Akses Global Read-Only</span>
                        <span x-show="loading" x-cloak class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 animate-pulse flex items-center gap-1.5">
                            <svg class="animate-spin h-3 w-3 text-amber-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Sinkronisasi Realtime...
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Workspace analisis menyeluruh PMB: Evaluasi funnel, konversi pendaftar, pencapaian target, dan performa tim secara dinamis & realtime.</p>
                </div>
            </div>

            <!-- Quick Action Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Dropdown Ekspor Excel -->
                <div x-data="{ openExport: false }" class="relative">
                    <button 
                        @click="openExport = !openExport" 
                        class="px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition shadow-xs flex items-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-emerald-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Ekspor Excel (.xlsx)</span>
                        <svg class="w-3.5 h-3.5 ml-0.5 text-emerald-200 transition-transform duration-200" :class="{ 'rotate-180': openExport }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div 
                        x-show="openExport" 
                        @click.away="openExport = false" 
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-72 bg-white rounded-2xl shadow-2xl border border-slate-200 py-2 z-50 text-xs text-slate-700 max-h-[85vh] overflow-y-auto"
                        style="display: none;"
                    >
                        <div class="px-3.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100">Pilih Kategori Ekspor Excel</div>
                        <a :href="getExportUrl('prospek')" class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span>Data Prospek & Gelombang</span>
                        </a>
                        <a :href="getExportUrl('funnel')" class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                            <span>Rekapitulasi Funnel PMB</span>
                        </a>
                        <a :href="getExportUrl('transaksi')" class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Transaksi Pembayaran Valid</span>
                        </a>
                        <a :href="getExportUrl('performa_sales')" class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            <span>Performa Sales & SPV</span>
                        </a>
                        <a :href="getExportUrl('sumber')" class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>Sumber & Kanal Marketing</span>
                        </a>
                        <a :href="getExportUrl('sekolah')" class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span>Sekolah & Perusahaan Mitra</span>
                        </a>
                        <a href="{{ route('export.kunjungan') }}" target="_blank" class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-sky-50 text-sky-800 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                            <span>Rekap Kunjungan (Semua Role)</span>
                        </a>
                        <a :href="getExportUrl('target')" class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                            <span>Target vs Realisasi</span>
                        </a>
                        <div class="border-t border-slate-100 my-1"></div>
                        <a :href="getExportUrl('follow_up')" class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                            <span>Detail Riwayat Follow-Up</span>
                        </a>
                        <a :href="getExportUrl('kualitas_data')" class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span>Audit Kualitas Data</span>
                        </a>
                    </div>
                </div>

                <a href="{{ route('analyst.prospek.index') }}" class="px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition flex items-center gap-1.5 border border-slate-200/80">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    <span>Eksplorasi Prospek</span>
                </a>

                <button 
                    type="button" 
                    @click="window.print()"
                    class="px-3.5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition flex items-center gap-1.5 shadow-xs cursor-pointer"
                >
                    <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Cetak Laporan</span>
                </button>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MULTI-DIMENSIONAL FILTER BAR (REALTIME AJAX/FETCH)             -->
        <!-- ============================================================== -->
        <form 
            x-ref="filterForm"
            @submit.prevent="applyFilter" 
            class="no-print bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/90 shadow-xs transition-opacity space-y-4"
            :class="{ 'opacity-70': loading }"
        >
            <!-- Header Filter Bar -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-slate-900 uppercase tracking-wider block">Filter Analisis Terpadu (Realtime Dinamis)</span>
                        <span class="text-[10px] text-slate-400 font-medium">Data grafik, funnel, omset, dan kunjungan otomatis menyesuaikan parameter</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        @click="resetFilter" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200/60 transition cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Reset Filter</span>
                    </button>
                </div>
            </div>

            <!-- Row 1: Dimensi Utama (4 Kolom Teratur) -->
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2">Dimensi Utama Analisis</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 text-xs">
                    <!-- Periode Cepat -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Periode Waktu</label>
                        <select name="periode" @change="applyFilter" class="w-full px-3 py-2.5 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="semua" {{ ($filters['periode'] ?? '') === 'semua' ? 'selected' : '' }}>Semua Waktu</option>
                            <option value="hari_ini" {{ ($filters['periode'] ?? '') === 'hari_ini' ? 'selected' : '' }}>Hari Ini</option>
                            <option value="kemarin" {{ ($filters['periode'] ?? '') === 'kemarin' ? 'selected' : '' }}>Kemarin</option>
                            <option value="minggu_ini" {{ ($filters['periode'] ?? '') === 'minggu_ini' ? 'selected' : '' }}>Minggu Ini</option>
                            <option value="bulan_ini" {{ ($filters['periode'] ?? '') === 'bulan_ini' ? 'selected' : '' }}>Bulan Ini</option>
                            <option value="tahunan" {{ ($filters['periode'] ?? '') === 'tahunan' ? 'selected' : '' }}>Tahun Berjalan</option>
                        </select>
                    </div>

                    <!-- Tahun Akademik -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Tahun Akademik</label>
                        <select name="ta" @change="applyFilter" class="w-full px-3 py-2.5 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="all">Semua Tahun Akademik</option>
                            @foreach($taOptions as $t)
                                <option value="{{ $t->nama }}" {{ ($filters['ta'] ?? '') === $t->nama ? 'selected' : '' }}>{{ $t->nama }} {{ $t->status === 'Aktif' ? '(Aktif)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Wilayah -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Wilayah / Domisili</label>
                        <select name="wilayah_id" @change="applyFilter" class="w-full px-3 py-2.5 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="all">Semua Wilayah</option>
                            @foreach($wilayahOptions as $w)
                                <option value="{{ $w->id }}" {{ ($filters['wilayah_id'] ?? '') == $w->id ? 'selected' : '' }}>{{ $w->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Prodi -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Program Studi</label>
                        <select name="prodi_id" @change="applyFilter" class="w-full px-3 py-2.5 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="all">Semua Program Studi</option>
                            @foreach($prodiOptions as $p)
                                <option value="{{ $p->id }}" {{ ($filters['prodi_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Row 2: Parameter Lanjutan (5 Kolom Bersih) -->
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2">Parameter Detail Pipeline & Tim</span>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 text-xs">
                    <!-- Kelas -->
                    <div class="space-y-1">
                        <label class="block text-[10.5px] font-bold text-slate-600">Kelas Mahasiswa</label>
                        <select name="kelas" @change="applyFilter" class="w-full px-3 py-2 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="all">Semua Kelas</option>
                            <option value="Reguler" {{ ($filters['kelas'] ?? '') === 'Reguler' ? 'selected' : '' }}>Reguler</option>
                            <option value="Karyawan" {{ ($filters['kelas'] ?? '') === 'Karyawan' ? 'selected' : '' }}>Karyawan</option>
                        </select>
                    </div>

                    <!-- Gelombang Pendaftaran -->
                    <div class="space-y-1">
                        <label class="block text-[10.5px] font-bold text-slate-600">Gelombang PMB</label>
                        <select name="gelombang" @change="applyFilter" class="w-full px-3 py-2 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="all">Semua Gelombang</option>
                            @foreach($gelombangOptions as $g)
                                <option value="{{ $g->nama }}" {{ ($filters['gelombang'] ?? '') === $g->nama ? 'selected' : '' }}>{{ $g->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sumber Informasi -->
                    <div class="space-y-1">
                        <label class="block text-[10.5px] font-bold text-slate-600">Sumber Lead</label>
                        <select name="source" @change="applyFilter" class="w-full px-3 py-2 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="all">Semua Sumber</option>
                            @foreach(\App\Models\Prospek::SOURCES as $src)
                                <option value="{{ $src }}" {{ ($filters['source'] ?? '') === $src ? 'selected' : '' }}>{{ $src }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Lead -->
                    <div class="space-y-1">
                        <label class="block text-[10.5px] font-bold text-slate-600">Status Tahapan</label>
                        <select name="status" @change="applyFilter" class="w-full px-3 py-2 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="all">Semua Status</option>
                            <option value="BARU" {{ ($filters['status'] ?? '') === 'BARU' ? 'selected' : '' }}>BARU</option>
                            <option value="KONTAK" {{ ($filters['status'] ?? '') === 'KONTAK' ? 'selected' : '' }}>KONTAK</option>
                            <option value="PROSPEK" {{ ($filters['status'] ?? '') === 'PROSPEK' ? 'selected' : '' }}>HANGAT</option>
                            <option value="HOT PROSPEK" {{ ($filters['status'] ?? '') === 'HOT PROSPEK' ? 'selected' : '' }}>PANAS</option>
                            <option value="FORMULIR" {{ ($filters['status'] ?? '') === 'FORMULIR' ? 'selected' : '' }}>FORMULIR</option>
                            <option value="BERKAS" {{ ($filters['status'] ?? '') === 'BERKAS' ? 'selected' : '' }}>BERKAS</option>
                            <option value="LUNAS" {{ ($filters['status'] ?? '') === 'LUNAS' ? 'selected' : '' }}>LUNAS</option>
                            <option value="DINGIN" {{ ($filters['status'] ?? '') === 'DINGIN' ? 'selected' : '' }}>DINGIN</option>
                            <option value="CANCEL" {{ ($filters['status'] ?? '') === 'CANCEL' ? 'selected' : '' }}>BATAL</option>
                        </select>
                    </div>

                    <!-- Sales -->
                    <div class="space-y-1">
                        <label class="block text-[10.5px] font-bold text-slate-600">Sales Handler</label>
                        <select name="sales_id" @change="applyFilter" class="w-full px-3 py-2 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="all">Semua Sales</option>
                            @foreach($salesOptions as $s)
                                <option value="{{ $s->id }}" {{ ($filters['sales_id'] ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Row 3: Custom Date Range & Realtime Sync Indicator -->
            <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[11px] font-bold text-slate-600 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Rentang Tanggal Kustom:</span>
                    </span>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                    <span class="text-slate-400 text-xs font-semibold">s/d</span>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                    <button type="submit" class="px-4 py-1.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs transition shadow-xs cursor-pointer">Terapkan Tanggal</button>
                </div>
                <div class="text-[11px] text-slate-500 font-medium flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span>Filter otomatis tersinkron ke metrik & grafik secara realtime</span>
                </div>
            </div>
        </form>

        <!-- ============================================================== -->
        <!-- SECTION 1: RINGKASAN EKSEKUTIF PENCAPAIAN PMB                  -->
        <!-- ============================================================== -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-sky-600"></span>
                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">1. Ringkasan Eksekutif & Konversi PMB</h3>
                </div>
                <span class="text-[11px] text-slate-400 font-medium">Metrik Konsolidasi Akurat</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
                <!-- Total Kontak Unik -->
                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-xs hover:-translate-y-1 hover:shadow-md transition-all duration-200 relative overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[10.5px] font-bold text-slate-500 uppercase tracking-wider">Total Kontak</span>
                            <span class="w-6 h-6 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight" x-text="Number(metrics.totalProspekUnik).toLocaleString('id-ID')">{{ number_format($totalProspekUnik) }}</div>
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium block mt-1.5 pt-1.5 border-t border-slate-100">Seluruh lead terdata</span>
                </div>

                <!-- Kontak Baru -->
                <div class="p-4 rounded-2xl bg-gradient-to-br from-sky-50/60 via-white to-white border border-sky-100 shadow-xs hover:-translate-y-1 hover:shadow-md transition-all duration-200 relative overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[10.5px] font-bold text-sky-700 uppercase tracking-wider">Kontak Baru</span>
                            <span class="w-6 h-6 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-sky-950 tracking-tight" x-text="Number(metrics.kontakBaruCount).toLocaleString('id-ID')">{{ number_format($kontakBaruCount) }}</div>
                    </div>
                    <span class="text-[10px] text-sky-600 font-medium block mt-1.5 pt-1.5 border-t border-sky-100/70">Periode aktif terpilih</span>
                </div>

                <!-- Beli Formulir -->
                <div class="p-4 rounded-2xl bg-gradient-to-br from-purple-50/60 via-white to-white border border-purple-100 shadow-xs hover:-translate-y-1 hover:shadow-md transition-all duration-200 relative overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[10.5px] font-bold text-purple-700 uppercase tracking-wider">Beli Formulir</span>
                            <span class="w-6 h-6 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-purple-950 tracking-tight" x-text="Number(metrics.formulirCount).toLocaleString('id-ID')">{{ number_format($formulirCount) }}</div>
                    </div>
                    <span class="text-[10px] text-purple-600 font-medium block mt-1.5 pt-1.5 border-t border-purple-100/70">Pendaftar terverifikasi</span>
                </div>

                <!-- Maba Lunas -->
                <div class="p-4 rounded-2xl bg-gradient-to-br from-emerald-50/60 via-white to-white border border-emerald-100 shadow-xs hover:-translate-y-1 hover:shadow-md transition-all duration-200 relative overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[10.5px] font-bold text-emerald-700 uppercase tracking-wider">Maba Lunas</span>
                            <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-950 tracking-tight" x-text="Number(metrics.lunasCount).toLocaleString('id-ID')">{{ number_format($lunasCount) }}</div>
                    </div>
                    <span class="text-[10px] text-emerald-600 font-medium block mt-1.5 pt-1.5 border-t border-emerald-100/70">Resmi registrasi Termin 1</span>
                </div>

                <!-- Konversi Kontak -> Lunas -->
                <div class="p-4 rounded-2xl bg-gradient-to-br from-indigo-50/60 via-white to-white border border-indigo-100 shadow-xs hover:-translate-y-1 hover:shadow-md transition-all duration-200 relative overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[10.5px] font-bold text-indigo-700 uppercase tracking-wider">Konversi Lunas</span>
                            <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            </span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-indigo-950 tracking-tight" x-text="metrics.convKontakLunas + '%'">{{ $convKontakLunas }}%</div>
                    </div>
                    <span class="text-[10px] text-indigo-600 font-medium block mt-1.5 pt-1.5 border-t border-indigo-100/70">Rasio kontak ke maba</span>
                </div>

                <!-- Total Omset Valid -->
                <div class="p-4 rounded-2xl bg-gradient-to-br from-teal-50/60 via-white to-white border border-teal-100 shadow-xs hover:-translate-y-1 hover:shadow-md transition-all duration-200 relative overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[10.5px] font-bold text-teal-700 uppercase tracking-wider">Omset Valid</span>
                            <span class="w-6 h-6 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                        </div>
                        <div class="text-xl sm:text-2xl font-black text-teal-950 tracking-tight" x-text="'Rp ' + (Number(metrics.totalRevenueValid) / 1000000).toFixed(1) + ' Jt'">Rp {{ number_format($totalRevenueValid / 1000000, 1) }} Jt</div>
                    </div>
                    <span class="text-[10px] text-teal-600 font-medium block mt-1.5 pt-1.5 border-t border-teal-100/70">Formulir + Termin 1</span>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- SECTION: GRAFIK VISUALISASI INTERAKTIF (CHART.JS)             -->
        <!-- ============================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Chart 1: Tren Pergerakan PMB -->
            <div class="crm-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                </svg>
                                <span>Grafik Tren Pergerakan PMB</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Pola registrasi prospek masuk, formulir, dan mahasiswa lunas</p>
                        </div>
                        <span class="text-[10px] font-bold text-sky-700 bg-sky-50 px-2 py-0.5 rounded-full border border-sky-200">Interaktif</span>
                    </div>

                    <div class="h-64 w-full relative">
                        <canvas id="trendChartCanvas"></canvas>
                    </div>
                </div>
            </div>

            <!-- Chart 2: Visual Funnel Konversi Interaktif -->
            <div class="crm-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                <span>Visualisasi Batang Funnel PMB</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Tingkat penurunan calon mahasiswa di setiap tahapan</p>
                        </div>
                        <span class="text-[10px] font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-full border border-purple-200">5 Tahap</span>
                    </div>

                    <div class="h-64 w-full relative">
                        <canvas id="funnelChartCanvas"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Chart 3: Distribusi Program Studi -->
            <div class="crm-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                            </svg>
                            <span>Distribusi Pilihan Program Studi</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Komposisi minat calon mahasiswa terhadap prodi UCIC</p>
                    </div>
                </div>

                <div class="h-64 w-full relative">
                    <canvas id="prodiChartCanvas"></canvas>
                </div>
            </div>

            <!-- Chart 4: Komparasi Performa Top Sales Personel -->
            <div class="crm-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Komparasi Top Sales (Formulir vs Lunas)</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Perolehan closing pendaftar antar sales personel</p>
                    </div>
                </div>

                <div class="h-64 w-full relative">
                    <canvas id="salesChartCanvas"></canvas>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- SECTION 2: FUNNEL DETAIL PROGRESS BARS                         -->
        <!-- ============================================================== -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/90 shadow-xs relative overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center shrink-0 shadow-xs">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight">Funnel Konversi 5 Tahap PMB UCIC</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">Standar PMB</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Penelusuran perjalanan calon mahasiswa dari kontak awal, formulir, berkas, hingga pelunasan Termin 1.</p>
                    </div>
                </div>
                <div class="text-left sm:text-right text-xs bg-rose-50/80 px-3 py-2 rounded-xl border border-rose-100 shrink-0">
                    <span class="text-rose-600 font-semibold block text-[11px]">Drop-off / Batal Total</span>
                    <span class="font-black text-rose-700 text-sm" x-text="Number(metrics.lostCount).toLocaleString('id-ID') + ' Calon'">{{ number_format($lostCount) }} Calon</span>
                </div>
            </div>

            <!-- Visual Funnel Stage Bars -->
            <div class="space-y-3.5">
                <template x-for="(f, idx) in funnelData" :key="idx">
                    <div class="bg-slate-50/80 hover:bg-slate-50 p-4 rounded-xl border border-slate-200/70 transition-all">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-white shadow-2xs border border-slate-200 text-slate-700 font-black text-xs flex items-center justify-center" x-text="idx + 1"></span>
                                <div>
                                    <span class="font-extrabold text-xs text-slate-900" x-text="f.stage"></span>
                                    <span class="text-[11px] text-slate-500 ml-2 hidden sm:inline" x-text="f.sub"></span>
                                </div>
                            </div>
                            <div class="text-right flex items-center gap-2">
                                <span class="font-black text-sm text-slate-900" x-text="Number(f.count).toLocaleString('id-ID')"></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white border border-slate-200 text-slate-700" x-text="f.pct_total + '%'"></span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-slate-200/70 rounded-full h-3 overflow-hidden p-0.5">
                            <div 
                                class="h-2 rounded-full transition-all duration-700 ease-out shadow-xs" 
                                :class="'bg-' + f.color + '-500'"
                                :style="'width: ' + f.pct_total + '%'"
                            ></div>
                        </div>

                        <template x-if="f.drop_count > 0">
                            <div class="mt-2 flex items-center justify-between text-[10.5px] text-slate-400 font-medium pt-1.5 border-t border-slate-200/40">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                    <span>Status berhenti / tidak merespons pada tahap ini:</span>
                                </span>
                                <span class="font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md" x-text="Number(f.drop_count).toLocaleString('id-ID') + ' prospek'"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <!-- Conversion Ratios Summary -->
            <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-3.5 text-center">
                <div class="p-4 bg-gradient-to-br from-blue-50/70 via-white to-white rounded-xl border border-blue-100 shadow-2xs">
                    <span class="text-[11px] text-blue-700 font-bold uppercase tracking-wider block mb-1">Kontak → Beli Formulir</span>
                    <span class="text-2xl font-black text-blue-950" x-text="metrics.convKontakFormulir + '%'">{{ $convKontakFormulir }}%</span>
                    <span class="text-[10px] text-blue-600 block mt-1">Efektivitas pendekatan awal</span>
                </div>
                <div class="p-4 bg-gradient-to-br from-purple-50/70 via-white to-white rounded-xl border border-purple-100 shadow-2xs">
                    <span class="text-[11px] text-purple-700 font-bold uppercase tracking-wider block mb-1">Formulir → Lunas Termin 1</span>
                    <span class="text-2xl font-black text-purple-950" x-text="metrics.convFormulirLunas + '%'">{{ $convFormulirLunas }}%</span>
                    <span class="text-[10px] text-purple-600 block mt-1">Konversi pendaftar ke registrasi</span>
                </div>
                <div class="p-4 bg-gradient-to-br from-emerald-50/70 via-white to-white rounded-xl border border-emerald-100 shadow-2xs">
                    <span class="text-[11px] text-emerald-700 font-bold uppercase tracking-wider block mb-1">Kontak → Lunas Maba Resmi</span>
                    <span class="text-2xl font-black text-emerald-950" x-text="metrics.convKontakLunas + '%'">{{ $convKontakLunas }}%</span>
                    <span class="text-[10px] text-emerald-600 block mt-1">Rasio akhir closing PMB</span>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- SECTION 3: PERBANDINGAN PERIODE & TRANSAKSI PEMBAYARAN         -->
        <!-- ============================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Card 1: Perbandingan Periode Waktu -->
            <div class="crm-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            <span>Perbandingan Periode Waktu</span>
                        </h3>
                        <span class="text-[11px] text-slate-400">Tersaring Sesuai Filter Aktif</span>
                    </div>

                    <div class="space-y-3 text-xs">
                        <!-- Hari Ini vs Kemarin -->
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
                            <div class="flex items-center justify-between font-bold text-slate-800 mb-1">
                                <span>Hari Ini vs Kemarin</span>
                                <span :class="periodComparison.today.kontak_diff >= 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="(periodComparison.today.kontak_diff >= 0 ? '+' : '') + periodComparison.today.kontak_diff + ' Kontak'"></span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-500">
                                <div>Kontak: <strong x-text="periodComparison.today.kontak"></strong> (Kemarin: <span x-text="periodComparison.today.kontak_prev"></span>)</div>
                                <div>Lunas: <strong x-text="periodComparison.today.lunas"></strong> (Kemarin: <span x-text="periodComparison.today.lunas_prev"></span>)</div>
                            </div>
                        </div>

                        <!-- Minggu Ini vs Minggu Lalu -->
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
                            <div class="flex items-center justify-between font-bold text-slate-800 mb-1">
                                <span>Minggu Ini vs Minggu Lalu</span>
                                <span :class="periodComparison.week.kontak_diff >= 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="(periodComparison.week.kontak_diff >= 0 ? '+' : '') + periodComparison.week.kontak_diff + ' Kontak'"></span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-500">
                                <div>Kontak: <strong x-text="periodComparison.week.kontak"></strong> (Lalu: <span x-text="periodComparison.week.kontak_prev"></span>)</div>
                                <div>Lunas: <strong x-text="periodComparison.week.lunas"></strong> (Lalu: <span x-text="periodComparison.week.lunas_prev"></span>)</div>
                            </div>
                        </div>

                        <!-- Bulan Ini vs Bulan Kemarin -->
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
                            <div class="flex items-center justify-between font-bold text-slate-800 mb-1">
                                <span>Bulan Ini vs Bulan Kemarin</span>
                                <span :class="periodComparison.month.kontak_diff >= 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="(periodComparison.month.kontak_diff >= 0 ? '+' : '') + periodComparison.month.kontak_diff + ' Kontak'"></span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-500">
                                <div>Kontak: <strong x-text="periodComparison.month.kontak"></strong> (Lalu: <span x-text="periodComparison.month.kontak_prev"></span>)</div>
                                <div>Lunas: <strong x-text="periodComparison.month.lunas"></strong> (Lalu: <span x-text="periodComparison.month.lunas_prev"></span>)</div>
                            </div>
                        </div>
                    </div>

                    <!-- Keterbatasan Data Historis YoY -->
                    <div class="mt-4 p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl text-[11px] text-amber-800">
                        <div class="font-bold flex items-center gap-1.5 mb-0.5">
                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Perbandingan Tahun Akademik Sebelumnya (YoY):</span>
                        </div>
                        <p class="leading-relaxed text-amber-700">Data operasional CRM saat ini aktif pada TA {{ $activeTa }}. Perbandingan antar periode dihitung dinamis sesuai filter yang dipilih.</p>
                    </div>
                </div>
            </div>

            <!-- Card 2: Pendaftar & Pembayaran Valid -->
            <div class="crm-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Pendaftar & Pembayaran Masuk (Tersaring)</span>
                        </h3>
                        <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">Verified CS</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="p-4 rounded-xl bg-purple-50/70 border border-purple-100">
                            <span class="text-[11px] font-bold text-purple-700 block">Biaya Formulir (Rp)</span>
                            <span class="text-lg font-black text-purple-950 block mt-1" x-text="'Rp ' + Number(metrics.transaksiFormulirNominal).toLocaleString('id-ID')">Rp {{ number_format($transaksiFormulirNominal, 0, ',', '.') }}</span>
                            <span class="text-[10px] text-purple-600 font-semibold" x-text="metrics.transaksiFormulirCount + ' Transaksi'">{{ $transaksiFormulirCount }} Transaksi</span>
                        </div>
                        <div class="p-4 rounded-xl bg-emerald-50/70 border border-emerald-100">
                            <span class="text-[11px] font-bold text-emerald-700 block">Termin 1 Maba (Rp)</span>
                            <span class="text-lg font-black text-emerald-950 block mt-1" x-text="'Rp ' + Number(metrics.transaksiTermin1Nominal).toLocaleString('id-ID')">Rp {{ number_format($transaksiTermin1Nominal, 0, ',', '.') }}</span>
                            <span class="text-[10px] text-emerald-600 font-semibold" x-text="metrics.transaksiTermin1Count + ' Calon Terverifikasi'">{{ $transaksiTermin1Count }} Calon Terverifikasi</span>
                        </div>
                    </div>

                    <div>
                        <div class="text-[11px] font-bold text-slate-700 mb-2 uppercase tracking-wider">Penerimaan Berdasarkan Program Studi:</div>
                        <div class="space-y-2">
                            <template x-for="(p, idx) in pembayaranPerProdi" :key="idx">
                                <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-slate-50 border border-slate-100">
                                    <span class="font-semibold text-slate-800" x-text="p.nama_prodi"></span>
                                    <span class="font-extrabold text-slate-900" x-text="'Rp ' + Number(p.total_nominal).toLocaleString('id-ID')"></span>
                                </div>
                            </template>
                            <template x-if="pembayaranPerProdi.length === 0">
                                <div class="text-center py-3 text-xs text-slate-400">Belum ada transaksi pembayaran pada filter ini.</div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- SECTION 4: TARGET & PENCAPAIAN BERJENJANG (CASCADING)          -->
        <!-- ============================================================== -->
        @if(isset($targetAchievementData))
            <x-target-achievement-table 
                :data="$targetAchievementData" 
                title="Pencapaian Target PMB Berjenjang (Global Cascading)" 
                subtitle="Monitoring komprehensif target, realisasi, defisit kumulatif, dan indikator warna batas pencapaian"
            />
        @endif

        <!-- ============================================================== -->
        <!-- SECTION 5: KINERJA SALES & TIM PENANGANAN                      -->
        <!-- ============================================================== -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/90 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0 shadow-xs">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight">Evaluasi Performa Tim Sales & Penugasan</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200" x-text="salesList.length + ' Personel'">{{ count($salesList) }} Personel</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Analisis kuantitas kontak, intensitas follow-up, waktu respon awal, dan rasio konversi per Sales.</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50/90 text-slate-500 font-bold uppercase text-[10px] border-b border-slate-200/80">
                        <tr>
                            <th class="py-3 px-3.5">Sales Personel</th>
                            <th class="py-3 px-3">Supervisor (SPV)</th>
                            <th class="py-3 px-3">Wilayah</th>
                            <th class="py-3 px-3 text-center">Kontak Handled</th>
                            <th class="py-3 px-3 text-center">Aktivitas Follow-Up</th>
                            <th class="py-3 px-3 text-center">Respon Pertama</th>
                            <th class="py-3 px-3 text-center">Formulir</th>
                            <th class="py-3 px-3 text-center">Closing / Lunas</th>
                            <th class="py-3 px-3 text-center">Rasio Konversi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="sales in salesList" :key="sales.id">
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center shrink-0 uppercase" x-text="(sales.name || 'S').substring(0, 2)"></div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 truncate" x-text="sales.name"></div>
                                            <div class="text-[10px] text-slate-400 font-mono" x-text="sales.kode || '-'"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-slate-600">
                                    <span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium" x-text="sales.spv || '-'"></span>
                                </td>
                                <td class="py-3 px-3 text-slate-600 font-medium" x-text="sales.wilayah || '-'"></td>
                                <td class="py-3 px-3 text-center font-bold text-slate-800 text-sm" x-text="Number(sales.total_kontak).toLocaleString('id-ID')"></td>
                                <td class="py-3 px-3 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded-md bg-sky-50 text-sky-700 font-semibold text-[11px]" x-text="Number(sales.follow_up_count).toLocaleString('id-ID') + 'x'"></span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <template x-if="sales.avg_response_hours !== null">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold" :class="sales.avg_response_hours <= 2 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'" x-text="sales.avg_response_hours + ' Jam'"></span>
                                    </template>
                                    <template x-if="sales.avg_response_hours === null">
                                        <span class="text-slate-400 text-[10px]">-</span>
                                    </template>
                                </td>
                                <td class="py-3 px-3 text-center font-bold text-purple-700 text-sm" x-text="Number(sales.formulir_count).toLocaleString('id-ID')"></td>
                                <td class="py-3 px-3 text-center font-black text-emerald-700 text-sm" x-text="Number(sales.lunas_count).toLocaleString('id-ID')"></td>
                                <td class="py-3 px-3 text-center">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-black" :class="sales.konversi >= 10 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : (sales.konversi >= 5 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-700')" x-text="sales.konversi + '%'"></span>
                                </td>
                            </tr>
                        </template>
                        <template x-if="salesList.length === 0">
                            <tr>
                                <td colspan="9" class="text-center py-8 text-slate-400">Belum ada data sales yang terdaftar pada filter ini.</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
        </div>

        <!-- ============================================================== -->
        <!-- SECTION 6: KANAL MARKETING & MITRA (SEKOLAH/PERUSAHAAN)        -->
        <!-- ============================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Sumber & Kanal Informasi -->
            <div class="crm-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Perbandingan Sumber Informasi</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Ranking kanal promosi berdasarkan jumlah prospek & konversi</p>
                    </div>
                </div>

                <template x-if="topChannel">
                    <div class="p-3 mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-emerald-700">Kanal Konversi Tertinggi:</span>
                            <div class="font-extrabold text-emerald-900 text-sm" x-text="topChannel.nama"></div>
                        </div>
                        <div class="text-right">
                            <span class="font-black text-emerald-700 text-base" x-text="topChannel.lunas + ' Maba Lunas'"></span>
                            <span class="text-[10px] text-emerald-600 block" x-text="'(' + topChannel.konversi + '% Konversi)'"></span>
                        </div>
                    </div>
                </template>

                <div class="space-y-3">
                    <template x-for="src in sumberAnalysis.slice(0, 6)" :key="src.nama">
                        <div class="text-xs">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-slate-800" x-text="src.nama"></span>
                                <span class="font-semibold text-slate-600">
                                    <span x-text="src.kontak + ' Kontak'"></span> • 
                                    <strong class="text-emerald-600" x-text="src.lunas + ' Lunas'"></strong> 
                                    <span x-text="'(' + src.konversi + '%)'"></span>
                                </span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-sky-500 h-2 rounded-full" :style="'width: ' + (metrics.totalProspekUnik > 0 ? Math.min(100, Math.round((src.kontak / metrics.totalProspekUnik) * 100)) : 0) + '%'"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Kemitraan Sekolah & Perusahaan -->
            <div class="crm-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Kemitraan Sekolah & Perusahaan</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Penetrasi kunjungan dan perolehan pendaftar dari institusi</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-center">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Total Sekolah Mitra</span>
                        <span class="text-xl font-black text-slate-900" x-text="Number(totalSekolah).toLocaleString('id-ID')">{{ number_format($totalSekolah) }}</span>
                        <span class="text-[10px] text-emerald-600 font-semibold block mt-0.5" x-text="sekolahDihubungiCount + ' Sudah Dihubungi'">{{ $sekolahDihubungiCount }} Sudah Dihubungi</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-center">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Total Perusahaan Mitra</span>
                        <span class="text-xl font-black text-slate-900" x-text="Number(totalPerusahaan).toLocaleString('id-ID')">{{ number_format($totalPerusahaan) }}</span>
                        <span class="text-[10px] text-slate-500 font-semibold block mt-0.5">Kerjasama Industri</span>
                    </div>
                </div>

                <div>
                    <div class="text-[11px] font-bold text-slate-700 mb-2 uppercase tracking-wider">Top 5 Sekolah Penyumbang Pendaftar Terbanyak:</div>
                    <div class="space-y-2">
                        <template x-for="sch in topSekolah" :key="sch.id">
                            <div class="flex items-center justify-between text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="min-w-0 pr-2">
                                    <div class="font-bold text-slate-900 truncate" x-text="sch.nama"></div>
                                    <div class="text-[10px] text-slate-400" x-text="sch.wilayah ? sch.wilayah.nama : '-'"></div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="font-extrabold text-blue-700" x-text="sch.total_prospek + ' Kontak'"></span>
                                    <span class="text-[10px] font-bold text-emerald-600 block" x-text="sch.total_lunas + ' Lunas'"></span>
                                </div>
                            </div>
                        </template>
                        <template x-if="topSekolah.length === 0">
                            <div class="text-center py-4 text-xs text-slate-400">Belum ada data sekolah mitra.</div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- SECTION: REKAP DATA KUNJUNGAN SEKOLAH & PERUSAHAAN (SEMUA ROLE)  -->
        <!-- ============================================================== -->
        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 uppercase tracking-wider">Rekap Data Kunjungan Sekolah & Perusahaan</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200">Semua Role Berwenang</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Data riil kunjungan offline, presentasi edukasi sekolah, dan audiensi kemitraan industri dari seluruh role (Sales, SPV, HM, Admin, dll.) tanpa data dummy.</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <a 
                        href="{{ route('export.kunjungan') }}" 
                        target="_blank"
                        class="px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold text-xs rounded-xl border border-emerald-200 transition flex items-center gap-1.5 cursor-pointer"
                        title="Unduh data kunjungan format Excel"
                    >
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Unduh Kunjungan (.xlsx)</span>
                    </a>
                    <a 
                        href="{{ route('kunjungan.index') }}" 
                        class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-xl transition flex items-center gap-1.5 shadow-xs cursor-pointer"
                    >
                        <span>Eksplorasi Lengkap</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- 4 Summary Metric Cards Kunjungan -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
                <div class="p-4 bg-gradient-to-br from-sky-50/50 via-white to-white rounded-2xl border border-sky-100/90 shadow-2xs hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">Total Kunjungan</span>
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 mt-1 block tracking-tight" x-text="Number(rekapKunjungan.total_kunjungan || 0).toLocaleString('id-ID')">{{ number_format($rekapKunjungan['total_kunjungan'] ?? 0) }}</span>
                        <span class="text-[10.5px] text-slate-400 font-medium block mt-1">Seluruh kunjungan riil</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-sky-100/80 text-sky-700 flex items-center justify-center shrink-0 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                </div>

                <div class="p-4 bg-gradient-to-br from-blue-50/50 via-white to-white rounded-2xl border border-blue-100/90 shadow-2xs hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider block">Kunjungan Sekolah</span>
                        <span class="text-2xl sm:text-3xl font-black text-blue-950 mt-1 block tracking-tight" x-text="Number(rekapKunjungan.total_sekolah || 0).toLocaleString('id-ID')">{{ number_format($rekapKunjungan['total_sekolah'] ?? 0) }}</span>
                        <span class="text-[10.5px] text-blue-600 font-medium block mt-1">Presentasi & Edufair</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-blue-100/80 text-blue-700 flex items-center justify-center shrink-0 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>

                <div class="p-4 bg-gradient-to-br from-purple-50/50 via-white to-white rounded-2xl border border-purple-100/90 shadow-2xs hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-purple-700 uppercase tracking-wider block">Kunjungan Perusahaan</span>
                        <span class="text-2xl sm:text-3xl font-black text-purple-950 mt-1 block tracking-tight" x-text="Number(rekapKunjungan.total_perusahaan || 0).toLocaleString('id-ID')">{{ number_format($rekapKunjungan['total_perusahaan'] ?? 0) }}</span>
                        <span class="text-[10.5px] text-purple-600 font-medium block mt-1">Kerjasama & Kelas Mitra</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-purple-100/80 text-purple-700 flex items-center justify-center shrink-0 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>

                <div class="p-4 bg-gradient-to-br from-emerald-50/50 via-white to-white rounded-2xl border border-emerald-100/90 shadow-2xs hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Selesai / Terverifikasi</span>
                        <span class="text-2xl sm:text-3xl font-black text-emerald-950 mt-1 block tracking-tight" x-text="Number(rekapKunjungan.total_selesai || 0).toLocaleString('id-ID')">{{ number_format($rekapKunjungan['total_selesai'] ?? 0) }}</span>
                        <span class="text-[10.5px] text-emerald-600 font-medium block mt-1">Hasil valid & terlaksana</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-100/80 text-emerald-700 flex items-center justify-center shrink-0 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Kolom Kiri (1 cols): Rekap Kunjungan per Role -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="pb-3 border-b border-slate-100 mb-3">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span>Distribusi per Role Petugas</span>
                            </h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">Konsolidasi volume kunjungan berdasarkan role akun pengguna</p>
                        </div>

                        <div class="space-y-3">
                            <template x-for="r in (rekapKunjungan.per_role || [])" :key="r.role">
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <div class="flex items-center gap-1.5">
                                            <span 
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold tracking-wide uppercase"
                                                :class="{
                                                    'bg-blue-100 text-blue-700': r.role === 'Sales',
                                                    'bg-amber-100 text-amber-700': r.role === 'SPV',
                                                    'bg-emerald-100 text-emerald-700': r.role === 'HM',
                                                    'bg-purple-100 text-purple-700': r.role === 'Admin',
                                                    'bg-indigo-100 text-indigo-700': r.role === 'CS',
                                                    'bg-rose-100 text-rose-700': r.role === 'EO',
                                                    'bg-slate-200 text-slate-700': !['Sales', 'SPV', 'HM', 'Admin', 'CS', 'EO'].includes(r.role)
                                                }"
                                                x-text="r.role"
                                            ></span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs font-black text-slate-900" x-text="r.total_kunjungan + ' Kunjungan'"></span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-3 gap-1.5 text-center text-[10px] pt-1 border-t border-slate-200/50 mt-1.5">
                                        <div>
                                            <span class="text-slate-400 block">Sekolah</span>
                                            <span class="font-bold text-blue-700" x-text="r.total_sekolah"></span>
                                        </div>
                                        <div>
                                            <span class="text-slate-400 block">Perusahaan</span>
                                            <span class="font-bold text-purple-700" x-text="r.total_perusahaan"></span>
                                        </div>
                                        <div>
                                            <span class="text-slate-400 block">Selesai</span>
                                            <span class="font-bold text-emerald-700" x-text="r.total_selesai"></span>
                                        </div>
                                    </div>

                                    <!-- Progress share -->
                                    <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden mt-2">
                                        <div 
                                            class="h-1.5 rounded-full bg-sky-500" 
                                            :style="'width: ' + ((rekapKunjungan.total_kunjungan > 0 ? (r.total_kunjungan / rekapKunjungan.total_kunjungan) * 100 : 0)) + '%'"
                                        ></div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="!rekapKunjungan.per_role || rekapKunjungan.per_role.length === 0">
                                <div class="py-8 text-center text-xs text-slate-400">
                                    Belum ada data kunjungan untuk role manapun.
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="pt-3 mt-3 border-t border-slate-100 text-[11px] text-slate-400 text-center">
                        Total role aktif berkunjung: <strong class="text-slate-700" x-text="(rekapKunjungan.per_role || []).length"></strong> role
                    </div>
                </div>

                <!-- Kolom Kanan (2 cols): Tabel Riwayat & Rekap Kunjungan -->
                <div class="lg:col-span-2 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div>
                        <!-- Header & Filter Tabs -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 mb-3">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Log Riil Kunjungan Sekolah & Perusahaan</h4>
                                <p class="text-[11px] text-slate-400 mt-0.5">Daftar kunjungan lapangan terkonsolidasi lintas role & institusi</p>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap">
                                <!-- Filter Jenis Kunjungan (Tabs) -->
                                <div class="inline-flex p-1 rounded-xl bg-slate-100 text-xs">
                                    <button 
                                        type="button"
                                        @click="kunjunganTab = 'all'"
                                        :class="kunjunganTab === 'all' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                        class="px-2.5 py-1 rounded-lg transition text-[11px] cursor-pointer"
                                    >
                                        Semua (<span x-text="rekapKunjungan.total_kunjungan || 0"></span>)
                                    </button>
                                    <button 
                                        type="button"
                                        @click="kunjunganTab = 'sekolah'"
                                        :class="kunjunganTab === 'sekolah' ? 'bg-white text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                        class="px-2.5 py-1 rounded-lg transition text-[11px] cursor-pointer"
                                    >
                                        Sekolah (<span x-text="rekapKunjungan.total_sekolah || 0"></span>)
                                    </button>
                                    <button 
                                        type="button"
                                        @click="kunjunganTab = 'perusahaan'"
                                        :class="kunjunganTab === 'perusahaan' ? 'bg-white text-purple-700 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                        class="px-2.5 py-1 rounded-lg transition text-[11px] cursor-pointer"
                                    >
                                        Perusahaan (<span x-text="rekapKunjungan.total_perusahaan || 0"></span>)
                                    </button>
                                </div>

                                <!-- Filter Role Petugas Dropdown -->
                                <select 
                                    x-model="kunjunganRoleFilter"
                                    class="px-2 py-1 bg-slate-50 border border-slate-200 text-slate-700 rounded-lg text-[11px] font-medium focus:outline-none focus:ring-1 focus:ring-sky-500"
                                >
                                    <option value="all">Semua Role</option>
                                    <template x-for="r in (rekapKunjungan.per_role || [])" :key="'opt-' + r.role">
                                        <option :value="r.role" x-text="'Role: ' + r.role"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Table Feed -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-600">
                                <thead class="bg-slate-50/80 text-[10.5px] uppercase font-bold text-slate-400 border-b border-slate-100">
                                    <tr>
                                        <th class="py-2.5 px-3">Tanggal & Waktu</th>
                                        <th class="py-2.5 px-3">Institusi & Jenis</th>
                                        <th class="py-2.5 px-3">Petugas & Role</th>
                                        <th class="py-2.5 px-3">Tujuan / Potensi</th>
                                        <th class="py-2.5 px-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="item in filteredKunjunganList" :key="item.id">
                                        <tr class="hover:bg-slate-50/70 transition">
                                            <td class="py-2.5 px-3 whitespace-nowrap">
                                                <div class="font-bold text-slate-900" x-text="item.tanggal"></div>
                                                <div class="text-[10px] text-slate-400" x-text="item.waktu + ' WIB'"></div>
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <div class="font-semibold text-slate-900 truncate max-w-[200px]" x-text="item.nama_institusi"></div>
                                                <span 
                                                    class="inline-block px-1.5 py-0.5 rounded text-[9.5px] font-bold mt-0.5"
                                                    :class="item.jenis === 'Sekolah' ? 'bg-blue-50 text-blue-700 border border-blue-200/60' : 'bg-purple-50 text-purple-700 border border-purple-200/60'"
                                                    x-text="item.jenis"
                                                ></span>
                                            </td>
                                            <td class="py-2.5 px-3 whitespace-nowrap">
                                                <div class="font-medium text-slate-900" x-text="item.petugas_name"></div>
                                                <span 
                                                    class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold text-slate-600 bg-slate-100"
                                                    x-text="item.petugas_role"
                                                ></span>
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <div class="truncate max-w-[180px] text-slate-700" x-text="item.tujuan"></div>
                                                <div class="text-[10px] text-emerald-600 font-semibold" x-text="'Potensi: ' + item.potensi"></div>
                                            </td>
                                            <td class="py-2.5 px-3 whitespace-nowrap">
                                                <span 
                                                    class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                    :class="{
                                                        'bg-emerald-50 text-emerald-700 border border-emerald-200': ['Terverifikasi', 'Selesai'].includes(item.status),
                                                        'bg-amber-50 text-amber-700 border border-amber-200': item.status === 'Proses',
                                                        'bg-slate-100 text-slate-600 border border-slate-200': !['Terverifikasi', 'Selesai', 'Proses'].includes(item.status)
                                                    }"
                                                    x-text="item.status"
                                                ></span>
                                            </td>
                                        </tr>
                                    </template>

                                    <template x-if="filteredKunjunganList.length === 0">
                                        <tr>
                                            <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                                                Tidak ditemukan catatan kunjungan untuk filter tab atau role ini.
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-[11px] text-slate-400 mt-2">
                        <span>Menampilkan <strong class="text-slate-700" x-text="filteredKunjunganList.length"></strong> kunjungan</span>
                        <a href="{{ route('kunjungan.index') }}" class="text-sky-600 hover:text-sky-700 font-semibold hover:underline">
                            Lihat semua kunjungan &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- SECTION 7: KUALITAS DATA OPERASIONAL & SEGMENTASI              -->
        <!-- ============================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Kualitas Data & Audit Kebersihan Data -->
            <div class="crm-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>Integritas & Kualitas Data CRM</span>
                        </h3>
                        <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">Audit Read-Only</span>
                    </div>
                </div>

                <!-- Status Volume & Follow-Up Prospek (Klik untuk Lihat Data Terfilter) -->
                <div class="grid grid-cols-2 gap-3 mb-3 text-center">
                    <a 
                        :href="getProspekScopeUrl(null)" 
                        href="{{ route('analyst.prospek.index') }}"
                        class="group p-3 bg-blue-50/70 hover:bg-blue-100/80 active:bg-blue-100 rounded-xl border border-blue-200/80 hover:border-blue-300 transition duration-150 flex flex-col justify-between shadow-2xs hover:shadow-xs hover:-translate-y-0.5 cursor-pointer block"
                        title="Klik untuk melihat seluruh data prospek"
                    >
                        <div>
                            <div class="flex items-center justify-center gap-1">
                                <span class="text-[10px] font-bold text-blue-700 uppercase">Total Prospek</span>
                                <svg class="w-3 h-3 text-blue-500 opacity-60 group-hover:opacity-100 group-hover:translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </div>
                            <span class="text-xl font-black text-blue-950 block mt-0.5" x-text="Number(metrics.totalProspekUnik).toLocaleString('id-ID')">{{ number_format($totalProspekUnik) }}</span>
                        </div>
                        <span class="text-[9.5px] text-blue-600 block mt-1 font-semibold group-hover:underline flex items-center justify-center gap-0.5">Semua Lead &rarr;</span>
                    </a>
                    <a 
                        :href="getProspekScopeUrl('sudah_follow_up')" 
                        href="{{ route('analyst.prospek.index', ['scope' => 'sudah_follow_up']) }}"
                        class="group p-3 bg-emerald-50/70 hover:bg-emerald-100/80 active:bg-emerald-100 rounded-xl border border-emerald-200/80 hover:border-emerald-300 transition duration-150 flex flex-col justify-between shadow-2xs hover:shadow-xs hover:-translate-y-0.5 cursor-pointer block"
                        title="Klik untuk melihat data prospek yang sudah di-follow up"
                    >
                        <div>
                            <div class="flex items-center justify-center gap-1">
                                <span class="text-[10px] font-bold text-emerald-700 uppercase">Sudah Follow-Up</span>
                                <svg class="w-3 h-3 text-emerald-500 opacity-60 group-hover:opacity-100 group-hover:translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </div>
                            <span class="text-xl font-black text-emerald-950 block mt-0.5" x-text="Number(metrics.sudahFollowUpCount).toLocaleString('id-ID')">{{ number_format($sudahFollowUpCount) }}</span>
                        </div>
                        <span class="text-[9.5px] text-emerald-600 block mt-1 font-semibold group-hover:underline flex items-center justify-center gap-0.5">Lihat yang Sudah &rarr;</span>
                    </a>
                </div>

                <!-- Audit Kualitas Data CRM (Klik untuk Lihat Data Terfilter) -->
                <div class="grid grid-cols-3 gap-3 mb-4 text-center">
                    <a 
                        :href="getProspekScopeUrl('belum_follow_up')" 
                        href="{{ route('analyst.prospek.index', ['scope' => 'belum_follow_up']) }}"
                        class="group p-3 bg-amber-50/70 hover:bg-amber-100/80 active:bg-amber-100 rounded-xl border border-amber-200/80 hover:border-amber-300 transition duration-150 flex flex-col justify-between shadow-2xs hover:shadow-xs hover:-translate-y-0.5 cursor-pointer block"
                        title="Klik untuk melihat data prospek yang belum di-follow up"
                    >
                        <div>
                            <div class="flex items-center justify-center gap-1">
                                <span class="text-[10px] font-bold text-amber-700 uppercase">Belum Follow-Up</span>
                                <svg class="w-3 h-3 text-amber-500 opacity-60 group-hover:opacity-100 group-hover:translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </div>
                            <span class="text-xl font-black text-amber-950 block mt-0.5" x-text="Number(metrics.belumFollowUpCount).toLocaleString('id-ID')">{{ number_format($belumFollowUpCount) }}</span>
                        </div>
                        <span class="text-[9.5px] text-amber-600 block mt-1 font-semibold group-hover:underline flex items-center justify-center gap-0.5">Lihat Siapa Saja &rarr;</span>
                    </a>
                    <a 
                        :href="getProspekScopeUrl('duplikat_wa')" 
                        href="{{ route('analyst.prospek.index', ['scope' => 'duplikat_wa']) }}"
                        class="group p-3 bg-rose-50/70 hover:bg-rose-100/80 active:bg-rose-100 rounded-xl border border-rose-200/80 hover:border-rose-300 transition duration-150 flex flex-col justify-between shadow-2xs hover:shadow-xs hover:-translate-y-0.5 cursor-pointer block"
                        title="Klik untuk melihat nomor WhatsApp duplikat"
                    >
                        <div>
                            <div class="flex items-center justify-center gap-1">
                                <span class="text-[10px] font-bold text-rose-700 uppercase">Duplikat Nomor</span>
                                <svg class="w-3 h-3 text-rose-500 opacity-60 group-hover:opacity-100 group-hover:translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </div>
                            <span class="text-xl font-black text-rose-950 block mt-0.5" x-text="Number(metrics.duplicateWaCount).toLocaleString('id-ID')">{{ number_format($duplicateWaCount) }}</span>
                        </div>
                        <span class="text-[9.5px] text-rose-600 block mt-1 font-semibold group-hover:underline flex items-center justify-center gap-0.5">Nomor Berulang &rarr;</span>
                    </a>
                    <a 
                        :href="getProspekScopeUrl('incomplete')" 
                        href="{{ route('analyst.prospek.index', ['scope' => 'incomplete']) }}"
                        class="group p-3 bg-slate-50 hover:bg-slate-100/90 active:bg-slate-100 rounded-xl border border-slate-200/90 hover:border-slate-300 transition duration-150 flex flex-col justify-between shadow-2xs hover:shadow-xs hover:-translate-y-0.5 cursor-pointer block"
                        title="Klik untuk melihat data prospek yang belum lengkap"
                    >
                        <div>
                            <div class="flex items-center justify-center gap-1">
                                <span class="text-[10px] font-bold text-slate-500 uppercase">Data Belum Lengkap</span>
                                <svg class="w-3 h-3 text-slate-400 opacity-60 group-hover:opacity-100 group-hover:translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </div>
                            <span class="text-xl font-black text-slate-900 block mt-0.5" x-text="Number(metrics.incompleteDataCount).toLocaleString('id-ID')">{{ number_format($incompleteDataCount) }}</span>
                        </div>
                        <span class="text-[9.5px] text-slate-400 block mt-1 font-semibold group-hover:underline flex items-center justify-center gap-0.5">Tanpa Mitra/Prodi &rarr;</span>
                    </a>
                </div>

                <div>
                    <div class="text-[11px] font-bold text-slate-700 mb-2 uppercase tracking-wider">Aktivitas Follow-Up Terkini:</div>
                    <div class="space-y-2">
                        @forelse($recentFollowUps as $rf)
                            <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-slate-50 border border-slate-100">
                                <div>
                                    <span class="font-bold text-slate-900">{{ $rf->prospek?->name ?? 'Prospek' }}</span>
                                    <span class="text-[10px] text-slate-500 block">Metode: {{ $rf->metode }} • Oleh: {{ $rf->user?->name ?? 'Staff' }}</span>
                                </div>
                                <span class="text-[10px] text-slate-400">{{ $rf->created_at ? $rf->created_at->diffForHumans() : '-' }}</span>
                            </div>
                        @empty
                            <div class="text-center py-3 text-xs text-slate-400">Belum ada histori aktivitas follow-up.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Segmentasi Pendaftar (Domisili, Kelas, Jalur, Gelombang) -->
            <div class="crm-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Segmentasi Pendaftar</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Pengelompokan calon mahasiswa berdasarkan kelas, jalur, gelombang, dan wilayah</p>
                    </div>
                </div>

                <!-- Kelas & Jalur Grid -->
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Kelas Mahasiswa:</span>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-700">Reguler:</span>
                            <span class="font-extrabold text-slate-900" x-text="Number(segmentasiKelas.Reguler).toLocaleString('id-ID') + ' Calon'">{{ number_format($segmentasiKelas['Reguler']) }} Calon</span>
                        </div>
                        <div class="flex items-center justify-between text-xs mt-1">
                            <span class="font-semibold text-slate-700">Karyawan:</span>
                            <span class="font-extrabold text-slate-900" x-text="Number(segmentasiKelas.Karyawan).toLocaleString('id-ID') + ' Calon'">{{ number_format($segmentasiKelas['Karyawan']) }} Calon</span>
                        </div>
                    </div>
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Jalur Pendaftaran:</span>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-700">Melalui Sales:</span>
                            <span class="font-extrabold text-slate-900" x-text="Number(segmentasiJalur.sales).toLocaleString('id-ID') + ' Lead'">{{ number_format($segmentasiJalur['sales']) }} Lead</span>
                        </div>
                        <div class="flex items-center justify-between text-xs mt-1">
                            <span class="font-semibold text-slate-700">Mandiri / CS:</span>
                            <span class="font-extrabold text-slate-900" x-text="Number(segmentasiJalur.direct).toLocaleString('id-ID') + ' Lead'">{{ number_format($segmentasiJalur['direct']) }} Lead</span>
                        </div>
                    </div>
                </div>

                <!-- Segmentasi Gelombang Pendaftaran -->
                <template x-if="segmentasiGelombang.length > 0">
                    <div class="mb-4 p-3 bg-teal-50/60 rounded-xl border border-teal-100">
                        <span class="text-[10px] font-bold uppercase text-teal-700 block mb-1.5">Distribusi Gelombang Pendaftaran:</span>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                            <template x-for="gel in segmentasiGelombang" :key="gel.gelombang">
                                <div class="bg-white p-2 rounded-lg border border-teal-200/70">
                                    <span class="font-bold text-slate-800 block text-[11px]" x-text="gel.gelombang"></span>
                                    <span class="font-extrabold text-teal-700 text-sm" x-text="Number(gel.total).toLocaleString('id-ID') + ' Pendaftar'"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Distribusi Wilayah Domisili Top 5 -->
                <div>
                    <div class="text-[11px] font-bold text-slate-700 mb-2 uppercase tracking-wider">Distribusi Wilayah Domisili Pendaftar:</div>
                    <div class="space-y-2">
                        <template x-for="wil in segmentasiWilayah" :key="wil.nama_wilayah">
                            <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-slate-50 border border-slate-100">
                                <span class="font-semibold text-slate-800" x-text="wil.nama_wilayah"></span>
                                <span class="font-bold text-slate-900" x-text="Number(wil.total).toLocaleString('id-ID') + ' Calon'"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Alpine.js & Chart.js Controller Script -->
    <script>
        function analystDashboardApp(config) {
            return {
                loading: false,
                apiUrl: config.apiUrl,
                exportBaseUrl: config.exportBaseUrl,
                filters: config.initialFilters,
                metrics: config.initialMetrics,
                funnelData: config.initialFunnel,
                chartsData: config.initialCharts,
                periodComparison: config.initialPeriod,
                salesList: config.initialSales,
                sumberAnalysis: config.initialSumber,
                topChannel: config.initialTopChannel,
                topSekolah: config.initialTopSekolah,
                totalSekolah: config.initialTotalSekolah,
                totalPerusahaan: config.initialTotalPerusahaan || 0,
                sekolahDihubungiCount: config.initialSekolahDihubungi,
                rekapKunjungan: config.initialRekapKunjungan || { total_kunjungan: 0, total_sekolah: 0, total_perusahaan: 0, total_selesai: 0, per_role: [], list: [] },
                kunjunganTab: 'all',
                kunjunganRoleFilter: 'all',
                segmentasiProdi: config.initialSegProdi,
                segmentasiKelas: config.initialSegKelas,
                segmentasiJalur: config.initialSegJalur,
                segmentasiWilayah: config.initialSegWilayah,
                segmentasiGelombang: config.initialSegGelombang || [],
                charts: {},

                get filteredKunjunganList() {
                    if (!this.rekapKunjungan || !this.rekapKunjungan.list) return [];
                    return this.rekapKunjungan.list.filter(item => {
                        let matchTab = true;
                        if (this.kunjunganTab === 'sekolah') {
                            matchTab = (item.jenis || '').toLowerCase() === 'sekolah';
                        } else if (this.kunjunganTab === 'perusahaan') {
                            matchTab = (item.jenis || '').toLowerCase() === 'perusahaan';
                        }
                        let matchRole = true;
                        if (this.kunjunganRoleFilter !== 'all') {
                            matchRole = (item.petugas_role || '').toLowerCase() === this.kunjunganRoleFilter.toLowerCase();
                        }
                        return matchTab && matchRole;
                    });
                },

                getProspekScopeUrl(scope = null) {
                    try {
                        const url = new URL('{{ route('analyst.prospek.index') }}', window.location.origin);
                        const form = this.$refs.filterForm;
                        if (form) {
                            const formData = new FormData(form);
                            for (const [key, val] of formData.entries()) {
                                if (val && val !== 'all' && key !== '_token' && key !== 'ajax') {
                                    url.searchParams.set(key, val);
                                }
                            }
                        }
                        if (scope) {
                            url.searchParams.set('scope', scope);
                        } else {
                            url.searchParams.delete('scope');
                        }
                        return url.toString();
                    } catch(e) {
                        return '{{ route('analyst.prospek.index') }}' + (scope ? '?scope=' + scope : '');
                    }
                },

                initDashboard() {
                    this.$nextTick(() => {
                        this.renderCharts();
                    });
                },

                applyFilter(event) {
                    if (event) event.preventDefault();
                    this.loading = true;

                    const form = this.$refs.filterForm;
                    const formData = new FormData(form);
                    const params = new URLSearchParams(formData);
                    params.set('ajax', '1');

                    // Sinkronisasi URL browser tanpa reload
                    const cleanParams = new URLSearchParams(formData);
                    window.history.pushState({}, '', '?' + cleanParams.toString());

                    fetch(this.apiUrl + '?' + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.metrics = data.metrics;
                            this.funnelData = data.funnelData;
                            this.chartsData = data.chartsData;
                            this.periodComparison = data.periodComparison;
                            this.salesList = data.salesList;
                            this.sumberAnalysis = data.sumberAnalysis;
                            this.topChannel = data.topChannel;
                            this.topSekolah = data.topSekolah;
                            this.totalSekolah = data.totalSekolah;
                            this.totalPerusahaan = data.totalPerusahaan;
                            this.sekolahDihubungiCount = data.sekolahDihubungiCount;
                            this.rekapKunjungan = data.rekapKunjungan || { total_kunjungan: 0, total_sekolah: 0, total_perusahaan: 0, total_selesai: 0, per_role: [], list: [] };
                            this.segmentasiProdi = data.segmentasiProdi;
                            this.segmentasiKelas = data.segmentasiKelas;
                            this.segmentasiJalur = data.segmentasiJalur;
                            this.segmentasiWilayah = data.segmentasiWilayah;
                            this.segmentasiGelombang = data.segmentasiGelombang;

                            this.updateCharts();
                        }
                    })
                    .catch(err => {
                        console.error('Error saat memperbarui data dashboard realtime:', err);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
                },

                resetFilter() {
                    window.location.href = this.apiUrl;
                },

                getExportUrl(type) {
                    const form = this.$refs.filterForm;
                    const formData = form ? new FormData(form) : new FormData();
                    const params = new URLSearchParams(formData);
                    params.set('type', type);
                    return this.exportBaseUrl + '?' + params.toString();
                },

                renderCharts() {
                    if (typeof Chart === 'undefined') return;

                    // 1. Trend Chart
                    const trendCtx = document.getElementById('trendChartCanvas');
                    if (trendCtx) {
                        this.charts.trend = new Chart(trendCtx, {
                            type: 'line',
                            data: {
                                labels: this.chartsData.trend.labels,
                                datasets: [
                                    {
                                        label: 'Kontak Baru',
                                        data: this.chartsData.trend.kontak,
                                        borderColor: '#0284c7',
                                        backgroundColor: 'rgba(2, 132, 199, 0.1)',
                                        fill: true,
                                        tension: 0.35,
                                        borderWidth: 2,
                                        pointRadius: 3
                                    },
                                    {
                                        label: 'Formulir',
                                        data: this.chartsData.trend.formulir,
                                        borderColor: '#9333ea',
                                        backgroundColor: 'rgba(147, 51, 234, 0.1)',
                                        fill: true,
                                        tension: 0.35,
                                        borderWidth: 2,
                                        pointRadius: 3
                                    },
                                    {
                                        label: 'Maba Lunas',
                                        data: this.chartsData.trend.lunas,
                                        borderColor: '#059669',
                                        backgroundColor: 'rgba(5, 150, 105, 0.1)',
                                        fill: true,
                                        tension: 0.35,
                                        borderWidth: 2,
                                        pointRadius: 3
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 10, weight: 'bold' } } }
                                },
                                scales: {
                                    y: { beginAtZero: true, grid: { color: 'rgba(226, 232, 240, 0.6)' } },
                                    x: { grid: { display: false } }
                                }
                            }
                        });
                    }

                    // 2. Funnel Chart
                    const funnelCtx = document.getElementById('funnelChartCanvas');
                    if (funnelCtx) {
                        this.charts.funnel = new Chart(funnelCtx, {
                            type: 'bar',
                            data: {
                                labels: ['1. Lead Masuk', '2. Audiensi/Hangat', '3. Beli Formulir', '4. Pemberkasan', '5. Maba Lunas'],
                                datasets: [{
                                    label: 'Jumlah Prospek',
                                    data: this.chartsData.funnel.counts,
                                    backgroundColor: ['#3b82f6', '#6366f1', '#a855f7', '#f59e0b', '#10b981'],
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: (ctx) => ` ${ctx.raw.toLocaleString('id-ID')} Prospek`
                                        }
                                    }
                                },
                                scales: {
                                    x: { beginAtZero: true, grid: { color: 'rgba(226, 232, 240, 0.6)' } },
                                    y: { grid: { display: false } }
                                }
                            }
                        });
                    }

                    // 3. Prodi Chart
                    const prodiCtx = document.getElementById('prodiChartCanvas');
                    if (prodiCtx) {
                        const prodiLabels = this.segmentasiProdi.map(p => p.nama_prodi);
                        const prodiCounts = this.segmentasiProdi.map(p => p.total);
                        this.charts.prodi = new Chart(prodiCtx, {
                            type: 'doughnut',
                            data: {
                                labels: prodiLabels.length ? prodiLabels : ['Belum Ada Data'],
                                datasets: [{
                                    data: prodiCounts.length ? prodiCounts : [1],
                                    backgroundColor: ['#3b82f6', '#06b6d4', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#94a3b8'],
                                    borderWidth: 2,
                                    borderColor: '#ffffff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { position: 'right', labels: { boxWidth: 10, font: { size: 10 } } }
                                },
                                cutout: '65%'
                            }
                        });
                    }

                    // 4. Sales Chart
                    const salesCtx = document.getElementById('salesChartCanvas');
                    if (salesCtx) {
                        this.charts.sales = new Chart(salesCtx, {
                            type: 'bar',
                            data: {
                                labels: this.chartsData.sales.labels.length ? this.chartsData.sales.labels : ['Belum Ada Data'],
                                datasets: [
                                    {
                                        label: 'Formulir',
                                        data: this.chartsData.sales.formulir,
                                        backgroundColor: '#a855f7',
                                        borderRadius: 4
                                    },
                                    {
                                        label: 'Lunas Termin 1',
                                        data: this.chartsData.sales.lunas,
                                        backgroundColor: '#10b981',
                                        borderRadius: 4
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { position: 'top', labels: { boxWidth: 10, font: { size: 10, weight: 'bold' } } }
                                },
                                scales: {
                                    y: { beginAtZero: true, grid: { color: 'rgba(226, 232, 240, 0.6)' } },
                                    x: { grid: { display: false } }
                                }
                            }
                        });
                    }
                },

                updateCharts() {
                    if (!this.charts) return;

                    // Update Trend Chart
                    if (this.charts.trend) {
                        this.charts.trend.data.labels = this.chartsData.trend.labels;
                        this.charts.trend.data.datasets[0].data = this.chartsData.trend.kontak;
                        this.charts.trend.data.datasets[1].data = this.chartsData.trend.formulir;
                        this.charts.trend.data.datasets[2].data = this.chartsData.trend.lunas;
                        this.charts.trend.update();
                    }

                    // Update Funnel Chart
                    if (this.charts.funnel) {
                        this.charts.funnel.data.datasets[0].data = this.chartsData.funnel.counts;
                        this.charts.funnel.update();
                    }

                    // Update Prodi Chart
                    if (this.charts.prodi) {
                        const prodiLabels = this.segmentasiProdi.map(p => p.nama_prodi);
                        const prodiCounts = this.segmentasiProdi.map(p => p.total);
                        this.charts.prodi.data.labels = prodiLabels.length ? prodiLabels : ['Belum Ada Data'];
                        this.charts.prodi.data.datasets[0].data = prodiCounts.length ? prodiCounts : [1];
                        this.charts.prodi.update();
                    }

                    // Update Sales Chart
                    if (this.charts.sales) {
                        this.charts.sales.data.labels = this.chartsData.sales.labels.length ? this.chartsData.sales.labels : ['Belum Ada Data'];
                        this.charts.sales.data.datasets[0].data = this.chartsData.sales.formulir;
                        this.charts.sales.data.datasets[1].data = this.chartsData.sales.lunas;
                        this.charts.sales.update();
                    }
                }
            };
        }
    </script>
</x-app-layout>
