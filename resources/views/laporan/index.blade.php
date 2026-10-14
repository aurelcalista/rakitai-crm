@php
    $pageTitle = 'Laporan Inbound & Rekapitulasi';
    $pageSubtitle = 'Export Data, Analisis Konversi & Rekap Pendaftaran';
@endphp

<x-app-layout :title="'Laporan - CRM UCIC'">

    <div x-data="{
        currentPage: 1,
        perPage: 25,
        selectedPeriode: 'semua',
        selectedStatus: 'all',
        selectedSales: 'all',
        selectedCs: 'all',
        tsInstance: null,
        initTomSelect() {
            this.$nextTick(() => {
                if (typeof TomSelect !== 'undefined' && this.$refs.periodeSelect && !this.tsInstance) {
                    this.tsInstance = new TomSelect(this.$refs.periodeSelect, {
                        create: false,
                        maxItems: 1,
                        allowEmptyOption: false,
                        placeholder: 'Ketik untuk cari periode...',
                        plugins: ['dropdown_input'],
                        sortField: [{ field: '$order' }],
                        onChange: (value) => {
                            this.selectedPeriode = value;
                        }
                    });
                    this.tsInstance.setValue(this.selectedPeriode, true);
                }
            });
        },
        prospectsList: {{ json_encode($prospects) }},
        periodLabels: {
            'semua': 'Semua Periode',
            'hari-ini': 'Hari Ini',
            'kemarin': 'Kemarin',
            'minggu-ini': 'Minggu Ini',
            'minggu-lalu': 'Minggu Lalu',
            'bulan-ini': 'Bulan Ini (Oktober 2026)',
            'bulan-lalu': 'Bulan Lalu (September 2026)',
            'jan': 'Bulan Januari',
            'feb': 'Bulan Februari',
            'mar': 'Bulan Maret',
            'apr': 'Bulan April',
            'mei': 'Bulan Mei',
            'jun': 'Bulan Juni',
            'jul': 'Bulan Juli',
            'agu': 'Bulan Agustus',
            'sep': 'Bulan September',
            'okt': 'Bulan Oktober',
            'nov': 'Bulan November',
            'des': 'Bulan Desember',
            'tahun-ini': 'Tahun 2026 (Tahun Ini)',
            'tahun-lalu': 'Tahun 2025 (Tahun Lalu)',
            'ta-2026-2027': 'Tahun Akademik 2026/2027'
        },
        get salesOptions() {
            const set = new Set();
            this.prospectsList.forEach(p => {
                const name = p.takeover_sales || p.sales_name;
                if (name && name !== '-') set.add(name);
            });
            return Array.from(set).sort();
        },
        get csOptions() {
            const set = new Set();
            this.prospectsList.forEach(p => {
                const name = p.takeover_cs;
                if (name && name !== '-') set.add(name);
            });
            return Array.from(set).sort();
        },
        filterByPeriod(p) {
            if (this.selectedPeriode === 'semua' || !this.selectedPeriode) return true;
            if (!p.created_at_raw && !p.created_at) return true;

            const pDate = p.created_at_raw ? new Date(p.created_at_raw) : new Date(p.created_at);
            if (isNaN(pDate.getTime())) return true;

            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

            switch (this.selectedPeriode) {
                case 'hari-ini':
                    return pDate >= today;
                case 'kemarin': {
                    const yest = new Date(today);
                    yest.setDate(today.getDate() - 1);
                    return pDate >= yest && pDate < today;
                }
                case 'minggu-ini': {
                    const day = (today.getDay() + 6) % 7;
                    const monday = new Date(today);
                    monday.setDate(today.getDate() - day);
                    return pDate >= monday;
                }
                case 'minggu-lalu': {
                    const day = (today.getDay() + 6) % 7;
                    const monday = new Date(today);
                    monday.setDate(today.getDate() - day);
                    const lastMonday = new Date(monday);
                    lastMonday.setDate(monday.getDate() - 7);
                    return pDate >= lastMonday && pDate < monday;
                }
                case 'bulan-ini':
                    return pDate.getFullYear() === now.getFullYear() && pDate.getMonth() === now.getMonth();
                case 'bulan-lalu': {
                    const prevM = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                    return pDate.getFullYear() === prevM.getFullYear() && pDate.getMonth() === prevM.getMonth();
                }
                case 'jan': return pDate.getMonth() === 0;
                case 'feb': return pDate.getMonth() === 1;
                case 'mar': return pDate.getMonth() === 2;
                case 'apr': return pDate.getMonth() === 3;
                case 'mei': return pDate.getMonth() === 4;
                case 'jun': return pDate.getMonth() === 5;
                case 'jul': return pDate.getMonth() === 6;
                case 'agu': return pDate.getMonth() === 7;
                case 'sep': return pDate.getMonth() === 8;
                case 'okt': return pDate.getMonth() === 9;
                case 'nov': return pDate.getMonth() === 10;
                case 'des': return pDate.getMonth() === 11;
                case 'tahun-ini':
                    return pDate.getFullYear() === now.getFullYear();
                case 'tahun-lalu':
                    return pDate.getFullYear() === (now.getFullYear() - 1);
                case 'ta-2026-2027': {
                    const startTA = new Date(2026, 6, 1);
                    const endTA = new Date(2027, 6, 31);
                    return pDate >= startTA && pDate <= endTA;
                }
                default:
                    return true;
            }
        },
        get filteredProspects() {
            return this.prospectsList.filter(p => {
                const matchPeriod = this.filterByPeriod(p);
                const matchStatus = this.selectedStatus === 'all' || 
                    (this.selectedStatus === 'Closing' ? (p.status === 'LUNAS' || p.status === 'Closing') : 
                    (this.selectedStatus === 'Lost' ? (p.status === 'DINGIN' || p.status === 'Lost' || p.status === 'NO RESPON') : 
                    p.status === this.selectedStatus));
                const matchSales = this.selectedSales === 'all' || (p.takeover_sales === this.selectedSales || p.sales_name === this.selectedSales);
                const matchCs = this.selectedCs === 'all' || p.takeover_cs === this.selectedCs;
                return matchPeriod && matchStatus && matchSales && matchCs;
            });
        },
        get paginatedProspects() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredProspects.slice(start, start + this.perPage);
        },
        get totalPages() {
            return Math.ceil(this.filteredProspects.length / this.perPage) || 1;
        },
        get summaryStats() {
            const list = this.filteredProspects;
            const total = list.length;
            const closing = list.filter(p => p.status === 'LUNAS' || p.status === 'Closing').length;
            const lost = list.filter(p => p.status === 'DINGIN' || p.status === 'Lost' || p.status === 'NO RESPON').length;
            const active = total - closing - lost;
            const rate = total > 0 ? ((closing / total) * 100).toFixed(1) : '0';
            return {
                total: total,
                active: active < 0 ? 0 : active,
                closing: closing,
                lost: lost,
                conversion_rate: rate + '%'
            };
        },
        formatDate(dateStr) {
            if (!dateStr) return '-';
            if (typeof dateStr === 'string' && /^\d{2}-\d{2}-\d{4}/.test(dateStr)) {
                return dateStr;
            }
            try {
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return dateStr;
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = d.getFullYear();
                return `${day}-${month}-${year}`;
            } catch (e) {
                return dateStr;
            }
        }
    }" x-init="initTomSelect(); $watch('selectedPeriode', val => { if (tsInstance && tsInstance.getValue() !== val) tsInstance.setValue(val, true); })" x-effect="selectedPeriode; selectedStatus; selectedSales; selectedCs; currentPage = 1">

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
                    <div class="w-20 h-16 shrink-0 flex items-center justify-center">
                        <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" class="max-w-full max-h-full object-contain">
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
                <p class="text-xs text-slate-600 mt-1">Periode Laporan: <strong x-text="periodLabels[selectedPeriode] || 'Semua Periode'">Semua Periode</strong> (Tahun Akademik 2026/2027)</p>
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
            <div class="no-print crm-card bg-white p-4 sm:p-5 space-y-3.5 text-xs">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="w-full sm:w-auto">
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Periode</label>
                        <div class="relative min-w-[260px]">
                            <select x-ref="periodeSelect" class="tom-select-periode">
                                <option value="semua">Semua Periode (Semua Waktu)</option>

                                <optgroup label="── Per Hari (Harian) ──">
                                    <option value="hari-ini">Hari Ini</option>
                                    <option value="kemarin">Kemarin</option>
                                </optgroup>

                                <optgroup label="── Per Minggu (Mingguan) ──">
                                    <option value="minggu-ini">Minggu Ini</option>
                                    <option value="minggu-lalu">Minggu Lalu</option>
                                </optgroup>

                                <optgroup label="── Per Bulan (Bulanan) ──">
                                    <option value="bulan-ini">Bulan Ini (Oktober 2026)</option>
                                    <option value="bulan-lalu">Bulan Lalu (September 2026)</option>
                                    <option value="jan">Januari</option>
                                    <option value="feb">Februari</option>
                                    <option value="mar">Maret</option>
                                    <option value="apr">April</option>
                                    <option value="mei">Mei</option>
                                    <option value="jun">Juni</option>
                                    <option value="jul">Juli</option>
                                    <option value="agu">Agustus</option>
                                    <option value="sep">September</option>
                                    <option value="okt">Oktober</option>
                                    <option value="nov">November</option>
                                    <option value="des">Desember</option>
                                </optgroup>

                                <optgroup label="── Per Tahun (Tahunan) ──">
                                    <option value="tahun-ini">Tahun 2026 (Tahun Ini)</option>
                                    <option value="tahun-lalu">Tahun 2025 (Tahun Lalu)</option>
                                    <option value="ta-2026-2027">Tahun Akademik 2026/2027</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Filter Sales</label>
                        <select x-model="selectedSales" class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="all">Semua Sales</option>
                            <template x-for="s in salesOptions" :key="s">
                                <option :value="s" x-text="s"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Filter CS</label>
                        <select x-model="selectedCs" class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="all">Semua CS</option>
                            <template x-for="c in csOptions" :key="c">
                                <option :value="c" x-text="c"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Status Prospek</label>
                        <select x-model="selectedStatus" class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="all">Semua Status</option>
                            <option value="Closing">Closing Only</option>
                            <option value="FORMULIR">Formulir</option>
                            <option value="BERKAS">Berkas</option>
                            <option value="PROSPEK">Prospek</option>
                            <option value="Lost">Lost Only</option>
                        </select>
                    </div>
                </div>

                <!-- Pilihan Cepat Tombol Periode -->
                <div class="flex items-center justify-between gap-2 pt-2.5 border-t border-slate-100 flex-wrap">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mr-1">Pilihan Cepat:</span>
                        <button type="button" @click="selectedPeriode = 'semua'" :class="selectedPeriode === 'semua' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer">Semua</button>
                        <button type="button" @click="selectedPeriode = 'hari-ini'" :class="selectedPeriode === 'hari-ini' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer">Per Hari</button>
                        <button type="button" @click="selectedPeriode = 'minggu-ini'" :class="selectedPeriode === 'minggu-ini' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer">Per Minggu</button>
                        <button type="button" @click="selectedPeriode = 'bulan-ini'" :class="selectedPeriode === 'bulan-ini' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer">Per Bulan</button>
                        <button type="button" @click="selectedPeriode = 'tahun-ini'" :class="selectedPeriode === 'tahun-ini' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer">Per Tahun</button>
                    </div>
                    <button type="button" @click="selectedPeriode = 'semua'; selectedStatus = 'all'; selectedSales = 'all'; selectedCs = 'all'; currentPage = 1;" class="text-xs text-slate-500 hover:text-slate-800 font-semibold underline underline-offset-2 cursor-pointer">
                        Reset Filter
                    </button>
                </div>
            </div>

            <!-- Summary KPIs (Screen View) -->
            <div class="no-print grid grid-cols-2 sm:grid-cols-5 gap-4">
                <div class="crm-card crm-card-hover p-5 relative overflow-hidden bg-white">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Total Prospek</p>
                    <h3 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900" x-text="summaryStats.total">{{ $summary['total_prospek'] }}</h3>
                    <p class="text-xs text-slate-500 mt-1.5 font-medium">Periode Dipilih</p>
                </div>
                <div class="crm-card crm-card-hover p-5 relative overflow-hidden bg-white">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Active Lead</p>
                    <h3 class="text-2xl sm:text-3xl font-bold tracking-tight text-indigo-600" x-text="summaryStats.active">{{ $summary['active'] }}</h3>
                    <p class="text-xs text-slate-500 mt-1.5 font-medium">Dalam Proses</p>
                </div>
                <div class="crm-card crm-card-hover p-5 relative overflow-hidden bg-white">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Total Closing</p>
                    <h3 class="text-2xl sm:text-3xl font-bold tracking-tight text-emerald-600" x-text="summaryStats.closing">{{ $summary['closing'] ?? $summary['LUNAS'] ?? 0 }}</h3>
                    <p class="text-xs text-slate-500 mt-1.5 font-medium">Mahasiswa Resmi</p>
                </div>
                <div class="crm-card crm-card-hover p-5 relative overflow-hidden bg-white">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Total Lost</p>
                    <h3 class="text-2xl sm:text-3xl font-bold tracking-tight text-rose-600" x-text="summaryStats.lost">{{ $summary['lost'] ?? $summary['DINGIN'] ?? 0 }}</h3>
                    <p class="text-xs text-slate-500 mt-1.5 font-medium">Historis Arsip</p>
                </div>
                <div class="crm-card crm-card-hover p-5 relative overflow-hidden bg-white">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Conversion Rate</p>
                    <h3 class="text-2xl sm:text-3xl font-bold tracking-tight text-purple-600" x-text="summaryStats.conversion_rate">{{ $summary['conversion_rate'] }}%</h3>
                    <p class="text-xs text-slate-500 mt-1.5 font-medium">Rasio Keberhasilan</p>
                </div>
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
                            <td class="py-2.5 px-2 border border-slate-400"><span x-text="summaryStats.total">{{ $summary['total_prospek'] }}</span> Prospek</td>
                            <td class="py-2.5 px-2 border border-slate-400 text-blue-700"><span x-text="summaryStats.active">{{ $summary['active'] }}</span> Lead</td>
                            <td class="py-2.5 px-2 border border-slate-400 text-emerald-700"><span x-text="summaryStats.closing">{{ $summary['closing'] ?? $summary['Closing (Lunas)'] ?? 0 }}</span> Mhs</td>
                            <td class="py-2.5 px-2 border border-slate-400 text-rose-700"><span x-text="summaryStats.lost">{{ $summary['lost'] }}</span> Lead</td>
                            <td class="py-2.5 px-2 border border-slate-400 text-purple-700"><span x-text="summaryStats.conversion_rate">{{ $summary['conversion_rate'] }}%</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Report Data Table -->
            <div class="crm-card bg-white overflow-hidden border border-slate-200/80 rounded-2xl">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Tabel Rekapitulasi Data Prospek</h3>
                    <span class="text-[11px] text-slate-500 font-medium">Total: <span x-text="filteredProspects.length"></span> Data</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs print:border print:border-slate-400">
                        <thead>
                            <tr class="bg-slate-100/80 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200 print:bg-slate-200 print:text-slate-900">
                                <th class="py-3 px-3 w-10 text-center print:border print:border-slate-400">No</th>
                                <th class="py-3 px-4 print:border print:border-slate-400">Nama Calon Mahasiswa Baru (Camaba)</th>
                                <th class="py-3 px-3 print:border print:border-slate-400">Sumber Informasi</th>
                                <th class="py-3 px-3 print:border print:border-slate-400">Status Terakhir</th>
                                <th class="py-3 px-3 print:border print:border-slate-400">Sales Incharge</th>
                                <th class="py-3 px-3 print:border print:border-slate-400">CS Incharge</th>
                                <th class="py-3 px-4 text-right print:border print:border-slate-400">Tanggal Masuk</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 print:divide-slate-400">
                            <template x-for="(prospect, index) in paginatedProspects" :key="prospect.id + '-' + currentPage">
                                <tr class="hover:bg-slate-50/80 transition crm-table-slide print:hover:bg-transparent">
                                    <td class="py-3 px-3 text-center text-slate-500 font-medium print:border print:border-slate-400" x-text="(currentPage - 1) * perPage + index + 1"></td>
                                    <td class="py-3 px-4 font-semibold text-slate-900 print:border print:border-slate-400">
                                        <span class="no-print">
                                            <a :href="'/' + (('{{ strtolower(auth()->user()->role ?? '') }}' === 'spv') ? 'spv/' : '') + 'prospek/' + prospect.id" class="hover:text-blue-600 hover:underline font-bold" x-text="prospect.pic || prospect.name"></a>
                                        </span>
                                        <span class="print-only font-bold text-slate-900" x-text="prospect.pic || prospect.name"></span>
                                    </td>
                                    <td class="py-3 px-3 text-slate-600 print:border print:border-slate-400 font-medium" x-text="prospect.type"></td>
                                    <td class="py-3 px-3 print:border print:border-slate-400">
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-block"
                                            :class="{
                                                'bg-slate-100 text-slate-700 border border-slate-200': prospect.status === 'BARU' || prospect.status === 'Cold Lead',
                                                'bg-blue-50 text-blue-700 border border-blue-200': prospect.status === 'KONTAK' || prospect.status === 'Interested',
                                                'bg-amber-50 text-amber-700 border border-amber-200': prospect.status === 'PROSPEK' || prospect.status === 'HANGAT' || prospect.status === 'Follow Up',
                                                'bg-orange-50 text-orange-700 border border-orange-200': prospect.status === 'HOT PROSPEK' || prospect.status === 'PANAS',
                                                'bg-purple-50 text-purple-700 border border-purple-200': prospect.status === 'FORMULIR' || prospect.status === 'Beli Formulir',
                                                'bg-cyan-50 text-cyan-700 border border-cyan-200': prospect.status === 'BERKAS' || prospect.status === 'Pembayaran Termin 1',
                                                'bg-emerald-100 text-emerald-800 border border-emerald-300 font-extrabold shadow-2xs': prospect.status === 'LUNAS' || prospect.status === 'Closing',
                                                'bg-gray-100 text-gray-600 border border-gray-200': prospect.status === 'NO RESPON' || prospect.status === 'DINGIN' || prospect.status === 'Lost'
                                            }"
                                            x-text="prospect.status">
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-slate-700 font-medium print:border print:border-slate-400" x-text="prospect.takeover_sales || prospect.sales_name || '-'"></td>
                                    <td class="py-3 px-3 text-slate-700 font-medium print:border print:border-slate-400" x-text="prospect.takeover_cs || '-'"></td>
                                    <td class="py-3 px-4 text-right text-slate-500 font-medium print:border print:border-slate-400" x-text="formatDate(prospect.created_at_raw || prospect.created_at)"></td>
                                </tr>
                            </template>
                            <tr x-show="filteredProspects.length === 0">
                                <td colspan="7" class="py-6 text-center text-slate-400 italic">Tidak ada data prospek yang ditemukan untuk kriteria filter ini.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Control (Hidden on Print) -->
                <div class="no-print">
                    <x-table-pagination
                        total="filteredProspects.length"
                        page="currentPage"
                        perPage="perPage"
                        totalPages="totalPages"
                        color="blue"
                    />
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- PRINT-ONLY: LEMBAR PENGESAHAN & TANDA TANGAN                   -->
            <!-- ============================================================== -->
            @php
                $currentAuth = auth()->user();
                $assignedHm = $currentAuth ? $currentAuth->getAssignedHm() : null;
                $assignedSpv = $currentAuth ? $currentAuth->getAssignedSpv() : null;
            @endphp
            <div class="print-only mt-10 pt-4 print-avoid-break">
                <div class="grid grid-cols-2 gap-8 text-xs text-slate-800">
                    <!-- Kolom Kiri: Head of Marketing -->
                    <div class="text-center">
                        <p class="font-medium text-slate-600">Mengetahui,</p>
                        <p class="font-bold text-slate-900 uppercase mt-0.5">Head of Marketing & Admisi UCIC</p>
                        <div class="h-20 flex items-center justify-center">
                            <span class="text-slate-300 italic text-[11px]">[ Tanda Tangan & Cap ]</span>
                        </div>
                        <p class="font-bold text-slate-900 underline decoration-slate-900">{{ $assignedHm?->name ?? 'Head of Marketing' }}</p>
                        <p class="text-[10px] text-slate-500 mt-0.5">{{ $assignedHm && $assignedHm->kode ? 'NIP/Kode: ' . $assignedHm->kode : 'NIP. Pegawai UCIC' }}</p>
                    </div>

                    <!-- Kolom Kanan: Supervisor Marketing / Pembuat Laporan -->
                    <div class="text-center">
                        <p class="font-medium text-slate-600">Cirebon, {{ date('d F Y') }}</p>
                        <p class="font-bold text-slate-900 uppercase mt-0.5">{{ ($currentAuth?->role === 'Sales') ? 'Supervisor Pembina Sales' : 'Supervisor Marketing & Sales' }}</p>
                        <div class="h-20 flex items-center justify-center">
                            <span class="text-slate-300 italic text-[11px]">[ Tanda Tangan ]</span>
                        </div>
                        <p class="font-bold text-slate-900 underline decoration-slate-900">{{ $assignedSpv?->name ?? ($currentAuth?->name ?? 'Supervisor Inbound') }}</p>
                        <p class="text-[10px] text-slate-500 mt-0.5">ID Pegawai: {{ ($assignedSpv ?? $currentAuth)?->kode ?? 'UCIC-SPV-001' }}</p>
                    </div>
                </div>

                <!-- Footer Catatan Sistem -->
                <div class="mt-8 pt-2 border-t border-slate-300 text-[9px] text-slate-400 flex items-center justify-between">
                    <span>Dokumen digenerate otomatis melalui Sistem RakitAI CRM Universitas Catur Insan Cendekia (UCIC).</span>
                    <span>Halaman 1 dari 1</span>
                </div>
            </div>

        </div>

    </div>

    <!-- Custom Styling for TomSelect Searchable Periode Dropdown -->
    <style>
        .ts-wrapper.tom-select-periode {
            min-width: 260px;
            width: 100%;
            position: relative;
        }
        .ts-wrapper.tom-select-periode .ts-control {
            border-radius: 0.75rem !important;
            padding: 0.5rem 0.875rem !important;
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            min-height: 38px !important;
            box-shadow: none !important;
            display: flex !important;
            align-items: center !important;
            cursor: pointer !important;
            transition: all 0.15s ease-in-out !important;
        }
        .ts-wrapper.tom-select-periode.focus .ts-control,
        .ts-wrapper.tom-select-periode.dropdown-active .ts-control {
            border-color: #3b82f6 !important;
            background-color: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        }
        .ts-wrapper.tom-select-periode .ts-dropdown {
            border-radius: 0.75rem !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
            font-size: 0.75rem !important;
            margin-top: 4px !important;
            padding: 4px !important;
            z-index: 60 !important;
            background: #ffffff !important;
            max-height: 280px !important;
        }
        .ts-wrapper.tom-select-periode .dropdown-input-wrap {
            padding: 6px 8px !important;
            border-bottom: 1px solid #f1f5f9 !important;
            position: relative !important;
        }
        .ts-wrapper.tom-select-periode .dropdown-input {
            border-radius: 0.5rem !important;
            border: 1px solid #cbd5e1 !important;
            padding: 0.45rem 0.75rem 0.45rem 2rem !important;
            font-size: 0.75rem !important;
            width: 100% !important;
            outline: none !important;
            box-sizing: border-box !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'%3E%3C/path%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: 8px center !important;
            background-size: 14px 14px !important;
        }
        .ts-wrapper.tom-select-periode .dropdown-input:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15) !important;
        }
        .ts-wrapper.tom-select-periode .optgroup-header {
            font-size: 0.65rem !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            color: #94a3b8 !important;
            padding: 6px 10px 2px !important;
            background: #f8fafc !important;
            border-radius: 0.375rem !important;
            margin-top: 4px !important;
        }
        .ts-wrapper.tom-select-periode .option {
            padding: 6px 10px !important;
            border-radius: 0.375rem !important;
            font-weight: 500 !important;
            color: #334155 !important;
            cursor: pointer !important;
        }
        .ts-wrapper.tom-select-periode .option.active {
            background-color: #eff6ff !important;
            color: #1d4ed8 !important;
            font-weight: 600 !important;
        }
        .ts-wrapper.tom-select-periode .option.selected {
            background-color: #3b82f6 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
        }
    </style>

</x-app-layout>
