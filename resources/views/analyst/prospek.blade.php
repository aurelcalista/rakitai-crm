@php
    $pageTitle = 'Data Prospek';
    $pageSubtitle = 'Daftar Seluruh Data Calon Mahasiswa & Pipeline PMB';
@endphp

<x-app-layout :title="'Data Prospek - CRM UCIC'">

    <!-- State Simulation Wrappers -->
    <div x-show="$store.crm.activeState === 'loading'" x-cloak class="no-print">
        <x-loading-skeleton type="table" />
    </div>

    <div x-show="$store.crm.activeState === 'empty'" x-cloak class="no-print">
        <x-empty-state 
            title="Tidak ada data prospek" 
            description="Tidak ditemukan prospek dengan parameter filter aktif."
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak class="no-print">
        <x-error-state />
    </div>

    <!-- NORMAL DATA STATE -->
    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6">

        <!-- ============================================================== -->
        <!-- HEADER SCREEN BANNER                                           -->
        <!-- ============================================================== -->
        <div class="relative z-30 bg-white rounded-2xl border border-slate-200/90 p-5 sm:p-6 shadow-xs">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-sky-500 via-indigo-500 to-emerald-500 rounded-t-2xl"></div>

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex items-start sm:items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-sky-600 to-indigo-600 text-white flex items-center justify-center shadow-xs shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Eksplorasi Data Prospek</h2>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-50 text-sky-700 border border-sky-200">Read-Only</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                {{ number_format($prospeks->total()) }} Calon Terdaftar
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">Audit terpusat data calon mahasiswa dari seluruh role & wilayah untuk verifikasi pelaporan tanpa mutasi data.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <a 
                        href="{{ route('analyst.dashboard') }}" 
                        class="px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition flex items-center gap-1.5 border border-slate-200/80 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Kembali ke Dashboard</span>
                    </a>

                    <a 
                        href="{{ route('analyst.export', array_merge(request()->query(), ['type' => 'prospek'])) }}" 
                        class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition flex items-center gap-2 cursor-pointer"
                        title="Unduh seluruh data prospek hasil filter"
                    >
                        <svg class="w-4 h-4 text-emerald-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Unduh Hasil Filter (.xlsx)</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- FILTER BAR TERPADU (TERMASUK FILTER BY DATE / RENTANG TANGGAL) -->
        <!-- ============================================================== -->
        <form method="GET" action="{{ route('analyst.prospek.index') }}" class="no-print bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/90 shadow-xs space-y-4">
            
            @if(request('scope'))
                <input type="hidden" name="scope" value="{{ request('scope') }}">
            @endif

            <!-- Scope Switcher Tabs -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl w-fit border border-slate-200/60">
                    <a 
                        href="{{ route('analyst.prospek.index', array_filter(array_merge(request()->query(), ['scope' => null]))) }}" 
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ !request('scope') ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800' }}"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        <span>Semua Prospek</span>
                    </a>
                    <a 
                        href="{{ route('analyst.prospek.index', array_merge(request()->query(), ['scope' => 'presensi'])) }}" 
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ request('scope') === 'presensi' ? 'bg-white text-emerald-700 shadow-2xs' : 'text-slate-500 hover:text-slate-800' }}"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Presensi Siswa Sekolah</span>
                    </a>
                </div>

                <div class="flex items-center gap-2 flex-wrap text-[11px]">
                    @if(!empty($filters['from_date']) || !empty($filters['to_date']))
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-sky-50 text-sky-700 border border-sky-200 font-semibold">
                            <svg class="w-3.5 h-3.5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Filter Tanggal: {{ $filters['from_date'] ?: '...' }} s/d {{ $filters['to_date'] ?: '...' }}</span>
                        </span>
                    @endif
                    <span class="text-slate-400 font-medium">Filter otomatis membatasi baris data tabel & unduhan Excel</span>
                </div>
            </div>

            <!-- Row 1: Filter By Date (Rentang Tanggal & Periode Waktu) -->
            <div class="p-3.5 sm:p-4 rounded-xl bg-slate-50/80 border border-slate-200/70 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Filter Berdasarkan Tanggal (By Date)</span>
                    </span>
                    @if(!empty($filters['from_date']) || !empty($filters['to_date']) || ($filters['periode'] ?? '') !== 'semua')
                        <a 
                            href="{{ route('analyst.prospek.index', array_filter(array_merge(request()->query(), ['from_date' => null, 'to_date' => null, 'start_date' => null, 'end_date' => null, 'periode' => 'semua']))) }}"
                            class="text-[10px] font-bold text-rose-600 hover:text-rose-700 hover:underline flex items-center gap-1"
                        >
                            <span>Reset Tanggal</span>
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    <!-- Preset Periode Cepat -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Pilihan Periode Waktu</label>
                        <select name="periode" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="semua" {{ ($filters['periode'] ?? '') === 'semua' ? 'selected' : '' }}>Semua Waktu</option>
                            <option value="hari_ini" {{ ($filters['periode'] ?? '') === 'hari_ini' ? 'selected' : '' }}>Hari Ini</option>
                            <option value="kemarin" {{ ($filters['periode'] ?? '') === 'kemarin' ? 'selected' : '' }}>Kemarin</option>
                            <option value="minggu_ini" {{ ($filters['periode'] ?? '') === 'minggu_ini' ? 'selected' : '' }}>Minggu Ini</option>
                            <option value="bulan_ini" {{ ($filters['periode'] ?? '') === 'bulan_ini' ? 'selected' : '' }}>Bulan Ini</option>
                            <option value="tahunan" {{ ($filters['periode'] ?? '') === 'tahunan' ? 'selected' : '' }}>Tahun Berjalan</option>
                        </select>
                    </div>

                    <!-- Tanggal Mulai -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Tanggal Mulai (Dari)</label>
                        <input 
                            type="date" 
                            name="from_date" 
                            value="{{ $filters['from_date'] ?? request('start_date', '') }}" 
                            title="Tanggal Mulai"
                            class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition"
                        >
                    </div>

                    <!-- Tanggal Selesai -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Tanggal Selesai (Sampai)</label>
                        <input 
                            type="date" 
                            name="to_date" 
                            value="{{ $filters['to_date'] ?? request('end_date', '') }}" 
                            title="Tanggal Selesai"
                            class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition"
                        >
                    </div>

                    <!-- Tahun Akademik -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Tahun Akademik</label>
                        <select name="ta" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="all">Semua Tahun Akademik</option>
                            @foreach($taOptions as $t)
                                <option value="{{ $t->nama }}" {{ ($filters['ta'] ?? '') === $t->nama ? 'selected' : '' }}>{{ $t->nama }} {{ $t->status === 'Aktif' ? '(Aktif)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Row 2: Pencarian & Dimensi Pokok -->
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2">Pencarian & Parameter Lead</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 text-xs">
                    <!-- Search text -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Cari Nama / WhatsApp / No Reg</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input 
                                type="text" 
                                name="search" 
                                value="{{ request('search') }}" 
                                placeholder="Ketik nama, WhatsApp, No Reg..." 
                                class="w-full pl-9 pr-3 py-2.5 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition"
                            >
                        </div>
                    </div>

                    <!-- Wilayah -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Wilayah Cakupan</label>
                        <select name="wilayah_id" onchange="this.form.submit()" class="w-full px-3 py-2.5 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="all">Semua Wilayah</option>
                            @foreach($wilayahOptions as $w)
                                <option value="{{ $w->id }}" {{ ($filters['wilayah_id'] ?? '') == $w->id ? 'selected' : '' }}>{{ $w->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Status Lead Pipeline</label>
                        <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2.5 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="all">Semua Status</option>
                            <option value="BARU" {{ ($filters['status'] ?? '') === 'BARU' ? 'selected' : '' }}>BARU</option>
                            <option value="KONTAK" {{ ($filters['status'] ?? '') === 'KONTAK' ? 'selected' : '' }}>KONTAK</option>
                            <option value="PROSPEK" {{ ($filters['status'] ?? '') === 'PROSPEK' ? 'selected' : '' }}>HANGAT (PROSPEK)</option>
                            <option value="HOT PROSPEK" {{ ($filters['status'] ?? '') === 'HOT PROSPEK' ? 'selected' : '' }}>PANAS (HOT PROSPEK)</option>
                            <option value="FORMULIR" {{ ($filters['status'] ?? '') === 'FORMULIR' ? 'selected' : '' }}>FORMULIR</option>
                            <option value="BERKAS" {{ ($filters['status'] ?? '') === 'BERKAS' ? 'selected' : '' }}>BERKAS</option>
                            <option value="LUNAS" {{ ($filters['status'] ?? '') === 'LUNAS' ? 'selected' : '' }}>LUNAS</option>
                            <option value="DINGIN" {{ ($filters['status'] ?? '') === 'DINGIN' ? 'selected' : '' }}>DINGIN</option>
                            <option value="CANCEL" {{ ($filters['status'] ?? '') === 'CANCEL' ? 'selected' : '' }}>BATAL</option>
                        </select>
                    </div>

                    <!-- Prodi -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Pilihan Program Studi</label>
                        <select name="prodi_id" onchange="this.form.submit()" class="w-full px-3 py-2.5 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="all">Semua Program Studi</option>
                            @foreach($prodiOptions as $p)
                                <option value="{{ $p->id }}" {{ ($filters['prodi_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Row 3: Dimensi Penugasan & Saluran -->
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2">Dimensi Penugasan, Kelas & Saluran</span>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3.5 text-xs">
                    <!-- Kelas -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Kelas Mahasiswa</label>
                        <select name="kelas" onchange="this.form.submit()" class="w-full px-3 py-2.5 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="all">Semua Kelas</option>
                            <option value="Reguler" {{ ($filters['kelas'] ?? '') === 'Reguler' ? 'selected' : '' }}>Reguler</option>
                            <option value="Karyawan" {{ ($filters['kelas'] ?? '') === 'Karyawan' ? 'selected' : '' }}>Karyawan</option>
                        </select>
                    </div>

                    <!-- Sumber Prospek -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Sumber / Kanal Lead</label>
                        <select name="source" onchange="this.form.submit()" class="w-full px-3 py-2.5 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="all">Semua Sumber</option>
                            @foreach($sourceOptions as $src)
                                <option value="{{ $src }}" {{ ($filters['source'] ?? '') === $src ? 'selected' : '' }}>{{ $src }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Gelombang -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Gelombang Pendaftaran</label>
                        <select name="gelombang" onchange="this.form.submit()" class="w-full px-3 py-2.5 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="all">Semua Gelombang</option>
                            @foreach($gelombangOptions as $g)
                                <option value="{{ $g->nama }}" {{ ($filters['gelombang'] ?? '') === $g->nama ? 'selected' : '' }}>{{ $g->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sales -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Sales Personel</label>
                        <select name="sales_id" onchange="this.form.submit()" class="w-full px-3 py-2.5 rounded-xl bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            <option value="all">Semua Sales</option>
                            @foreach($salesOptions as $s)
                                <option value="{{ $s->id }}" {{ ($filters['sales_id'] ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Footer Action Filter -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100 text-xs">
                <div class="flex items-center gap-2">
                    <button 
                        type="submit" 
                        class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs transition shadow-xs flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span>Terapkan Filter</span>
                    </button>
                    <a 
                        href="{{ route('analyst.prospek.index') }}" 
                        class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition border border-slate-200 cursor-pointer"
                    >
                        Reset Semua Filter
                    </a>
                </div>
                <span class="text-[11px] text-slate-400 font-medium">Menampilkan 20 data per halaman secara terurut berdasarkan tanggal terbaru</span>
            </div>
        </form>

        <!-- ============================================================== -->
        <!-- TABEL DATA PROSPEK                                             -->
        <!-- ============================================================== -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50/90 text-slate-500 font-bold uppercase text-[10px] border-b border-slate-200/80">
                        <tr>
                            <th class="py-3 px-3.5 text-center">ID</th>
                            <th class="py-3 px-3.5">Nama Calon Mahasiswa</th>
                            <th class="py-3 px-3.5">Kontak WhatsApp</th>
                            <th class="py-3 px-3.5">Status Lead</th>
                            <th class="py-3 px-3.5">Prodi & Kelas</th>
                            <th class="py-3 px-3.5">Asal Sekolah / Mitra</th>
                            <th class="py-3 px-3.5">Wilayah</th>
                            <th class="py-3 px-3.5">Sales / CS</th>
                            <th class="py-3 px-3.5 text-center">Follow-Up</th>
                            <th class="py-3 px-3.5 text-center">Kelayakan Closing</th>
                            <th class="py-3 px-3.5">Tanggal Masuk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($prospeks as $p)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-3.5 text-center font-mono text-[11px] text-slate-400">#{{ $p->id }}</td>
                                <td class="py-3.5 px-3.5">
                                    <div class="flex items-start gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-black text-xs flex items-center justify-center shrink-0 uppercase mt-0.5">
                                            {{ strtoupper(substr(($p->pic && $p->pic !== '-') ? $p->pic : $p->name, 0, 2)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-extrabold text-slate-900 leading-snug">
                                                {{ ($p->pic && $p->pic !== '-') ? $p->pic : $p->name }}
                                            </div>
                                            @if($p->pic && $p->pic !== '-' && $p->pic !== $p->name)
                                                <div class="text-[10px] text-slate-400 font-normal truncate max-w-[160px]">{{ $p->name }}</div>
                                            @endif
                                            <div class="flex items-center gap-1 flex-wrap mt-1">
                                                @if($p->no_registrasi)
                                                    <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 text-[9px] font-mono font-bold border border-blue-200">
                                                        No: {{ $p->no_registrasi }}
                                                    </span>
                                                @endif
                                                @if($p->gelombang)
                                                    <span class="px-1.5 py-0.5 rounded bg-teal-50 text-teal-700 text-[9px] font-semibold border border-teal-200">
                                                        {{ $p->gelombang }}
                                                    </span>
                                                @endif
                                                @if($p->source === \App\Models\Prospek::SOURCE_PRESENSI_SISWA || $p->source === 'Presensi Siswa')
                                                    <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[9px] font-bold border border-emerald-200">
                                                        Presensi Siswa
                                                    </span>
                                                @else
                                                    <span class="text-[9.5px] text-slate-400">{{ $p->source ?: 'Direct' }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3.5 whitespace-nowrap">
                                    @if($p->whatsapp)
                                        <div class="flex items-center gap-1.5 font-mono text-slate-700 font-medium">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            <span>{{ $p->whatsapp }}</span>
                                        </div>
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3.5 whitespace-nowrap">
                                    <x-status-badge :status="$p->status" />
                                    <div class="text-[9.5px] text-slate-400 font-medium mt-0.5">
                                        PRD: {{ \App\Services\AnalystExportService::mapToPrdStatus($p->status) }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-3.5">
                                    <div class="font-bold text-slate-800 leading-snug truncate max-w-[170px]" title="{{ $p->prodi?->nama ?: ($p->prodi_lainnya ?: '-') }}">
                                        {{ $p->prodi?->nama ?: ($p->prodi_lainnya ?: '-') }}
                                    </div>
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[9.5px] font-semibold bg-slate-100 text-slate-600 mt-0.5">
                                        {{ $p->kelas ?: 'Reguler' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3.5">
                                    <div class="text-slate-700 font-medium truncate max-w-[170px]" title="{{ $p->sekolah?->nama ?: ($p->perusahaan?->nama ?: '-') }}">
                                        {{ $p->sekolah?->nama ?: ($p->perusahaan?->nama ?: '-') }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-3.5 text-slate-600 font-medium whitespace-nowrap">
                                    {{ $p->wilayah?->nama ?: '-' }}
                                </td>
                                <td class="py-3.5 px-3.5 whitespace-nowrap">
                                    <div class="font-semibold text-slate-800">
                                        {{ $p->sales?->name ?: 'Direct / CS' }}
                                    </div>
                                    @if($p->cs)
                                        <div class="text-[9.5px] text-slate-400">CS: {{ $p->cs->name }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3.5 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $p->follow_up_count > 0 ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $p->follow_up_count ?: 0 }}x
                                    </span>
                                </td>
                                <td class="py-3.5 px-3.5 text-center whitespace-nowrap">
                                    @if($p->isMabaLunas())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>LUNAS VALID</span>
                                        </span>
                                    @elseif($p->status === 'LUNAS')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                            LUNAS
                                        </span>
                                    @else
                                        <span class="text-slate-300 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3.5 text-[11px] text-slate-500 whitespace-nowrap font-medium">
                                    {{ $p->created_at ? $p->created_at->format('d M Y, H:i') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-12 text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="font-bold text-slate-600 text-sm">Tidak ada data prospek ditemukan</span>
                                        <span class="text-xs text-slate-400 mt-0.5">Coba sesuaikan parameter filter atau reset filter</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($prospeks->hasPages())
                <div class="p-4 bg-slate-50/50 border-t border-slate-100">
                    {{ $prospeks->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
