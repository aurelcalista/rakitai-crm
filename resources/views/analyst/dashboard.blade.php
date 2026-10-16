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
            initialSekolahDihubungi: {{ $sekolahDihubungiCount }},
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
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white px-5 py-4 sm:px-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Halo, {{ auth()->user()->name ?? 'Data Analyst' }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">Data Analyst PMB</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">TA {{ $activeTa }}</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Akses Global Read-Only</span>
                    <span x-show="loading" x-cloak class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 animate-pulse flex items-center gap-1">
                        <svg class="animate-spin h-3 w-3 text-amber-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Sinkronisasi Data...
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">Workspace analisis menyeluruh PMB: Evaluasi funnel, konversi pendaftar, pencapaian target, dan performa tim secara dinamis & realtime.</p>
            </div>

            <!-- Quick Action Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Dropdown Ekspor Excel (Link Dinamis Sesuai Filter Aktif) -->
                <div x-data="{ openExport: false }" class="relative">
                    <button 
                        @click="openExport = !openExport" 
                        class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition shadow-xs flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-emerald-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Ekspor Excel (.xlsx)</span>
                        <svg class="w-3.5 h-3.5 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div 
                        x-show="openExport" 
                        @click.away="openExport = false" 
                        x-transition 
                        class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-lg border border-slate-200 py-1.5 z-30 text-xs text-slate-700"
                    >
                        <div class="px-3 py-1.5 text-[10px] font-bold uppercase text-slate-400 border-b border-slate-100">Pilih Kategori Ekspor Excel</div>
                        <a :href="getExportUrl('prospek')" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span>Data Prospek & Gelombang</span>
                        </a>
                        <a :href="getExportUrl('funnel')" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                            <span>Rekapitulasi Funnel PMB</span>
                        </a>
                        <a :href="getExportUrl('transaksi')" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Transaksi Pembayaran Valid</span>
                        </a>
                        <a :href="getExportUrl('performa_sales')" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            <span>Performa Sales & SPV</span>
                        </a>
                        <a :href="getExportUrl('sumber')" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>Sumber & Kanal Marketing</span>
                        </a>
                        <a :href="getExportUrl('sekolah')" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span>Sekolah & Perusahaan Mitra</span>
                        </a>
                        <a :href="getExportUrl('target')" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                            <span>Target vs Realisasi</span>
                        </a>
                        <div class="border-t border-slate-100 my-1"></div>
                        <a :href="getExportUrl('follow_up')" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                            <span>Detail Riwayat Follow-Up</span>
                        </a>
                        <a :href="getExportUrl('kualitas_data')" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span>Audit Kualitas Data</span>
                        </a>
                    </div>
                </div>

                <a href="{{ route('analyst.prospek.index') }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition flex items-center gap-1.5 border border-slate-200">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    <span>Eksplorasi Prospek</span>
                </a>

                <button 
                    type="button" 
                    @click="window.print()"
                    class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition flex items-center gap-1.5 shadow-xs cursor-pointer"
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
            class="no-print bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs transition-opacity"
            :class="{ 'opacity-70': loading }"
        >
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Filter Analisis Terpadu (Realtime Dinamis)</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="resetFilter" class="text-[11px] font-semibold text-rose-600 hover:text-rose-700 hover:underline cursor-pointer">Reset Filter</button>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-9 gap-3 text-xs">
                <!-- Periode Cepat -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Periode</label>
                    <select name="periode" @change="applyFilter" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="semua" {{ ($filters['periode'] ?? '') === 'semua' ? 'selected' : '' }}>Semua Waktu</option>
                        <option value="hari_ini" {{ ($filters['periode'] ?? '') === 'hari_ini' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="kemarin" {{ ($filters['periode'] ?? '') === 'kemarin' ? 'selected' : '' }}>Kemarin</option>
                        <option value="minggu_ini" {{ ($filters['periode'] ?? '') === 'minggu_ini' ? 'selected' : '' }}>Minggu Ini</option>
                        <option value="bulan_ini" {{ ($filters['periode'] ?? '') === 'bulan_ini' ? 'selected' : '' }}>Bulan Ini</option>
                        <option value="tahunan" {{ ($filters['periode'] ?? '') === 'tahunan' ? 'selected' : '' }}>Tahun Berjalan</option>
                    </select>
                </div>

                <!-- Tahun Akademik -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Tahun Akademik</label>
                    <select name="ta" @change="applyFilter" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua TA</option>
                        @foreach($taOptions as $t)
                            <option value="{{ $t->nama }}" {{ ($filters['ta'] ?? '') === $t->nama ? 'selected' : '' }}>{{ $t->nama }} {{ $t->status === 'Aktif' ? '(Aktif)' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Wilayah -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Wilayah</label>
                    <select name="wilayah_id" @change="applyFilter" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua Wilayah</option>
                        @foreach($wilayahOptions as $w)
                            <option value="{{ $w->id }}" {{ ($filters['wilayah_id'] ?? '') == $w->id ? 'selected' : '' }}>{{ $w->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Prodi -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Program Studi</label>
                    <select name="prodi_id" @change="applyFilter" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua Prodi</option>
                        @foreach($prodiOptions as $p)
                            <option value="{{ $p->id }}" {{ ($filters['prodi_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Kelas -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Kelas</label>
                    <select name="kelas" @change="applyFilter" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua Kelas</option>
                        <option value="Reguler" {{ ($filters['kelas'] ?? '') === 'Reguler' ? 'selected' : '' }}>Reguler</option>
                        <option value="Karyawan" {{ ($filters['kelas'] ?? '') === 'Karyawan' ? 'selected' : '' }}>Karyawan</option>
                    </select>
                </div>

                <!-- Gelombang Pendaftaran -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Gelombang</label>
                    <select name="gelombang" @change="applyFilter" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua Gelombang</option>
                        @foreach($gelombangOptions as $g)
                            <option value="{{ $g->nama }}" {{ ($filters['gelombang'] ?? '') === $g->nama ? 'selected' : '' }}>{{ $g->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sumber Informasi -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Sumber Lead</label>
                    <select name="source" @change="applyFilter" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua Sumber</option>
                        @foreach(\App\Models\Prospek::SOURCES as $src)
                            <option value="{{ $src }}" {{ ($filters['source'] ?? '') === $src ? 'selected' : '' }}>{{ $src }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Lead -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Status Lead</label>
                    <select name="status" @change="applyFilter" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
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
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Sales Handler</label>
                    <select name="sales_id" @change="applyFilter" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua Sales</option>
                        @foreach($salesOptions as $s)
                            <option value="{{ $s->id }}" {{ ($filters['sales_id'] ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Custom Date Range Picker & Submit Action -->
            <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[11px] font-bold text-slate-500">Rentang Tanggal Kustom:</span>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs">
                    <span class="text-slate-400 text-xs">s/d</span>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs">
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs transition cursor-pointer">Terapkan Tanggal</button>
                </div>
                <div class="text-[11px] text-slate-400 font-medium flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Filter otomatis tersinkron ke visual grafik dan tabel di bawah</span>
                </div>
            </div>
        </form>

        <!-- ============================================================== -->
        <!-- SECTION 1: RINGKASAN EKSEKUTIF PENCAPAIAN PMB                  -->
        <!-- ============================================================== -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-sky-600"></span>
                    <span>1. Ringkasan Eksekutif & Konversi PMB</span>
                </h3>
                <span class="text-xs text-slate-400">Data Terupdate Realtime</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
                <div class="crm-stat-card p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                    <span class="text-[11px] font-semibold text-slate-500 block">Total Kontak Unik</span>
                    <div class="text-2xl font-black text-slate-900 mt-1" x-text="Number(metrics.totalProspekUnik).toLocaleString('id-ID')">{{ number_format($totalProspekUnik) }}</div>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Seluruh Lead Terdata</span>
                </div>

                <div class="crm-stat-card p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                    <span class="text-[11px] font-semibold text-sky-600 block">Kontak Baru</span>
                    <div class="text-2xl font-black text-slate-900 mt-1" x-text="Number(metrics.kontakBaruCount).toLocaleString('id-ID')">{{ number_format($kontakBaruCount) }}</div>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Periode Terpilih</span>
                </div>

                <div class="crm-stat-card p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                    <span class="text-[11px] font-semibold text-purple-600 block">Beli Formulir</span>
                    <div class="text-2xl font-black text-purple-900 mt-1" x-text="Number(metrics.formulirCount).toLocaleString('id-ID')">{{ number_format($formulirCount) }}</div>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Pendaftar Terverifikasi</span>
                </div>

                <div class="crm-stat-card p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                    <span class="text-[11px] font-semibold text-emerald-600 block">Maba Lunas Tahap 1</span>
                    <div class="text-2xl font-black text-emerald-900 mt-1" x-text="Number(metrics.lunasCount).toLocaleString('id-ID')">{{ number_format($lunasCount) }}</div>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Mahasiswa Resmi</span>
                </div>

                <div class="crm-stat-card p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                    <span class="text-[11px] font-semibold text-indigo-600 block">Konversi Kontak → Lunas</span>
                    <div class="text-2xl font-black text-indigo-900 mt-1" x-text="metrics.convKontakLunas + '%'">{{ $convKontakLunas }}%</div>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Rasio Sukses PMB</span>
                </div>

                <div class="crm-stat-card p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                    <span class="text-[11px] font-semibold text-teal-600 block">Total Omset Valid</span>
                    <div class="text-2xl font-black text-teal-900 mt-1" x-text="'Rp ' + (Number(metrics.totalRevenueValid) / 1000000).toFixed(1) + ' Jt'">Rp {{ number_format($totalRevenueValid / 1000000, 1) }} Jt</div>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Formulir + Termin 1</span>
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
        <div class="crm-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 mb-5 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Funnel Konversi 5 Tahap PMB UCIC</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200">Baku Standar PMB</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Penelusuran perjalanan calon mahasiswa dari kontak awal, formulir, berkas, hingga pelunasan Termin 1.</p>
                </div>
                <div class="text-right text-xs">
                    <span class="text-slate-400">Drop-off / Batal Total:</span>
                    <span class="font-extrabold text-rose-600 ml-1" x-text="Number(metrics.lostCount).toLocaleString('id-ID') + ' Calon'">{{ number_format($lostCount) }} Calon</span>
                </div>
            </div>

            <!-- Visual Funnel Stage Bars -->
            <div class="space-y-4">
                <template x-for="(f, idx) in funnelData" :key="idx">
                    <div class="bg-slate-50/70 p-3.5 rounded-xl border border-slate-200/70">
                        <div class="flex items-center justify-between mb-1.5">
                            <div>
                                <span class="font-extrabold text-xs text-slate-900" x-text="f.stage"></span>
                                <span class="text-[11px] text-slate-500 ml-2 hidden sm:inline" x-text="f.sub"></span>
                            </div>
                            <div class="text-right">
                                <span class="font-black text-sm text-slate-900" x-text="Number(f.count).toLocaleString('id-ID')"></span>
                                <span class="text-xs font-semibold text-slate-500 ml-1" x-text="'(' + f.pct_total + '%)'"></span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-slate-200/80 rounded-full h-3 overflow-hidden">
                            <div 
                                class="h-3 rounded-full transition-all duration-700 ease-out shadow-xs" 
                                :class="'bg-' + f.color + '-500'"
                                :style="'width: ' + f.pct_total + '%'"
                            ></div>
                        </div>

                        <template x-if="f.drop_count > 0">
                            <div class="mt-1.5 flex items-center justify-between text-[10px] text-slate-400 font-medium">
                                <span>Status Berhenti / Tidak Merespons pada tahap ini:</span>
                                <span class="font-bold text-rose-500" x-text="Number(f.drop_count).toLocaleString('id-ID') + ' prospek'"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <!-- Conversion Ratios Summary -->
            <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-3 text-center">
                <div class="p-3 bg-blue-50/60 rounded-xl border border-blue-100">
                    <span class="text-[11px] text-blue-700 font-semibold block">Kontak → Beli Formulir</span>
                    <span class="text-xl font-extrabold text-blue-900" x-text="metrics.convKontakFormulir + '%'">{{ $convKontakFormulir }}%</span>
                </div>
                <div class="p-3 bg-purple-50/60 rounded-xl border border-purple-100">
                    <span class="text-[11px] text-purple-700 font-semibold block">Beli Formulir → Lunas Termin 1</span>
                    <span class="text-xl font-extrabold text-purple-900" x-text="metrics.convFormulirLunas + '%'">{{ $convFormulirLunas }}%</span>
                </div>
                <div class="p-3 bg-emerald-50/60 rounded-xl border border-emerald-100">
                    <span class="text-[11px] text-emerald-700 font-semibold block">Kontak → Lunas Maba Resmi</span>
                    <span class="text-xl font-extrabold text-emerald-900" x-text="metrics.convKontakLunas + '%'">{{ $convKontakLunas }}%</span>
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
        <div class="crm-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Evaluasi Performa Tim Sales & Penugasan</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200" x-text="salesList.length + ' Personel'">{{ count($salesList) }} Personel</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Analisis kuantitas kontak, intensitas follow-up, waktu respon awal, dan rasio konversi per Sales.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] border-b border-slate-200/70">
                        <tr>
                            <th class="py-2.5 px-3">Sales Personel</th>
                            <th class="py-2.5 px-3">Supervisor (SPV)</th>
                            <th class="py-2.5 px-3">Wilayah</th>
                            <th class="py-2.5 px-3 text-center">Kontak Handled</th>
                            <th class="py-2.5 px-3 text-center">Aktivitas Follow-Up</th>
                            <th class="py-2.5 px-3 text-center">Respon Pertama</th>
                            <th class="py-2.5 px-3 text-center">Formulir</th>
                            <th class="py-2.5 px-3 text-center">Closing / Lunas</th>
                            <th class="py-2.5 px-3 text-center">Rasio Konversi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="sales in salesList" :key="sales.id">
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-900" x-text="sales.name"></div>
                                    <div class="text-[10px] text-slate-400 font-mono" x-text="sales.kode"></div>
                                </td>
                                <td class="py-3 px-3 text-slate-600" x-text="sales.spv"></td>
                                <td class="py-3 px-3 text-slate-600" x-text="sales.wilayah"></td>
                                <td class="py-3 px-3 text-center font-bold text-slate-800" x-text="Number(sales.total_kontak).toLocaleString('id-ID')"></td>
                                <td class="py-3 px-3 text-center text-slate-700" x-text="Number(sales.follow_up_count).toLocaleString('id-ID')"></td>
                                <td class="py-3 px-3 text-center">
                                    <template x-if="sales.avg_response_hours !== null">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold" :class="sales.avg_response_hours <= 2 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'" x-text="sales.avg_response_hours + ' Jam'"></span>
                                    </template>
                                    <template x-if="sales.avg_response_hours === null">
                                        <span class="text-slate-400 text-[10px]">-</span>
                                    </template>
                                </td>
                                <td class="py-3 px-3 text-center font-semibold text-purple-700" x-text="Number(sales.formulir_count).toLocaleString('id-ID')"></td>
                                <td class="py-3 px-3 text-center font-extrabold text-emerald-700" x-text="Number(sales.lunas_count).toLocaleString('id-ID')"></td>
                                <td class="py-3 px-3 text-center">
                                    <span class="font-extrabold" :class="sales.konversi >= 10 ? 'text-emerald-600' : (sales.konversi >= 5 ? 'text-blue-600' : 'text-slate-600')" x-text="sales.konversi + '%'"></span>
                                </td>
                            </tr>
                        </template>
                        <template x-if="salesList.length === 0">
                            <tr>
                                <td colspan="9" class="text-center py-6 text-slate-400">Belum ada data sales yang terdaftar pada filter ini.</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
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

                <div class="grid grid-cols-3 gap-3 mb-4 text-center">
                    <div class="p-3 bg-amber-50/70 rounded-xl border border-amber-100">
                        <span class="text-[10px] font-bold text-amber-700 uppercase block">Belum Follow-Up</span>
                        <span class="text-xl font-black text-amber-950" x-text="Number(metrics.belumFollowUpCount).toLocaleString('id-ID')">{{ number_format($belumFollowUpCount) }}</span>
                        <span class="text-[9.5px] text-amber-600 block mt-0.5">Perlu Tindak Lanjut</span>
                    </div>
                    <div class="p-3 bg-rose-50/70 rounded-xl border border-rose-100">
                        <span class="text-[10px] font-bold text-rose-700 uppercase block">Duplikat Nomor</span>
                        <span class="text-xl font-black text-rose-950" x-text="Number(metrics.duplicateWaCount).toLocaleString('id-ID')">{{ number_format($duplicateWaCount) }}</span>
                        <span class="text-[9.5px] text-rose-600 block mt-0.5">Nomor WA Berulang</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                        <span class="text-[10px] font-bold text-slate-500 uppercase block">Data Belum Lengkap</span>
                        <span class="text-xl font-black text-slate-900" x-text="Number(metrics.incompleteDataCount).toLocaleString('id-ID')">{{ number_format($incompleteDataCount) }}</span>
                        <span class="text-[9.5px] text-slate-400 block mt-0.5">Tanpa Prodi / Mitra</span>
                    </div>
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
                segmentasiProdi: config.initialSegProdi,
                segmentasiKelas: config.initialSegKelas,
                segmentasiJalur: config.initialSegJalur,
                segmentasiWilayah: config.initialSegWilayah,
                segmentasiGelombang: config.initialSegGelombang || [],
                charts: {},

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
