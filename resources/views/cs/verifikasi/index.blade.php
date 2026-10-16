@php
    $pageTitle = 'Verifikasi & Riwayat Pembayaran';
    $pageSubtitle = $activeTab === 'pending' ? 'Menunggu Konfirmasi CS' : 'Arsip Transaksi Diproses';
@endphp

<x-app-layout :title="'Verifikasi Pembayaran - CRM UCIC'">

    <div class="space-y-6" x-data="{ 
        rejectModal: false, 
        rejectId: null, 
        rejectName: '',
        rejectNominal: '',
        rejectReason: '',
        detailModal: false,
        selectedTrx: null,
        openRejectModal(id, name, nominal) {
            this.rejectId = id;
            this.rejectName = name;
            this.rejectNominal = nominal;
            this.rejectReason = '';
            this.rejectModal = true;
        },
        openDetailModal(trx) {
            this.selectedTrx = trx;
            this.detailModal = true;
        }
    }">

        {{-- Header & Stat Cards --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white px-5 py-4 sm:px-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    Verifikasi & Riwayat Pembayaran
                    @if($totalPending > 0)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            {{ $totalPending }} Perlu Tindakan
                        </span>
                    @endif
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Periksa dan verifikasi pembayaran Termin 1 dari Sales serta pantau riwayat persetujuan CS.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a 
                    href="{{ route('export.verifikasi', request()->query()) }}"
                    target="_blank"
                    class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs flex items-center gap-1.5 shadow-xs transition cursor-pointer"
                    title="Unduh Spreadsheet Verifikasi Pembayaran (.xlsx)"
                >
                    <svg class="w-4 h-4 text-emerald-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Unduh Spreadsheet</span>
                </a>
                <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-600">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Audit CS Real-time
                </span>
            </div>
        </div>

        {{-- Summary Metric Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Pending --}}
            <a href="{{ route('cs.verifikasi.index', array_filter(['tab' => 'pending', 'prodi_id' => $prodiFilter, 'kelas' => $kelasFilter, 'kategori' => $kategoriFilter, 'q' => $search])) }}"
               class="p-4 rounded-2xl border transition duration-150 {{ $activeTab === 'pending' ? 'bg-amber-50/50 border-amber-300 ring-2 ring-amber-400/20' : 'bg-white border-slate-200/80 hover:border-slate-300 shadow-xs' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-600">Menunggu</span>
                    <span class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-black text-amber-900">{{ $totalPending }}</span>
                    <span class="text-[11px] text-amber-700 font-medium">transaksi</span>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Butuh review CS hari ini</p>
            </a>

            {{-- Verified --}}
            <a href="{{ route('cs.verifikasi.index', array_filter(['tab' => 'riwayat', 'status' => 'verified', 'prodi_id' => $prodiFilter, 'kelas' => $kelasFilter, 'kategori' => $kategoriFilter, 'q' => $search])) }}"
               class="p-4 rounded-2xl border transition duration-150 {{ $activeTab === 'riwayat' && $statusFilter === 'verified' ? 'bg-emerald-50/50 border-emerald-300 ring-2 ring-emerald-400/20' : 'bg-white border-slate-200/80 hover:border-slate-300 shadow-xs' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-600">Disetujui</span>
                    <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-black text-emerald-900">{{ $totalVerified }}</span>
                    <span class="text-[11px] text-emerald-700 font-medium">sah / closing</span>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Telah dinyatakan Lunas</p>
            </a>

            {{-- Rejected --}}
            <a href="{{ route('cs.verifikasi.index', array_filter(['tab' => 'riwayat', 'status' => 'rejected', 'prodi_id' => $prodiFilter, 'kelas' => $kelasFilter, 'kategori' => $kategoriFilter, 'q' => $search])) }}"
               class="p-4 rounded-2xl border transition duration-150 {{ $activeTab === 'riwayat' && $statusFilter === 'rejected' ? 'bg-rose-50/50 border-rose-300 ring-2 ring-rose-400/20' : 'bg-white border-slate-200/80 hover:border-slate-300 shadow-xs' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-600">Ditolak</span>
                    <span class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-black text-rose-900">{{ $totalRejected }}</span>
                    <span class="text-[11px] text-rose-700 font-medium">dibatalkan</span>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Bukti ditolak / bermasalah</p>
            </a>

            {{-- Nominal Disahkan --}}
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-600">Total Sah CS</span>
                    <span class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </span>
                </div>
                <div class="mt-2">
                    <span class="text-lg sm:text-xl font-black text-slate-900">Rp {{ number_format($sumVerifiedAmount, 0, ',', '.') }}</span>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Total pembayaran diverifikasi</p>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="px-5 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ session('success') }}
            </div>
        @endif
        @if(session('info'))
            <div class="px-5 py-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm font-semibold flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('info') }}
            </div>
        @endif
        @if($errors->any())
            <div class="px-5 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-semibold">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Main Container Card --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">

            {{-- Tab Bar Navigation --}}
            <div class="border-b border-slate-100 px-5 pt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50">
                <div class="flex items-center gap-2 overflow-x-auto pb-2 sm:pb-0">
                    {{-- Tab 1: Menunggu --}}
                    <a href="{{ route('cs.verifikasi.index', array_filter(['tab' => 'pending', 'prodi_id' => $prodiFilter, 'kelas' => $kelasFilter, 'kategori' => $kategoriFilter, 'q' => $search])) }}"
                       class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ $activeTab === 'pending' ? 'bg-white text-slate-900 border border-slate-200 shadow-xs' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        <span>Menunggu Verifikasi</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $activeTab === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-700' }}">
                            {{ $totalPending }}
                        </span>
                    </a>

                    {{-- Tab 2: Riwayat Verifikasi --}}
                    <a href="{{ route('cs.verifikasi.index', array_filter(['tab' => 'riwayat', 'prodi_id' => $prodiFilter, 'kelas' => $kelasFilter, 'kategori' => $kategoriFilter, 'q' => $search])) }}"
                       class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ $activeTab === 'riwayat' ? 'bg-white text-slate-900 border border-slate-200 shadow-xs' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        <span>Riwayat Verifikasi</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $activeTab === 'riwayat' ? 'bg-blue-100 text-blue-800' : 'bg-slate-200 text-slate-700' }}">
                            {{ $totalRiwayat }}
                        </span>
                    </a>
                </div>

                {{-- Status Pills filter for Riwayat tab --}}
                @if($activeTab === 'riwayat')
                    <div class="flex items-center gap-1.5 pb-2 sm:pb-0 text-xs">
                        <span class="text-[11px] font-semibold text-slate-400 mr-1">Filter:</span>
                        <a href="{{ route('cs.verifikasi.index', array_filter(['tab' => 'riwayat', 'prodi_id' => $prodiFilter, 'kelas' => $kelasFilter, 'kategori' => $kategoriFilter, 'q' => $search])) }}"
                           class="px-2.5 py-1 rounded-lg font-medium text-[11px] transition {{ empty($statusFilter) ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            Semua
                        </a>
                        <a href="{{ route('cs.verifikasi.index', array_filter(['tab' => 'riwayat', 'status' => 'verified', 'prodi_id' => $prodiFilter, 'kelas' => $kelasFilter, 'kategori' => $kategoriFilter, 'q' => $search])) }}"
                           class="px-2.5 py-1 rounded-lg font-medium text-[11px] transition {{ $statusFilter === 'verified' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                            Disetujui ({{ $totalVerified }})
                        </a>
                        <a href="{{ route('cs.verifikasi.index', array_filter(['tab' => 'riwayat', 'status' => 'rejected', 'prodi_id' => $prodiFilter, 'kelas' => $kelasFilter, 'kategori' => $kategoriFilter, 'q' => $search])) }}"
                           class="px-2.5 py-1 rounded-lg font-medium text-[11px] transition {{ $statusFilter === 'rejected' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                            Ditolak ({{ $totalRejected }})
                        </a>
                    </div>
                @endif
            </div>

            {{-- Search & Filter Bar --}}
            <div class="p-4 border-b border-slate-100 bg-white flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <form action="{{ route('cs.verifikasi.index') }}" method="GET" class="flex flex-wrap items-center gap-2 w-full lg:max-w-4xl">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    @if($statusFilter)
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                    @endif
                    <div class="relative min-w-[200px] flex-1">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" name="q" value="{{ $search }}"
                               placeholder="Cari camaba, sales, catatan, atau alasan..."
                               class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    </div>

                    {{-- Filter Jurusan / Prodi --}}
                    <select name="prodi_id" onchange="this.form.submit()"
                            class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shrink-0 cursor-pointer max-w-[190px]">
                        <option value="">Semua Jurusan</option>
                        @foreach($prodis as $p)
                            <option value="{{ $p->id }}" {{ (string)$prodiFilter === (string)$p->id ? 'selected' : '' }}>
                                {{ $p->nama }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Filter Kelas --}}
                    <select name="kelas" onchange="this.form.submit()"
                            class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shrink-0 cursor-pointer">
                        <option value="">Semua Kelas</option>
                        <option value="Reguler" {{ $kelasFilter === 'Reguler' ? 'selected' : '' }}>Reguler</option>
                        <option value="Karyawan" {{ $kelasFilter === 'Karyawan' ? 'selected' : '' }}>Karyawan</option>
                    </select>

                    {{-- Filter Kategori --}}
                    <select name="kategori" onchange="this.form.submit()"
                            class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shrink-0 cursor-pointer">
                        <option value="">Semua Kategori</option>
                        @foreach(\App\Models\Transaksi::KATEGORI_LIST as $kat)
                            <option value="{{ $kat }}" {{ $kategoriFilter === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                        @endforeach
                    </select>

                    {{-- Filter Rentang Tanggal --}}
                    <div class="flex items-center gap-1 shrink-0">
                        <span class="text-[11px] font-semibold text-slate-400">Tgl:</span>
                        <input type="date" name="start_date" value="{{ $startDate ?? '' }}" title="Dari Tanggal"
                               class="px-2 py-1 text-xs rounded-xl border border-slate-200 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <span class="text-slate-300">-</span>
                        <input type="date" name="end_date" value="{{ $endDate ?? '' }}" title="Sampai Tanggal"
                               class="px-2 py-1 text-xs rounded-xl border border-slate-200 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    </div>

                    <button type="submit" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-xl transition shrink-0 cursor-pointer">
                        Cari
                    </button>
                    @if($search || $kategoriFilter || $prodiFilter || $kelasFilter || !empty($startDate) || !empty($endDate))
                        <a href="{{ route('cs.verifikasi.index', array_filter(['tab' => $activeTab, 'status' => $statusFilter])) }}"
                           class="px-2.5 py-1.5 text-xs text-rose-600 hover:text-rose-700 font-semibold shrink-0 inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Reset
                        </a>
                    @endif
                </form>

                <div class="text-xs text-slate-500 shrink-0">
                    Menampilkan <span class="font-bold text-slate-800">{{ $totalCount ?? $transaksis->total() }}</span> transaksi
                </div>
            </div>

            {{-- Filter Status Active Badges --}}
            @if($prodiFilter || $kelasFilter || $kategoriFilter || $search)
                @php
                    $activeProdi = $prodiFilter ? $prodis->firstWhere('id', $prodiFilter) : null;
                @endphp
                <div class="px-5 py-2.5 bg-slate-50/70 border-b border-slate-100 flex flex-wrap items-center gap-2 text-xs">
                    <span class="text-slate-400 font-semibold text-[11px]">Filter Aktif:</span>
                    @if($activeProdi)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-800 text-[11px] font-semibold">
                            <span>Jurusan: <strong>{{ $activeProdi->nama }}</strong></span>
                            <a href="{{ route('cs.verifikasi.index', array_filter(['tab' => $activeTab, 'status' => $statusFilter, 'kelas' => $kelasFilter, 'kategori' => $kategoriFilter, 'q' => $search])) }}" class="text-indigo-400 hover:text-indigo-700 font-black ml-0.5" title="Hapus filter jurusan">×</a>
                        </span>
                    @endif
                    @if($kelasFilter)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-purple-50 border border-purple-200 text-purple-800 text-[11px] font-semibold">
                            <span>Kelas: <strong>{{ $kelasFilter }}</strong></span>
                            <a href="{{ route('cs.verifikasi.index', array_filter(['tab' => $activeTab, 'status' => $statusFilter, 'prodi_id' => $prodiFilter, 'kategori' => $kategoriFilter, 'q' => $search])) }}" class="text-purple-400 hover:text-purple-700 font-black ml-0.5" title="Hapus filter kelas">×</a>
                        </span>
                    @endif
                    @if($kategoriFilter)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 text-[11px] font-semibold">
                            <span>Kategori: <strong>{{ $kategoriFilter }}</strong></span>
                            <a href="{{ route('cs.verifikasi.index', array_filter(['tab' => $activeTab, 'status' => $statusFilter, 'prodi_id' => $prodiFilter, 'kelas' => $kelasFilter, 'q' => $search])) }}" class="text-blue-400 hover:text-blue-700 font-black ml-0.5" title="Hapus filter kategori">×</a>
                        </span>
                    @endif
                    @if($search)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-slate-200 text-slate-800 text-[11px] font-semibold">
                            <span>Cari: "<strong>{{ $search }}</strong>"</span>
                            <a href="{{ route('cs.verifikasi.index', array_filter(['tab' => $activeTab, 'status' => $statusFilter, 'prodi_id' => $prodiFilter, 'kelas' => $kelasFilter, 'kategori' => $kategoriFilter])) }}" class="text-slate-500 hover:text-slate-800 font-black ml-0.5" title="Hapus pencarian">×</a>
                        </span>
                    @endif
                    <a href="{{ route('cs.verifikasi.index', array_filter(['tab' => $activeTab, 'status' => $statusFilter])) }}" class="text-[11px] text-rose-600 hover:underline font-semibold ml-auto flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Reset Filter
                    </a>
                </div>
            @endif

            {{-- ═════════════════════════════════════════════════════════════ --}}
            {{-- TAB 1: DAFTAR MENUNGGU VERIFIKASI                           --}}
            {{-- ═════════════════════════════════════════════════════════════ --}}
            @if($activeTab === 'pending')
                @if($transaksis->isEmpty())
                    <div class="py-16 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-700">Tidak ada pembayaran yang perlu diverifikasi</p>
                        <p class="text-xs text-slate-400 mt-1">Semua transaksi Termin 1 dari Sales sudah diproses.</p>
                        <a href="{{ route('cs.verifikasi.index', ['tab' => 'riwayat']) }}" class="mt-4 inline-flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-700 font-bold">
                            Lihat Riwayat Verifikasi →
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-50/80 border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Camaba / Prospek</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Sales Lapangan</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Jenis Transaksi</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kategori</th>
                                    <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nominal</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Metode & Info Bank</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Input</th>
                                    <th class="px-4 py-3 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">Aksi Verifikasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($transaksis as $trx)
                                    @php
                                        $cleanWa = preg_replace('/[^0-9]/', '', $trx->prospek->whatsapp ?? '');
                                        if (str_starts_with($cleanWa, '0')) {
                                            $cleanWa = '62' . substr($cleanWa, 1);
                                        }
                                        $metodeIcons = [
                                            'bank_transfer'   => ['label' => 'Transfer Bank', 'color' => 'bg-amber-50 text-amber-800 border-amber-200'],
                                            'virtual_account' => ['label' => 'Virtual Account', 'color' => 'bg-blue-50 text-blue-700 border-blue-200'],
                                            'gopay'           => ['label' => 'GoPay', 'color' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                                            'dana'            => ['label' => 'DANA', 'color' => 'bg-sky-50 text-sky-700 border-sky-200'],
                                            'shopeepay'       => ['label' => 'ShopeePay', 'color' => 'bg-orange-50 text-orange-700 border-orange-200'],
                                            'tunai'           => ['label' => 'Kasir / Tunai', 'color' => 'bg-slate-50 text-slate-700 border-slate-200'],
                                        ];
                                        $m = $metodeIcons[$trx->metode_pembayaran] ?? ['label' => $trx->metode_pembayaran ?: 'Transfer Bank', 'color' => 'bg-slate-50 text-slate-600 border-slate-200'];
                                        $prodiNama = $trx->prospek?->prodi?->nama ?? $trx->prospek?->prodi_lainnya;
                                        $kelasNama = $trx->prospek?->kelas ?? 'Reguler';
                                        $prospek = $trx->prospek;
                                        $camabaName = $prospek ? $prospek->camaba_name : ('Prospek #' . $trx->prospek_id);
                                        $sekolahNama = $prospek ? $prospek->sekolah_nama : '-';

                                        $trxJson = [
                                            'id'               => $trx->id,
                                            'tanggal'          => $trx->tanggal ? $trx->tanggal->format('d/m/Y') : ($trx->created_at ? $trx->created_at->format('d/m/Y') : '-'),
                                            'waktu'            => $trx->created_at ? $trx->created_at->format('H:i') : '',
                                            'jenis'            => $trx->jenis,
                                            'kategori'         => $trx->kategori ?? '-',
                                            'nominal'          => number_format((float)$trx->nominal, 0, ',', '.'),
                                            'metode'           => $m['label'],
                                            'status'           => $trx->payment_status,
                                            'notes'            => $trx->notes,
                                            'prospek_id'       => $trx->prospek_id,
                                            'prospek_name'     => $camabaName,
                                            'prospek_pic'      => $prospek?->pic ?? '-',
                                            'prospek_whatsapp' => $prospek?->whatsapp ?? '-',
                                            'prospek_school'   => $sekolahNama,
                                            'prospek_prodi'    => $prodiNama ?: '-',
                                            'prospek_kelas'    => $kelasNama,
                                            'sales_name'       => $trx->user?->name ?? ($prospek?->sales?->name ?? '-'),
                                            'verifier_name'    => $trx->verifier?->name ?? null,
                                            'verified_at'      => $trx->verified_at ? $trx->verified_at->format('d/m/Y H:i') : null,
                                            'rejecter_name'    => $trx->rejecter?->name ?? null,
                                            'rejected_at'      => $trx->rejected_at ? $trx->rejected_at->format('d/m/Y H:i') : null,
                                            'rejection_reason' => $trx->rejection_reason ?? null,
                                            'checklist'        => $trx->getChecklistArrayForJson(),
                                        ];
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition">
                                        {{-- Prospek --}}
                                        <td class="px-4 py-4">
                                            <a href="{{ route('prospek.show', $trx->prospek_id) }}" class="font-bold text-slate-900 hover:text-blue-600 block text-xs" title="Lihat detail prospek">
                                                {{ $camabaName }}
                                            </a>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                @if($sekolahNama && $sekolahNama !== '-')
                                                    <span class="text-slate-500 font-medium text-[10px] truncate max-w-[170px]" title="{{ $sekolahNama }}">{{ $sekolahNama }}</span>
                                                @else
                                                    <span class="text-slate-400 text-[10px]">{{ $prospek?->type ?? 'Individu' }}</span>
                                                @endif
                                                @if($prospek?->whatsapp)
                                                    <span class="text-[10px] text-slate-400">• {{ $prospek->whatsapp }}</span>
                                                @endif
                                            </div>
                                            <div class="flex flex-wrap items-center gap-1 mt-1.5">
                                                @if($prodiNama)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9.5px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/80">
                                                        {{ $prodiNama }}
                                                    </span>
                                                @endif
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9.5px] font-semibold {{ $kelasNama === 'Karyawan' ? 'bg-amber-50 text-amber-800 border border-amber-200/80 font-bold' : 'bg-slate-100 text-slate-600 border border-slate-200/80' }}">
                                                    Kelas {{ $kelasNama }}
                                                </span>
                                                @if($trx->prospek?->cs)
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9.5px] font-semibold bg-teal-50 text-teal-800 border border-teal-200">
                                                        CS: {{ $trx->prospek->cs->name }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        {{-- Sales --}}
                                        <td class="px-4 py-4">
                                            <span class="font-medium text-slate-800">{{ $trx->user?->name ?? $trx->prospek?->sales?->name ?? '-' }}</span>
                                        </td>
                                        {{-- Jenis Transaksi yang Diajukan --}}
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200 shadow-2xs">
                                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>{{ $trx->jenis }}</span>
                                            </div>
                                        </td>
                                        {{-- Kategori --}}
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if($trx->kategori)
                                                <span class="px-2 py-0.5 rounded-md font-semibold text-[10px] bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                    {{ $trx->kategori }}
                                                </span>
                                            @else
                                                <span class="text-slate-400 text-[10px]">-</span>
                                            @endif
                                        </td>
                                        {{-- Nominal --}}
                                        <td class="px-4 py-4 text-right font-black text-slate-900 text-xs">
                                            Rp {{ number_format($trx->nominal, 0, ',', '.') }}
                                        </td>
                                        {{-- Metode --}}
                                        <td class="px-4 py-4">
                                            <span class="px-2 py-0.5 rounded-md border font-semibold text-[10px] inline-flex items-center gap-1 {{ $m['color'] }}">
                                                {{ $m['label'] }}
                                            </span>

                                            @if($trx->notes)
                                                <div class="mt-1 text-[10px] text-slate-600 bg-slate-50 p-1.5 rounded-lg border border-slate-100 max-w-xs leading-tight font-medium">
                                                    {{ $trx->notes }}
                                                </div>
                                            @endif
                                        </td>
                                        {{-- Tanggal --}}
                                        <td class="px-4 py-4 text-slate-600 whitespace-nowrap">
                                            {{ $trx->tanggal?->format('d/m/Y') ?? '-' }}
                                        </td>
                                        {{-- Aksi --}}
                                        <td class="px-4 py-4">
                                            <div class="flex items-center justify-center gap-1.5">
                                                {{-- Tombol Rincian Modal --}}
                                                <button type="button"
                                                    @click="openDetailModal({{ json_encode($trxJson) }})"
                                                    class="px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-[10px] font-bold transition flex items-center gap-1 cursor-pointer">
                                                    <svg class="w-3 h-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    <span>Rincian</span>
                                                </button>

                                                {{-- Tombol Verifikasi --}}
                                                <form action="{{ route('cs.verifikasi.verify', $trx->id) }}" method="POST"
                                                      onsubmit="event.preventDefault(); confirmVerifyPayment(this, '{{ addslashes($camabaName) }}', '{{ number_format($trx->nominal, 0, ',', '.') }}');">
                                                    @csrf
                                                    <button type="submit"
                                                        class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold shadow-xs transition flex items-center gap-1 cursor-pointer">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                        <span>Verifikasi</span>
                                                    </button>
                                                </form>

                                                {{-- Tombol Tolak --}}
                                                <button type="button"
                                                    @click="openRejectModal({{ $trx->id }}, {{ json_encode($camabaName) }}, {{ json_encode(number_format($trx->nominal, 0, ',', '.')) }})"
                                                    class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[10px] font-bold transition flex items-center gap-1 cursor-pointer">
                                                    <svg class="w-3 h-3 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    <span>Tolak</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            {{-- ═════════════════════════════════════════════════════════════ --}}
            {{-- TAB 2: RIWAYAT VERIFIKASI (VERIFIED & REJECTED)             --}}
            {{-- ═════════════════════════════════════════════════════════════ --}}
            @else
                @if($transaksis->isEmpty())
                    <div class="py-16 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-700">Belum ada riwayat transaksi yang diproses</p>
                        <p class="text-xs text-slate-400 mt-1">Transaksi yang telah Anda setujui atau tolak akan dicatat secara transparan di sini.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-50/80 border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Camaba / Prospek</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Sales Pemilik</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Jenis Transaksi</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kategori</th>
                                    <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nominal</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Metode</th>
                                    <th class="px-4 py-3 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Diproses Oleh</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Waktu Proses</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Catatan / Alasan</th>
                                    <th class="px-4 py-3 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @php
                                    $groups = [];
                                    foreach ($transaksis as $trx) {
                                        $matchedIndex = null;
                                        foreach ($groups as $index => $group) {
                                            $firstTrx = $group['transactions'][0];

                                            // 1. Harus prospek yang sama (jangan campur transaksi user berbeda)
                                            $sameProspek = ($trx->prospek_id === $firstTrx->prospek_id);

                                            // 2. Status verifikasi sama (verified vs rejected tidak boleh dicampur)
                                            $sameStatus = ($trx->payment_status === $firstTrx->payment_status);

                                            // 3. Tanggal transaksi sama (pengajuan pada tanggal yang sama)
                                            $date1 = $trx->tanggal ? $trx->tanggal->format('Y-m-d') : ($trx->created_at ? $trx->created_at->format('Y-m-d') : null);
                                            $date2 = $firstTrx->tanggal ? $firstTrx->tanggal->format('Y-m-d') : ($firstTrx->created_at ? $firstTrx->created_at->format('Y-m-d') : null);
                                            $sameTanggal = ($date1 === $date2);

                                            // 4. Metode pembayaran sama
                                            $sameMetode = ($trx->metode_pembayaran === $firstTrx->metode_pembayaran);

                                            // 5. Waktu pengajuan dalam batch yang sama (perbedaan created_at <= 5 menit)
                                            $time1 = $trx->created_at ? $trx->created_at->timestamp : 0;
                                            $time2 = $firstTrx->created_at ? $firstTrx->created_at->timestamp : 0;
                                            $sameBatch = (abs($time1 - $time2) <= 300);

                                            if ($sameProspek && $sameStatus && $sameTanggal && $sameMetode && $sameBatch) {
                                                $matchedIndex = $index;
                                                break;
                                            }
                                        }

                                        if ($matchedIndex !== null) {
                                            $groups[$matchedIndex]['transactions'][] = $trx;
                                            $groups[$matchedIndex]['total_nominal'] += (float)$trx->nominal;
                                            $groups[$matchedIndex]['items'][] = [
                                                'id'                => $trx->id,
                                                'jenis'             => $trx->jenis,
                                                'kategori'          => $trx->kategori,
                                                'nominal'           => (float)$trx->nominal,
                                                'nominal_formatted' => number_format((float)$trx->nominal, 0, ',', '.'),
                                            ];
                                        } else {
                                            $groups[] = [
                                                'primary_trx'   => $trx,
                                                'transactions'  => [$trx],
                                                'total_nominal' => (float)$trx->nominal,
                                                'items'         => [
                                                    [
                                                        'id'                => $trx->id,
                                                        'jenis'             => $trx->jenis,
                                                        'kategori'          => $trx->kategori,
                                                        'nominal'           => (float)$trx->nominal,
                                                        'nominal_formatted' => number_format((float)$trx->nominal, 0, ',', '.'),
                                                    ]
                                                ],
                                            ];
                                        }
                                    }
                                @endphp

                                @foreach($groups as $group)
                                    @php
                                        $primaryTrx = $group['primary_trx'];
                                        $prospek = $primaryTrx->prospek;
                                        $camabaName = $prospek ? $prospek->camaba_name : ('Prospek #' . $primaryTrx->prospek_id);
                                        $sekolahNama = $prospek ? $prospek->sekolah_nama : '-';

                                        $cleanWa = preg_replace('/[^0-9]/', '', $prospek->whatsapp ?? '');
                                        if (str_starts_with($cleanWa, '0')) {
                                            $cleanWa = '62' . substr($cleanWa, 1);
                                        }
                                        $metodeLabel = \App\Models\Transaksi::METODE_PEMBAYARAN[$primaryTrx->metode_pembayaran] ?? ($primaryTrx->metode_pembayaran ?: 'Transfer Bank');
                                        $prodiNama = $prospek?->prodi?->nama ?? $prospek?->prodi_lainnya;
                                        $kelasNama = $prospek?->kelas ?? 'Reguler';

                                        $groupCategories = collect($group['transactions'])->pluck('kategori')->filter()->unique()->values();
                                        $allNotes = collect($group['transactions'])->pluck('notes')->filter()->unique()->implode(' | ');
                                        $allRejections = collect($group['transactions'])->pluck('rejection_reason')->filter()->unique()->implode(' | ');
                                        $allJenis = collect($group['transactions'])->pluck('jenis')->filter()->unique()->implode(', ');

                                        $trxJson = [
                                            'id'               => count($group['transactions']) > 1
                                                                    ? collect($group['transactions'])->pluck('id')->implode(', #')
                                                                    : $primaryTrx->id,
                                            'tanggal'          => $primaryTrx->tanggal ? $primaryTrx->tanggal->format('d/m/Y') : ($primaryTrx->created_at ? $primaryTrx->created_at->format('d/m/Y') : '-'),
                                            'waktu'            => $primaryTrx->created_at ? $primaryTrx->created_at->format('H:i') : '',
                                            'jenis'            => $allJenis,
                                            'kategori'         => $groupCategories->implode(', ') ?: '-',
                                            'nominal'          => number_format((float)$group['total_nominal'], 0, ',', '.'),
                                            'metode'           => $metodeLabel,
                                            'status'           => $primaryTrx->payment_status,
                                            'notes'            => $allNotes ?: null,
                                            'prospek_id'       => $primaryTrx->prospek_id,
                                            'prospek_name'     => $camabaName,
                                            'prospek_pic'      => $prospek?->pic ?? '-',
                                            'prospek_whatsapp' => $prospek?->whatsapp ?? '-',
                                            'prospek_school'   => $sekolahNama,
                                            'prospek_prodi'    => $prodiNama ?: '-',
                                            'prospek_kelas'    => $kelasNama,
                                            'sales_name'       => $primaryTrx->user?->name ?? ($prospek?->sales?->name ?? '-'),
                                            'verifier_name'    => $primaryTrx->verifier?->name ?? null,
                                            'verified_at'      => $primaryTrx->verified_at ? $primaryTrx->verified_at->format('d/m/Y H:i') : null,
                                            'rejecter_name'    => $primaryTrx->rejecter?->name ?? null,
                                            'rejected_at'      => $primaryTrx->rejected_at ? $primaryTrx->rejected_at->format('d/m/Y H:i') : null,
                                            'rejection_reason' => $allRejections ?: null,
                                            'checklist'        => $primaryTrx->getChecklistArrayForJson(),
                                            'items'            => $group['items'],
                                        ];
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition">
                                        {{-- Prospek --}}
                                        <td class="px-4 py-3.5">
                                            <a href="{{ route('prospek.show', $primaryTrx->prospek_id) }}" class="font-bold text-slate-900 hover:text-blue-600 block text-xs" title="Lihat detail prospek">
                                                {{ $camabaName }}
                                            </a>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                @if($sekolahNama && $sekolahNama !== '-')
                                                    <span class="text-slate-500 font-medium text-[10px] truncate max-w-[170px]" title="{{ $sekolahNama }}">{{ $sekolahNama }}</span>
                                                @else
                                                    <span class="text-slate-400 text-[10px]">{{ $prospek?->type ?? 'Individu' }}</span>
                                                @endif
                                                @if($prospek?->whatsapp)
                                                    <span class="text-[10px] text-slate-400">• {{ $prospek->whatsapp }}</span>
                                                @endif
                                            </div>
                                            <div class="flex flex-wrap items-center gap-1 mt-1.5">
                                                @if($prodiNama)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9.5px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/80">
                                                        {{ $prodiNama }}
                                                    </span>
                                                @endif
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9.5px] font-semibold {{ $kelasNama === 'Karyawan' ? 'bg-amber-50 text-amber-800 border border-amber-200/80 font-bold' : 'bg-slate-100 text-slate-600 border border-slate-200/80' }}">
                                                    Kelas {{ $kelasNama }}
                                                </span>
                                                @if(count($group['items']) > 1)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9.5px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                        {{ count($group['items']) }} Item Digabung
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        {{-- Sales --}}
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <span class="font-medium text-slate-800">{{ $primaryTrx->user?->name ?? $primaryTrx->prospek?->sales?->name ?? '-' }}</span>
                                        </td>
                                        {{-- Jenis Transaksi yang Diajukan (Rincian Transparan Per Komponen) --}}
                                        <td class="px-4 py-3.5">
                                            <div class="flex flex-col gap-1.5 min-w-[170px]">
                                                @foreach($group['items'] as $item)
                                                    <div class="inline-flex items-center justify-between gap-2 px-2.5 py-1 rounded-lg text-xs font-bold {{ $primaryTrx->payment_status === 'verified' ? 'bg-emerald-50 text-emerald-900 border border-emerald-200' : 'bg-rose-50 text-rose-900 border border-rose-200' }}">
                                                        <div class="flex items-center gap-1.5 truncate">
                                                            @if($primaryTrx->payment_status === 'verified')
                                                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                            @else
                                                                <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            @endif
                                                            <span class="truncate">{{ $item['jenis'] }}</span>
                                                        </div>
                                                        <span class="text-[11px] font-bold text-slate-700 shrink-0">
                                                            Rp {{ $item['nominal_formatted'] }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        {{-- Kategori --}}
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            @if($groupCategories->isNotEmpty())
                                                <div class="flex flex-col gap-1">
                                                    @foreach($groupCategories as $kat)
                                                        <span class="px-2 py-0.5 rounded-md font-semibold text-[10px] bg-indigo-50 text-indigo-700 border border-indigo-200 inline-block w-fit">
                                                            {{ $kat }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-slate-400 text-[10px]">-</span>
                                            @endif
                                        </td>
                                        {{-- Nominal (Total Akumulasi) --}}
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                            <div class="font-black text-slate-900 text-xs">
                                                Rp {{ number_format($group['total_nominal'], 0, ',', '.') }}
                                            </div>
                                            @if(count($group['items']) > 1)
                                                <div class="text-[10px] text-blue-600 font-semibold mt-0.5">
                                                    Total ({{ count($group['items']) }} Item)
                                                </div>
                                            @endif
                                        </td>
                                        {{-- Metode --}}
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <span class="text-[11px] text-slate-700 font-semibold">{{ $metodeLabel }}</span>
                                        </td>
                                        {{-- Status Badge --}}
                                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                            @if($primaryTrx->payment_status === \App\Models\Transaksi::STATUS_VERIFIED)
                                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px] inline-flex items-center gap-1 border border-emerald-200">
                                                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    <span>Disetujui</span>
                                                </span>
                                            @elseif($primaryTrx->payment_status === \App\Models\Transaksi::STATUS_REJECTED)
                                                <span class="px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold text-[10px] inline-flex items-center gap-1 border border-rose-200">
                                                    <svg class="w-3 h-3 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    <span>Ditolak</span>
                                                </span>
                                            @else
                                                <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px] border border-amber-200">
                                                    Pending
                                                </span>
                                            @endif
                                        </td>
                                        {{-- Diproses Oleh --}}
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            @if($primaryTrx->payment_status === \App\Models\Transaksi::STATUS_VERIFIED)
                                                <div class="font-semibold text-slate-800 text-[11px]">
                                                    {{ $primaryTrx->verifier?->name ?? 'CS Staff' }}
                                                </div>
                                                <span class="text-[9px] text-emerald-600 font-bold">Verifikator</span>
                                            @elseif($primaryTrx->payment_status === \App\Models\Transaksi::STATUS_REJECTED)
                                                <div class="font-semibold text-slate-800 text-[11px]">
                                                    {{ $primaryTrx->rejecter?->name ?? 'CS Staff' }}
                                                </div>
                                                <span class="text-[9px] text-rose-600 font-bold">Menolak</span>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                        {{-- Waktu Proses --}}
                                        <td class="px-4 py-3.5 text-slate-600 text-[11px] whitespace-nowrap">
                                            @if($primaryTrx->payment_status === \App\Models\Transaksi::STATUS_VERIFIED && $primaryTrx->verified_at)
                                                {{ $primaryTrx->verified_at->format('d/m/Y H:i') }}
                                            @elseif($primaryTrx->payment_status === \App\Models\Transaksi::STATUS_REJECTED && $primaryTrx->rejected_at)
                                                {{ $primaryTrx->rejected_at->format('d/m/Y H:i') }}
                                            @else
                                                {{ $primaryTrx->updated_at?->format('d/m/Y H:i') ?? '-' }}
                                            @endif
                                        </td>
                                        {{-- Catatan / Alasan --}}
                                        <td class="px-4 py-3.5">
                                            @if($allRejections)
                                                <div class="text-[10px] text-rose-700 bg-rose-50 p-1.5 rounded-lg border border-rose-100 font-medium max-w-xs leading-tight">
                                                    <span class="font-bold">Alasan tolak:</span> {{ $allRejections }}
                                                </div>
                                            @elseif($allNotes)
                                                <div class="text-[10px] text-slate-600 bg-slate-50 p-1.5 rounded-lg border border-slate-100 font-medium max-w-xs truncate" title="{{ $allNotes }}">
                                                    {{ $allNotes }}
                                                </div>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                        {{-- Aksi (Rincian) --}}
                                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                            <button type="button"
                                                @click="openDetailModal({{ json_encode($trxJson) }})"
                                                class="px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-[10px] font-bold transition flex items-center justify-center gap-1 mx-auto cursor-pointer">
                                                <svg class="w-3 h-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <span>Rincian</span>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif

            {{-- Pagination --}}
            @if($transaksis->hasPages())
                <div class="px-5 py-4 border-t border-slate-100">
                    {{ $transaksis->links() }}
                </div>
            @endif

        </div>

        {{-- MODAL RINCIAN PEMBAYARAN (CS & TIM) --}}
        <div x-show="detailModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="detailModal = false"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 z-10 space-y-5" x-show="selectedTrx">
                    {{-- Modal Header --}}
                    <div class="flex items-start justify-between pb-3 border-b border-slate-100">
                        <div>
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Rincian Pembayaran CS</div>
                            <h3 class="text-base font-bold text-slate-900 mt-0.5" x-text="'Transaksi #' + (selectedTrx ? selectedTrx.id : '')"></h3>
                        </div>
                        <button type="button" @click="detailModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {{-- Status Banner --}}
                    <template x-if="selectedTrx">
                        <div>
                            <div class="p-3.5 rounded-xl border flex items-center justify-between"
                                 :class="{
                                     'bg-emerald-50 border-emerald-200 text-emerald-900': selectedTrx.status === 'verified',
                                     'bg-amber-50 border-amber-200 text-amber-900': selectedTrx.status === 'pending',
                                     'bg-rose-50 border-rose-200 text-rose-900': selectedTrx.status === 'rejected'
                                 }">
                                <div>
                                    <div class="text-[10px] font-bold uppercase tracking-wider opacity-75">Status Transaksi</div>
                                    <div class="text-sm font-extrabold" x-text="selectedTrx.status === 'verified' ? 'Diverifikasi CS (Sah / Lunas)' : (selectedTrx.status === 'pending' ? 'Menunggu Verifikasi CS' : 'Ditolak CS')"></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[10px] font-bold uppercase tracking-wider opacity-75" x-text="selectedTrx.items && selectedTrx.items.length > 1 ? 'Total Nominal Gabungan' : 'Nominal Transaksi Ini'"></div>
                                    <div class="text-base font-extrabold" x-text="'Rp ' + selectedTrx.nominal"></div>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Detail Breakdown Card --}}
                    <template x-if="selectedTrx">
                        <div class="space-y-3 text-xs">
                            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100 space-y-2">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Informasi Calon Mahasiswa</div>
                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Nama Calon Mahasiswa:</span>
                                        <span class="font-bold text-slate-800" x-text="selectedTrx.prospek_name"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Asal Sekolah / Mitra:</span>
                                        <span class="text-slate-700 font-semibold" x-text="selectedTrx.prospek_school"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">PIC / Kontak:</span>
                                        <span class="font-semibold text-slate-700" x-text="selectedTrx.prospek_pic"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Program Studi:</span>
                                        <span class="text-slate-800 font-semibold text-indigo-700" x-text="selectedTrx.prospek_prodi"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Kelas:</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold"
                                              :class="selectedTrx.prospek_kelas === 'Karyawan' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-slate-200 text-slate-800'"
                                              x-text="'Kelas ' + (selectedTrx.prospek_kelas || 'Reguler')"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Detail Pembayaran & Komponen</div>
                                    <span class="text-[10px] text-purple-700 bg-purple-50 px-2 py-0.5 rounded-md font-bold border border-purple-100" x-text="selectedTrx.items && selectedTrx.items.length > 1 ? (selectedTrx.items.length + ' Item Transaksi Digabung') : ('Item: ' + selectedTrx.jenis)"></span>
                                </div>

                                {{-- Rincian Multi-Item (Jika transaksi gabungan) --}}
                                <template x-if="selectedTrx.items && selectedTrx.items.length > 1">
                                    <div class="space-y-2">
                                        <template x-for="(item, idx) in selectedTrx.items" :key="item.id || idx">
                                            <div class="p-3 bg-white rounded-xl border border-slate-200 flex items-center justify-between shadow-2xs">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-xs border border-blue-200 shrink-0">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                                    </div>
                                                    <div>
                                                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Komponen:</div>
                                                        <div class="text-xs font-bold text-slate-900" x-text="item.jenis"></div>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Nominal:</div>
                                                    <div class="text-xs font-black text-emerald-700" x-text="'Rp ' + (item.nominal_formatted || item.nominal)"></div>
                                                </div>
                                            </div>
                                        </template>
                                        <div class="p-2.5 bg-blue-50/80 rounded-xl border border-blue-200 flex items-center justify-between text-xs">
                                            <span class="font-bold text-blue-900" x-text="'Total Keseluruhan (' + selectedTrx.items.length + ' Item):'"></span>
                                            <span class="font-black text-blue-900" x-text="'Rp ' + selectedTrx.nominal"></span>
                                        </div>
                                    </div>
                                </template>

                                {{-- Rincian Single Item --}}
                                <template x-if="!selectedTrx.items || selectedTrx.items.length <= 1">
                                    <div class="p-3.5 bg-white rounded-xl border border-slate-200 flex items-center justify-between shadow-2xs">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-xs border border-blue-200">
                                                <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            </div>
                                            <div>
                                                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Komponen Transaksi:</div>
                                                <div class="text-sm font-extrabold text-slate-900" x-text="selectedTrx.jenis"></div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Nominal Pengajuan:</div>
                                            <div class="text-sm font-black text-emerald-700" x-text="'Rp ' + selectedTrx.nominal"></div>
                                        </div>
                                    </div>
                                </template>

                                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-200/60">
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Kategori Transaksi:</span>
                                        <span class="font-bold text-indigo-700" x-text="selectedTrx.kategori"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Metode Pembayaran:</span>
                                        <span class="font-semibold text-slate-800" x-text="selectedTrx.metode"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Tanggal Bayar:</span>
                                        <span class="text-slate-700" x-text="selectedTrx.tanggal + (selectedTrx.waktu ? ' ' + selectedTrx.waktu : '')"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Sales Penanggung Jawab:</span>
                                        <span class="text-slate-700 font-medium" x-text="selectedTrx.sales_name"></span>
                                    </div>
                                </div>

                                <template x-if="selectedTrx.notes">
                                    <div class="pt-2 border-t border-slate-200/60">
                                        <span class="text-slate-400 block text-[10px]">Catatan / Info Rekening:</span>
                                        <div class="text-[11px] text-slate-700 mt-0.5 bg-white p-2 rounded-lg border border-slate-200" x-text="selectedTrx.notes"></div>
                                    </div>
                                </template>
                            </div>

                            {{-- Verifikasi CS Log --}}
                            <div class="p-3.5 rounded-xl border border-slate-100 bg-slate-50 space-y-1.5">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Riwayat Verifikasi CS</div>
                                <template x-if="selectedTrx.verifier_name">
                                    <div class="text-xs text-emerald-800">
                                        Disetujui oleh <strong x-text="selectedTrx.verifier_name"></strong> pada <span x-text="selectedTrx.verified_at"></span>.
                                    </div>
                                </template>
                                <template x-if="selectedTrx.rejecter_name">
                                    <div class="text-xs text-rose-800">
                                        Ditolak oleh <strong x-text="selectedTrx.rejecter_name"></strong> pada <span x-text="selectedTrx.rejected_at"></span>.
                                        <div class="text-[11px] text-rose-700 mt-1">Alasan: <span class="italic" x-text="selectedTrx.rejection_reason || '-'"></span></div>
                                    </div>
                                </template>
                                <template x-if="!selectedTrx.verifier_name && !selectedTrx.rejecter_name">
                                    <div class="text-xs text-amber-800 italic">
                                        Transaksi sedang menunggu pemeriksaan dan approval oleh tim CS.
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Modal Actions --}}
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 gap-2">
                        <template x-if="selectedTrx && selectedTrx.prospek_id">
                            <a :href="'/prospek/' + selectedTrx.prospek_id" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 transition">
                                Buka Profil Prospek
                            </a>
                        </template>
                        <button type="button" @click="detailModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer ml-auto">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL TOLAK PEMBAYARAN --}}
        <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="rejectModal = false; rejectId = null; rejectReason = ''"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Tolak Pembayaran</h3>
                                <p class="text-[11px] text-slate-500">Konfirmasi penolakan transaksi calon mahasiswa</p>
                            </div>
                        </div>
                        <button type="button" @click="rejectModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <p class="text-xs text-slate-600 mb-3">Pembayaran akan ditolak dan Sales terkait akan mendapatkan notifikasi. Transaksi ini <strong>tidak</strong> akan dihitung sebagai closing.</p>

                    <!-- Info Ringkas Transaksi yang Ditolak -->
                    <div class="p-3 bg-rose-50/70 rounded-xl border border-rose-100 text-xs mb-4 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 text-[11px]">Calon Mahasiswa:</span>
                            <span class="font-bold text-slate-800 text-[11px]" x-text="rejectName"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 text-[11px]">Nominal Pembayaran:</span>
                            <span class="font-black text-rose-700 text-xs" x-text="'Rp ' + rejectNominal"></span>
                        </div>
                    </div>

                    <form :action="rejectId ? '{{ url('/cs/verifikasi') }}/' + rejectId + '/reject' : '#'" method="POST">
                        @csrf
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Alasan Penolakan <span class="text-slate-400 font-normal">(opsional / disarankan)</span>
                                </label>
                                <textarea name="rejection_reason" x-model="rejectReason" rows="3"
                                    placeholder="Contoh: Bukti transfer tidak valid, mutasi bank tidak ditemukan, nominal kurang, dsb."
                                    class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition"></textarea>
                            </div>
                            <div class="flex gap-2 justify-end pt-3 border-t border-slate-100">
                                <button type="button" @click="rejectModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                                    Batal
                                </button>
                                <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <span>Tolak Pembayaran</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script>
        function confirmVerifyPayment(form, name, nominal) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Verifikasi Pembayaran',
                    html: `<div class="text-xs text-slate-600">
                            <p class="mb-3">Yakin ingin memverifikasi pembayaran ini? Calon mahasiswa akan dinyatakan <strong>LUNAS</strong> dan masuk ke target Closing Sales.</p>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-left space-y-1.5 text-xs">
                                <div class="flex justify-between"><span class="text-slate-500">Calon Mahasiswa:</span> <span class="font-bold text-slate-800">${name}</span></div>
                                <div class="flex justify-between"><span class="text-slate-500">Nominal:</span> <span class="font-bold text-emerald-700">Rp ${nominal}</span></div>
                            </div>
                           </div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#059669',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Verifikasi Sekarang',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-2xl border border-slate-100 p-6',
                        title: 'text-slate-900 font-bold text-base',
                        confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2.5 shadow-xs',
                        cancelButton: 'rounded-xl text-xs font-semibold px-4 py-2.5'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else {
                if (confirm('Yakin memverifikasi pembayaran ini? Prospek akan dinyatakan LUNAS.')) {
                    form.submit();
                }
            }
        }
    </script>

</x-app-layout>
