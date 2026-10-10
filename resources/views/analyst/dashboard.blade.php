@php
    $pageTitle = 'Dashboard PMB';
    $pageSubtitle = 'Evaluasi Funnel, Konversi Mahasiswa, Pencapaian Target & Performa Tim';
@endphp

<x-app-layout :title="'Dashboard PMB - CRM UCIC'">

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
    <!-- NORMAL ANALYTICAL DATA STATE                                   -->
    <!-- ============================================================== -->
    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6">

        <!-- Header Greeting & Quick Actions -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white px-5 py-4 sm:px-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Halo, {{ auth()->user()->name ?? 'Data Analyst' }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">Data Analyst PMB</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">TA {{ $activeTa }}</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Akses Lintas Wilayah & Tim</span>
                </div>
                <p class="text-xs text-slate-500 mt-1">Workspace analisis menyeluruh PMB: Evaluasi funnel, konversi pendaftar, pencapaian target, dan performa kanal.</p>
            </div>

            <!-- Quick Action Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Dropdown Ekspor Excel -->
                <div x-data="{ openExport: false }" class="relative">
                    <button 
                        @click="openExport = !openExport" 
                        class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition shadow-xs flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-emerald-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Ekspor Excel / CSV</span>
                        <svg class="w-3.5 h-3.5 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div 
                        x-show="openExport" 
                        @click.away="openExport = false" 
                        x-transition 
                        class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-lg border border-slate-200 py-1.5 z-30 text-xs text-slate-700"
                    >
                        <div class="px-3 py-1.5 text-[10px] font-bold uppercase text-slate-400 border-b border-slate-100">Pilih Kategori Ekspor</div>
                        <a href="{{ route('analyst.export', array_merge(request()->query(), ['type' => 'prospek'])) }}" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span>Data Prospek & Lead Detail</span>
                        </a>
                        <a href="{{ route('analyst.export', array_merge(request()->query(), ['type' => 'funnel'])) }}" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                            <span>Rekapitulasi Funnel & Konversi</span>
                        </a>
                        <a href="{{ route('analyst.export', array_merge(request()->query(), ['type' => 'transaksi'])) }}" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Transaksi Pembayaran Valid</span>
                        </a>
                        <a href="{{ route('analyst.export', array_merge(request()->query(), ['type' => 'performa_sales'])) }}" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            <span>Performa Sales & SPV</span>
                        </a>
                        <a href="{{ route('analyst.export', array_merge(request()->query(), ['type' => 'sumber'])) }}" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>Sumber & Kanal Marketing</span>
                        </a>
                        <a href="{{ route('analyst.export', array_merge(request()->query(), ['type' => 'sekolah'])) }}" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span>Sekolah & Perusahaan Mitra</span>
                        </a>
                        <a href="{{ route('analyst.export', array_merge(request()->query(), ['type' => 'target'])) }}" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                            <span>Target vs Realisasi</span>
                        </a>
                        <div class="border-t border-slate-100 my-1"></div>
                        <a href="{{ route('analyst.export', array_merge(request()->query(), ['type' => 'follow_up'])) }}" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                            <span>Detail Riwayat Follow-Up</span>
                        </a>
                        <a href="{{ route('analyst.export', array_merge(request()->query(), ['type' => 'timeline'])) }}" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-violet-500"></span>
                            <span>Log Timeline Perubahan Status</span>
                        </a>
                        <a href="{{ route('analyst.export', array_merge(request()->query(), ['type' => 'kualitas_data'])) }}" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition font-medium">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span>Audit Kualitas & Anomali Data</span>
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
        <!-- MULTI-DIMENSIONAL FILTER BAR                                   -->
        <!-- ============================================================== -->
        <form method="GET" action="{{ route('dashboard.data-analyst') }}" class="no-print bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Filter Analisis Terpadu</span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('dashboard.data-analyst') }}" class="text-[11px] font-semibold text-rose-600 hover:text-rose-700 hover:underline">Reset Filter</a>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 text-xs">
                <!-- Periode Cepat -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Periode</label>
                    <select name="periode" onchange="this.form.submit()" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="semua" {{ ($filters['periode'] ?? '') === 'semua' ? 'selected' : '' }}>Semua Waktu</option>
                        <option value="hari_ini" {{ ($filters['periode'] ?? '') === 'hari_ini' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="minggu_ini" {{ ($filters['periode'] ?? '') === 'minggu_ini' ? 'selected' : '' }}>Minggu Ini</option>
                        <option value="bulan_ini" {{ ($filters['periode'] ?? '') === 'bulan_ini' ? 'selected' : '' }}>Bulan Ini</option>
                        <option value="tahunan" {{ ($filters['periode'] ?? '') === 'tahunan' ? 'selected' : '' }}>Tahun Berjalan</option>
                    </select>
                </div>

                <!-- Tahun Akademik -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Tahun Akademik</label>
                    <select name="ta" onchange="this.form.submit()" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua TA</option>
                        @foreach($taOptions as $t)
                            <option value="{{ $t->nama }}" {{ ($filters['ta'] ?? '') === $t->nama ? 'selected' : '' }}>{{ $t->nama }} {{ $t->status === 'Aktif' ? '(Aktif)' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Wilayah -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Wilayah</label>
                    <select name="wilayah_id" onchange="this.form.submit()" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua Wilayah</option>
                        @foreach($wilayahOptions as $w)
                            <option value="{{ $w->id }}" {{ ($filters['wilayah_id'] ?? '') == $w->id ? 'selected' : '' }}>{{ $w->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Prodi -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Program Studi</label>
                    <select name="prodi_id" onchange="this.form.submit()" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua Prodi</option>
                        @foreach($prodiOptions as $p)
                            <option value="{{ $p->id }}" {{ ($filters['prodi_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Kelas -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Kelas</label>
                    <select name="kelas" onchange="this.form.submit()" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua Kelas</option>
                        <option value="Reguler" {{ ($filters['kelas'] ?? '') === 'Reguler' ? 'selected' : '' }}>Reguler</option>
                        <option value="Karyawan" {{ ($filters['kelas'] ?? '') === 'Karyawan' ? 'selected' : '' }}>Karyawan</option>
                    </select>
                </div>

                <!-- Sumber Informasi -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Sumber Lead</label>
                    <select name="source" onchange="this.form.submit()" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua Sumber</option>
                        @foreach(\App\Models\Prospek::SOURCES as $src)
                            <option value="{{ $src }}" {{ ($filters['source'] ?? '') === $src ? 'selected' : '' }}>{{ $src }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Lead -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Status Lead</label>
                    <select name="status" onchange="this.form.submit()" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua Status</option>
                        <option value="BARU" {{ ($filters['status'] ?? '') === 'BARU' ? 'selected' : '' }}>BARU</option>
                        <option value="KONTAK" {{ ($filters['status'] ?? '') === 'KONTAK' ? 'selected' : '' }}>KONTAK</option>
                        <option value="PROSPEK" {{ ($filters['status'] ?? '') === 'PROSPEK' ? 'selected' : '' }}>HANGAT (PROSPEK)</option>
                        <option value="HOT PROSPEK" {{ ($filters['status'] ?? '') === 'HOT PROSPEK' ? 'selected' : '' }}>PANAS (HOT)</option>
                        <option value="FORMULIR" {{ ($filters['status'] ?? '') === 'FORMULIR' ? 'selected' : '' }}>FORMULIR</option>
                        <option value="BERKAS" {{ ($filters['status'] ?? '') === 'BERKAS' ? 'selected' : '' }}>BERKAS</option>
                        <option value="LUNAS" {{ ($filters['status'] ?? '') === 'LUNAS' ? 'selected' : '' }}>LUNAS</option>
                        <option value="DINGIN" {{ ($filters['status'] ?? '') === 'DINGIN' ? 'selected' : '' }}>DINGIN / LOST</option>
                        <option value="CANCEL" {{ ($filters['status'] ?? '') === 'CANCEL' ? 'selected' : '' }}>BATAL (CANCEL)</option>
                    </select>
                </div>

                <!-- Sales -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Sales Handler</label>
                    <select name="sales_id" onchange="this.form.submit()" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua Sales</option>
                        @foreach($salesOptions as $s)
                            <option value="{{ $s->id }}" {{ ($filters['sales_id'] ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Custom Date Range Picker (Optional) -->
            <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap items-center gap-3">
                <span class="text-[11px] font-bold text-slate-500">Rentang Tanggal Kustom:</span>
                <div class="flex items-center gap-2">
                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs">
                    <span class="text-slate-400 text-xs">s/d</span>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs">
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs transition">Terapkan</button>
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
                <x-stat-card 
                    title="Total Kontak Unik" 
                    :value="number_format($totalProspekUnik)" 
                    subtitle="Seluruh Lead Terdata" 
                    color="blue"
                />
                <x-stat-card 
                    title="Kontak Baru" 
                    :value="number_format($kontakBaruCount)" 
                    subtitle="Periode Terpilih" 
                    color="sky"
                />
                <x-stat-card 
                    title="Beli Formulir" 
                    :value="number_format($formulirCount)" 
                    subtitle="Pendaftar Terverifikasi" 
                    color="purple"
                />
                <x-stat-card 
                    title="Maba Lunas Tahap 1" 
                    :value="number_format($lunasCount)" 
                    subtitle="Mahasiswa Resmi" 
                    color="emerald"
                />
                <x-stat-card 
                    title="Konversi Kontak → Lunas" 
                    :value="$convKontakLunas . '%'" 
                    subtitle="Rasio Sukses PMB" 
                    color="indigo"
                />
                <x-stat-card 
                    title="Total Omset Valid" 
                    :value="'Rp ' . number_format($totalRevenueValid / 1000000, 1) . ' Jt'" 
                    subtitle="Formulir + Termin 1" 
                    color="teal"
                />
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- SECTION 2: FUNNEL KONVERSI PMB & DROPOFF                       -->
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
                    <span class="font-extrabold text-rose-600 ml-1">{{ number_format($lostCount) }} Calon</span>
                </div>
            </div>

            <!-- Visual Funnel Stage Bars -->
            <div class="space-y-4">
                @foreach($funnelData as $f)
                    <div class="bg-slate-50/70 p-3.5 rounded-xl border border-slate-200/70">
                        <div class="flex items-center justify-between mb-1.5">
                            <div>
                                <span class="font-extrabold text-xs text-slate-900">{{ $f['stage'] }}</span>
                                <span class="text-[11px] text-slate-500 ml-2 hidden sm:inline">{{ $f['sub'] }}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-black text-sm text-slate-900">{{ number_format($f['count']) }}</span>
                                <span class="text-xs font-semibold text-slate-500 ml-1">({{ $f['pct_total'] }}%)</span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-slate-200/80 rounded-full h-3 overflow-hidden">
                            <div 
                                class="bg-{{ $f['color'] }}-500 h-3 rounded-full transition-all duration-700 ease-out shadow-xs" 
                                style="width: {{ $f['pct_total'] }}%"
                            ></div>
                        </div>

                        @if($f['drop_count'] > 0)
                            <div class="mt-1.5 flex items-center justify-between text-[10px] text-slate-400 font-medium">
                                <span>Status Berhenti / Tidak Merespons pada tahap ini:</span>
                                <span class="font-bold text-rose-500">{{ number_format($f['drop_count']) }} prospek</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Conversion Ratios Summary -->
            <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-3 text-center">
                <div class="p-3 bg-blue-50/60 rounded-xl border border-blue-100">
                    <span class="text-[11px] text-blue-700 font-semibold block">Kontak → Beli Formulir</span>
                    <span class="text-xl font-extrabold text-blue-900">{{ $convKontakFormulir }}%</span>
                </div>
                <div class="p-3 bg-purple-50/60 rounded-xl border border-purple-100">
                    <span class="text-[11px] text-purple-700 font-semibold block">Beli Formulir → Lunas Termin 1</span>
                    <span class="text-xl font-extrabold text-purple-900">{{ $convFormulirLunas }}%</span>
                </div>
                <div class="p-3 bg-emerald-50/60 rounded-xl border border-emerald-100">
                    <span class="text-[11px] text-emerald-700 font-semibold block">Kontak → Lunas Maba Resmi</span>
                    <span class="text-xl font-extrabold text-emerald-900">{{ $convKontakLunas }}%</span>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- SECTION 3: PERBANDINGAN PERIODE & TARGET RESMI                 -->
        <!-- ============================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Card 1: Perbandingan Periode -->
            <div class="crm-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            <span>Perbandingan Periode Waktu</span>
                        </h3>
                        <span class="text-[11px] text-slate-400">Basis Tanggal Riil</span>
                    </div>

                    <div class="space-y-3 text-xs">
                        <!-- Hari Ini vs Kemarin -->
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
                            <div class="flex items-center justify-between font-bold text-slate-800 mb-1">
                                <span>Hari Ini vs Kemarin</span>
                                <span class="{{ $periodComparison['today']['kontak_diff'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $periodComparison['today']['kontak_diff'] >= 0 ? '+' : '' }}{{ $periodComparison['today']['kontak_diff'] }} Kontak
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-500">
                                <div>Kontak: <strong>{{ $periodComparison['today']['kontak'] }}</strong> (Kemarin: {{ $periodComparison['today']['kontak_prev'] }})</div>
                                <div>Lunas: <strong>{{ $periodComparison['today']['lunas'] }}</strong> (Kemarin: {{ $periodComparison['today']['lunas_prev'] }})</div>
                            </div>
                        </div>

                        <!-- Minggu Ini vs Minggu Lalu -->
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
                            <div class="flex items-center justify-between font-bold text-slate-800 mb-1">
                                <span>Minggu Ini vs Minggu Lalu</span>
                                <span class="{{ $periodComparison['week']['kontak_diff'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $periodComparison['week']['kontak_diff'] >= 0 ? '+' : '' }}{{ $periodComparison['week']['kontak_diff'] }} Kontak
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-500">
                                <div>Kontak: <strong>{{ $periodComparison['week']['kontak'] }}</strong> (Lalu: {{ $periodComparison['week']['kontak_prev'] }})</div>
                                <div>Lunas: <strong>{{ $periodComparison['week']['lunas'] }}</strong> (Lalu: {{ $periodComparison['week']['lunas_prev'] }})</div>
                            </div>
                        </div>

                        <!-- Bulan Ini vs Bulan Lalu -->
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
                            <div class="flex items-center justify-between font-bold text-slate-800 mb-1">
                                <span>Bulan Ini vs Bulan Kemarin</span>
                                <span class="{{ $periodComparison['month']['kontak_diff'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $periodComparison['month']['kontak_diff'] >= 0 ? '+' : '' }}{{ $periodComparison['month']['kontak_diff'] }} Kontak
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-500">
                                <div>Kontak: <strong>{{ $periodComparison['month']['kontak'] }}</strong> (Lalu: {{ $periodComparison['month']['kontak_prev'] }})</div>
                                <div>Lunas: <strong>{{ $periodComparison['month']['lunas'] }}</strong> (Lalu: {{ $periodComparison['month']['lunas_prev'] }})</div>
                            </div>
                        </div>
                    </div>

                    <!-- Keterbatasan Data Historis YoY -->
                    <div class="mt-4 p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl text-[11px] text-amber-800">
                        <div class="font-bold flex items-center gap-1.5 mb-0.5">
                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Perbandingan Tahun Akademik Sebelumnya (YoY):</span>
                        </div>
                        <p class="leading-relaxed text-amber-700">Data operasional CRM saat ini aktif pada TA 2027/2028. Data historis pembanding untuk tahun akademik sebelumnya belum tersedia dalam database (tidak diisi dummy).</p>
                    </div>
                </div>
            </div>

            <!-- Card 2: Pendaftar & Pembayaran Valid -->
            <div class="crm-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Pendaftar & Pembayaran Masuk</span>
                        </h3>
                        <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">Verified CS</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="p-4 rounded-xl bg-purple-50/70 border border-purple-100">
                            <span class="text-[11px] font-bold text-purple-700 block">Biaya Formulir (Rp)</span>
                            <span class="text-lg font-black text-purple-950 block mt-1">Rp {{ number_format($transaksiFormulirNominal, 0, ',', '.') }}</span>
                            <span class="text-[10px] text-purple-600 font-semibold">{{ $transaksiFormulirCount }} Transaksi</span>
                        </div>
                        <div class="p-4 rounded-xl bg-emerald-50/70 border border-emerald-100">
                            <span class="text-[11px] font-bold text-emerald-700 block">Termin 1 Maba (Rp)</span>
                            <span class="text-lg font-black text-emerald-950 block mt-1">Rp {{ number_format($transaksiTermin1Nominal, 0, ',', '.') }}</span>
                            <span class="text-[10px] text-emerald-600 font-semibold">{{ $transaksiTermin1Count }} Calon Terverifikasi</span>
                        </div>
                    </div>

                    <div>
                        <div class="text-[11px] font-bold text-slate-700 mb-2 uppercase tracking-wider">Penerimaan Berdasarkan Program Studi:</div>
                        <div class="space-y-2">
                            @forelse($pembayaranPerProdi as $prodiPay)
                                <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-slate-50 border border-slate-100">
                                    <span class="font-semibold text-slate-800">{{ $prodiPay->nama_prodi }}</span>
                                    <span class="font-extrabold text-slate-900">Rp {{ number_format($prodiPay->total_nominal, 0, ',', '.') }}</span>
                                </div>
                            @empty
                                <div class="text-center py-3 text-xs text-slate-400">Belum ada transaksi pembayaran pada filter ini.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] text-slate-500 bg-slate-50/80 p-2.5 rounded-xl border border-slate-200/60">
                        <span class="font-bold text-slate-700 block mb-0.5">Catatan Jenis Pembayaran:</span>
                        <p class="leading-relaxed">Sistem saat ini mencatat jenis <em>Beli Formulir</em> dan <em>Pembayaran Termin 1 (Lunas Tahap 1)</em>. Pembayaran DP terpisah dan perlengkapan belum memiliki skema kolom khusus pada database CRM.</p>
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
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">{{ count($salesList) }} Personel</span>
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
                        @forelse($salesList as $sales)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-900">{{ $sales['name'] }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $sales['kode'] }}</div>
                                </td>
                                <td class="py-3 px-3 text-slate-600">{{ $sales['spv'] }}</td>
                                <td class="py-3 px-3 text-slate-600">{{ $sales['wilayah'] }}</td>
                                <td class="py-3 px-3 text-center font-bold text-slate-800">{{ number_format($sales['total_kontak']) }}</td>
                                <td class="py-3 px-3 text-center text-slate-700">{{ number_format($sales['follow_up_count']) }}</td>
                                <td class="py-3 px-3 text-center">
                                    @if($sales['avg_response_hours'] !== null)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $sales['avg_response_hours'] <= 2 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                            {{ $sales['avg_response_hours'] }} Jam
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-[10px]">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-center font-semibold text-purple-700">{{ number_format($sales['formulir_count']) }}</td>
                                <td class="py-3 px-3 text-center font-extrabold text-emerald-700">{{ number_format($sales['lunas_count']) }}</td>
                                <td class="py-3 px-3 text-center">
                                    <span class="font-extrabold {{ $sales['konversi'] >= 10 ? 'text-emerald-600' : ($sales['konversi'] >= 5 ? 'text-blue-600' : 'text-slate-600') }}">
                                        {{ $sales['konversi'] }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-6 text-slate-400">Belum ada data sales yang terdaftar.</td>
                            </tr>
                        @endforelse
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

                @if($topChannel)
                    <div class="p-3 mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-emerald-700">Kanal Konversi Tertinggi:</span>
                            <div class="font-extrabold text-emerald-900 text-sm">{{ $topChannel['nama'] }}</div>
                        </div>
                        <div class="text-right">
                            <span class="font-black text-emerald-700 text-base">{{ $topChannel['lunas'] }} Maba Lunas</span>
                            <span class="text-[10px] text-emerald-600 block">({{ $topChannel['konversi'] }}% Konversi)</span>
                        </div>
                    </div>
                @endif

                <div class="space-y-3">
                    @foreach($sumberAnalysis->take(6) as $src)
                        <div class="text-xs">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-slate-800">{{ $src['nama'] }}</span>
                                <span class="font-semibold text-slate-600">{{ $src['kontak'] }} Kontak • <strong class="text-emerald-600">{{ $src['lunas'] }} Lunas</strong> ({{ $src['konversi'] }}%)</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-sky-500 h-2 rounded-full" style="width: {{ $totalProspekUnik > 0 ? min(100, round(($src['kontak'] / $totalProspekUnik) * 100)) : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
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
                        <span class="text-xl font-black text-slate-900">{{ number_format($totalSekolah) }}</span>
                        <span class="text-[10px] text-emerald-600 font-semibold block mt-0.5">{{ $sekolahDihubungiCount }} Sudah Dihubungi</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-center">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Total Perusahaan Mitra</span>
                        <span class="text-xl font-black text-slate-900">{{ number_format($totalPerusahaan) }}</span>
                        <span class="text-[10px] text-slate-500 font-semibold block mt-0.5">Kerjasama Industri</span>
                    </div>
                </div>

                <div>
                    <div class="text-[11px] font-bold text-slate-700 mb-2 uppercase tracking-wider">Top 5 Sekolah Penyumbang Pendaftar Terbanyak:</div>
                    <div class="space-y-2">
                        @forelse($topSekolah as $sch)
                            <div class="flex items-center justify-between text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="min-w-0 pr-2">
                                    <div class="font-bold text-slate-900 truncate">{{ $sch->nama }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $sch->wilayah?->nama ?? '-' }}</div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="font-extrabold text-blue-700">{{ $sch->total_prospek }} Kontak</span>
                                    <span class="text-[10px] font-bold text-emerald-600 block">{{ $sch->total_lunas }} Lunas</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-xs text-slate-400">Belum ada data sekolah mitra.</div>
                        @endforelse
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
                        <span class="text-xl font-black text-amber-950">{{ number_format($belumFollowUpCount) }}</span>
                        <span class="text-[9.5px] text-amber-600 block mt-0.5">Perlu Tindak Lanjut</span>
                    </div>
                    <div class="p-3 bg-rose-50/70 rounded-xl border border-rose-100">
                        <span class="text-[10px] font-bold text-rose-700 uppercase block">Duplikat Nomor</span>
                        <span class="text-xl font-black text-rose-950">{{ number_format($duplicateWaCount) }}</span>
                        <span class="text-[9.5px] text-rose-600 block mt-0.5">Nomor WA Berulang</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                        <span class="text-[10px] font-bold text-slate-500 uppercase block">Data Belum Lengkap</span>
                        <span class="text-xl font-black text-slate-900">{{ number_format($incompleteDataCount) }}</span>
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

            <!-- Segmentasi Pendaftar (Domisili, Kelas, Jalur) -->
            <div class="crm-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Segmentasi Pendaftar</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Pengelompokan calon mahasiswa berdasarkan kelas, jalur, dan wilayah</p>
                    </div>
                </div>

                <!-- Kelas & Jalur Grid -->
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Kelas Mahasiswa:</span>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-700">Reguler:</span>
                            <span class="font-extrabold text-slate-900">{{ number_format($segmentasiKelas['Reguler']) }} Calon</span>
                        </div>
                        <div class="flex items-center justify-between text-xs mt-1">
                            <span class="font-semibold text-slate-700">Karyawan:</span>
                            <span class="font-extrabold text-slate-900">{{ number_format($segmentasiKelas['Karyawan']) }} Calon</span>
                        </div>
                    </div>
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Jalur Pendaftaran:</span>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-700">Melalui Sales:</span>
                            <span class="font-extrabold text-slate-900">{{ number_format($segmentasiJalur['sales']) }} Lead</span>
                        </div>
                        <div class="flex items-center justify-between text-xs mt-1">
                            <span class="font-semibold text-slate-700">Mandiri / CS:</span>
                            <span class="font-extrabold text-slate-900">{{ number_format($segmentasiJalur['direct']) }} Lead</span>
                        </div>
                    </div>
                </div>

                <!-- Distribusi Wilayah Domisili Top 5 -->
                <div>
                    <div class="text-[11px] font-bold text-slate-700 mb-2 uppercase tracking-wider">Distribusi Wilayah Domisili Pendaftar:</div>
                    <div class="space-y-2">
                        @foreach($segmentasiWilayah as $wil)
                            <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-slate-50 border border-slate-100">
                                <span class="font-semibold text-slate-800">{{ $wil->nama_wilayah }}</span>
                                <span class="font-bold text-slate-900">{{ number_format($wil->total) }} Calon ({{ $totalProspekUnik > 0 ? round(($wil->total / $totalProspekUnik) * 100, 1) : 0 }}%)</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
