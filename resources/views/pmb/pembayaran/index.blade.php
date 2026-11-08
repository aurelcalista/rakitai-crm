@php
    $pageTitle = 'Data Pembayaran';
    $pageSubtitle = 'Monitoring dan Verifikasi Transaksi PMB UCIC';
    $userRole = strtolower(auth()->user()->role ?? '');
@endphp

<x-app-layout :title="'Data Pembayaran PMB - CRM UCIC'">
    <div class="space-y-6" x-data="{
        verifyModalOpen: false,
        rejectModalOpen: false,
        detailModalOpen: false,
        advancedFilterOpen: {{ request()->hasAny(['metode', 'start_date', 'end_date']) ? 'true' : 'false' }},
        selectedTransaksi: {},
        verifyUrl: '',
        rejectUrl: '',
        openVerify(t) {
            this.selectedTransaksi = t;
            this.verifyUrl = '{{ url('pmb/pembayaran') }}/' + t.id + '/verify';
            this.verifyModalOpen = true;
        },
        openReject(t) {
            this.selectedTransaksi = t;
            this.rejectUrl = '{{ url('pmb/pembayaran') }}/' + t.id + '/reject';
            this.rejectModalOpen = true;
        },
        openDetail(t) {
            this.selectedTransaksi = t;
            this.detailModalOpen = true;
        }
    }">

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between text-sm shadow-xs transition-all animate-fade-in">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="font-bold text-xs">{{ session('success') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 p-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl flex items-center justify-between text-sm shadow-xs transition-all animate-fade-in">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <span class="font-bold text-xs">{{ session('error') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-800 p-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <!-- 4 Stat Cards Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Total Diterima -->
            <div class="group bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md hover:-translate-y-1 hover:border-blue-300 transition-all duration-300 flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#0b3d82] flex items-center justify-center shrink-0 border border-blue-100 group-hover:bg-[#0b3d82] group-hover:text-white group-hover:scale-110 transition-all duration-300 shadow-2xs">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight truncate font-display">
                        Rp {{ number_format($totalDiterima, 0, ',', '.') }}
                    </h3>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-0.5 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#0b3d82]"></span>
                        TOTAL DITERIMA
                    </p>
                </div>
            </div>

            <!-- 2. Berhasil -->
            <div class="group bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md hover:-translate-y-1 hover:border-emerald-300 transition-all duration-300 flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100 group-hover:bg-emerald-600 group-hover:text-white group-hover:scale-110 transition-all duration-300 shadow-2xs">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-2xl font-black text-emerald-600 tracking-tight font-display">
                        {{ number_format($countBerhasil, 0, ',', '.') }}
                    </h3>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-0.5 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        BERHASIL
                    </p>
                </div>
            </div>

            <!-- 3. Pending -->
            <div class="group bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md hover:-translate-y-1 hover:border-amber-300 transition-all duration-300 flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100 group-hover:bg-amber-500 group-hover:text-white group-hover:scale-110 transition-all duration-300 shadow-2xs">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-2xl font-black text-amber-600 tracking-tight font-display">
                        {{ number_format($countPending, 0, ',', '.') }}
                    </h3>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-0.5 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        PENDING
                    </p>
                </div>
            </div>

            <!-- 4. Gagal/Batal -->
            <div class="group bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md hover:-translate-y-1 hover:border-rose-300 transition-all duration-300 flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-100 group-hover:bg-rose-600 group-hover:text-white group-hover:scale-110 transition-all duration-300 shadow-2xs">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-2xl font-black text-rose-600 tracking-tight font-display">
                        {{ number_format($countGagal, 0, ',', '.') }}
                    </h3>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-0.5 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        GAGAL / BATAL
                    </p>
                </div>
            </div>
        </div>

        <!-- Main Card: Data Pembayaran PMB -->
        <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden transition-all duration-300">
            
            <!-- Card Header: Title & Page Size Selector -->
            <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#0b3d82]"></span>
                        <h2 class="text-base sm:text-lg font-extrabold text-slate-900 font-display">Data Pembayaran PMB</h2>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Daftar konfirmasi dan riwayat transaksi pembayaran calon mahasiswa</p>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <!-- Page Size Selector -->
                    <form action="{{ route('pmb.pembayaran.index') }}" method="GET" id="perPageForm">
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <input type="hidden" name="quick_filter" value="{{ request('quick_filter', 'all') }}">
                        <input type="hidden" name="metode" value="{{ request('metode') }}">
                        <select name="per_page" onchange="document.getElementById('perPageForm').submit()" class="text-xs font-bold rounded-xl border border-slate-200 bg-slate-50/80 px-3 py-2 text-slate-700 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 cursor-pointer shadow-2xs">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 / hal</option>
                            <option value="25" {{ request('per_page', 25) == 25 ? 'selected' : '' }}>25 / hal</option>
                            <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>50 / hal</option>
                            <option value="100" {{ request('per_page', 100) == 100 ? 'selected' : '' }}>100 / hal</option>
                        </select>
                    </form>

                    <!-- Export Button -->
                    <a 
                        href="{{ route('pmb.pembayaran.export', request()->query()) }}" 
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs hover:shadow-md active:scale-[0.98] transition-all cursor-pointer"
                        title="Export CSV Transaksi"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Export CSV</span>
                    </a>
                </div>
            </div>

            <!-- Search, Filter & Quick Pills Toolbar -->
            <div class="p-5 sm:p-6 pt-4 space-y-4">
                <form action="{{ route('pmb.pembayaran.index') }}" method="GET" id="searchFilterForm">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                    <input type="hidden" name="quick_filter" value="{{ request('quick_filter', 'all') }}">

                    <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2.5">
                        <!-- Search Input -->
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input 
                                type="text" 
                                name="search" 
                                value="{{ request('search') }}" 
                                placeholder="Cari Order ID, nama pendaftar, email..." 
                                class="w-full text-xs rounded-xl border border-slate-200 pl-10 pr-4 py-2.5 bg-slate-50/70 text-slate-800 placeholder-slate-400 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs"
                            >
                        </div>

                        <!-- Blue Filter Button -->
                        <button 
                            type="submit" 
                            class="inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl text-xs font-bold bg-[#0b3d82] hover:bg-[#082a5c] text-white shadow-xs hover:shadow-md active:scale-95 transition cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <span>Filter</span>
                        </button>

                        <!-- Filter Lanjutan Toggle Button -->
                        <button 
                            type="button" 
                            @click="advancedFilterOpen = !advancedFilterOpen"
                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold border border-blue-300 text-[#0b3d82] hover:bg-blue-50 transition cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                            <span>Filter Lanjutan</span>
                        </button>

                        @if(request()->hasAny(['search', 'metode', 'start_date', 'end_date', 'quick_filter']))
                            <a 
                                href="{{ route('pmb.pembayaran.index') }}" 
                                class="inline-flex items-center justify-center px-3 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                                title="Reset semua filter"
                            >
                                Reset
                            </a>
                        @endif
                    </div>

                    <!-- Collapsible Advanced Filter Panel -->
                    <div x-show="advancedFilterOpen" x-cloak class="pt-3.5 grid grid-cols-1 sm:grid-cols-3 gap-3.5 border-t border-slate-100 mt-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Metode Pembayaran</label>
                            <select name="metode" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-slate-700">
                                <option value="all" {{ request('metode', 'all') === 'all' ? 'selected' : '' }}>Semua Metode</option>
                                @foreach($metodeList as $m)
                                    <option value="{{ $m }}" {{ request('metode') === $m ? 'selected' : '' }}>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Dari Tanggal</label>
                            <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-slate-700">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                            <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-slate-700">
                        </div>
                    </div>
                </form>

                <!-- Row: Result Count & Quick Filter Badges -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                    <div class="text-xs text-slate-500">
                        @if($transaksiList->total() > 0)
                            Menampilkan <span class="font-bold text-slate-800">{{ $transaksiList->firstItem() }} - {{ $transaksiList->lastItem() }}</span> dari <span class="font-bold text-slate-800">{{ number_format($transaksiList->total(), 0, ',', '.') }}</span> transaksi
                        @else
                            Menampilkan 0 data
                        @endif
                    </div>

                    <!-- Quick Filter Badges -->
                    <div class="flex flex-wrap items-center gap-2">
                        @php
                            $currentQuick = request('quick_filter', 'all');
                        @endphp
                        
                        <!-- Semua -->
                        <a 
                            href="{{ route('pmb.pembayaran.index', array_merge(request()->query(), ['quick_filter' => 'all'])) }}"
                            class="px-3 py-1 rounded-full text-xs font-bold transition-all border {{ $currentQuick === 'all' ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}"
                        >
                            Semua
                        </a>

                        <!-- Berhasil (Green) -->
                        <a 
                            href="{{ route('pmb.pembayaran.index', array_merge(request()->query(), ['quick_filter' => 'berhasil'])) }}"
                            class="px-3 py-1 rounded-full text-xs font-bold transition-all border {{ $currentQuick === 'berhasil' ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' : 'bg-white text-emerald-700 border-emerald-300 hover:bg-emerald-50' }}"
                        >
                            ✓ Berhasil
                        </a>

                        <!-- Pending (Amber) -->
                        <a 
                            href="{{ route('pmb.pembayaran.index', array_merge(request()->query(), ['quick_filter' => 'pending'])) }}"
                            class="px-3 py-1 rounded-full text-xs font-bold transition-all border {{ $currentQuick === 'pending' ? 'bg-amber-500 text-white border-amber-500 shadow-xs' : 'bg-white text-amber-700 border-amber-300 hover:bg-amber-50' }}"
                        >
                            ⏱ Pending
                        </a>

                        <!-- Gagal (Red) -->
                        <a 
                            href="{{ route('pmb.pembayaran.index', array_merge(request()->query(), ['quick_filter' => 'gagal'])) }}"
                            class="px-3 py-1 rounded-full text-xs font-bold transition-all border {{ $currentQuick === 'gagal' ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-white text-rose-700 border-rose-300 hover:bg-rose-50' }}"
                        >
                            ✕ Gagal
                        </a>

                        <!-- Hari Ini (Blue) -->
                        <a 
                            href="{{ route('pmb.pembayaran.index', array_merge(request()->query(), ['quick_filter' => 'hari_ini'])) }}"
                            class="px-3 py-1 rounded-full text-xs font-bold transition-all border {{ $currentQuick === 'hari_ini' ? 'bg-[#0b3d82] text-white border-[#0b3d82] shadow-xs' : 'bg-white text-[#0b3d82] border-blue-300 hover:bg-blue-50' }}"
                        >
                            📅 Hari Ini
                        </a>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs whitespace-nowrap min-w-[950px]">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider text-[11px] border-b border-slate-100">
                            <th class="py-3.5 px-4 text-center w-12">NO</th>
                            <th class="py-3.5 px-4">ORDER ID</th>
                            <th class="py-3.5 px-4">NAMA PENDAFTAR</th>
                            <th class="py-3.5 px-4">NOMINAL</th>
                            <th class="py-3.5 px-4">DETAIL</th>
                            <th class="py-3.5 px-4">METODE</th>
                            <th class="py-3.5 px-4">STATUS</th>
                            <th class="py-3.5 px-4">TGL. TRANSAKSI</th>
                            <th class="py-3.5 px-4 text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($transaksiList as $index => $trx)
                            @php
                                $statusRaw = strtolower($trx->payment_status ?? 'pending');
                                $isVerified = in_array($statusRaw, ['verified', 'paid', 'success', 'settlement', 'capture']);
                                $isRejected = in_array($statusRaw, ['rejected', 'failed', 'batal', 'expire', 'deny', 'cancel']);
                                $isPending = !$isVerified && !$isRejected;

                                $statusLabelIndo = match(true) {
                                    $isVerified => 'Berhasil',
                                    $isRejected => 'Gagal',
                                    default => 'Pending',
                                };

                                $rawMetode = strtoupper($trx->metode_pembayaran ?? '');
                                $paymentType = strtolower($trx->payment_type ?? '');

                                $metodeLabel = match(true) {
                                    str_contains($paymentType, 'echannel') || str_contains($paymentType, 'mandiri') => 'Mandiri VA',
                                    str_contains($paymentType, 'bca') => 'BCA VA',
                                    str_contains($paymentType, 'bni') => 'BNI VA',
                                    str_contains($paymentType, 'bri') => 'BRI VA',
                                    str_contains($paymentType, 'permata') => 'Permata VA',
                                    str_contains($paymentType, 'cimb') => 'CIMB VA',
                                    str_contains($paymentType, 'danamon') => 'Danamon VA',
                                    str_contains($paymentType, 'qris') => 'QRIS',
                                    str_contains($paymentType, 'gopay') => 'GoPay',
                                    str_contains($paymentType, 'shopee') => 'ShopeePay',
                                    str_contains($paymentType, 'credit_card') => 'Kartu Kredit',
                                    str_contains($paymentType, 'bank_transfer') || str_contains($paymentType, 'va') => 'Virtual Account',
                                    $rawMetode === 'MIDTRANS' => 'Virtual Account',
                                    $rawMetode === 'BANK_TRANSFER' => 'Transfer Bank',
                                    $rawMetode === 'TRANSFER_MANUAL' || str_contains($rawMetode, 'MANUAL') => 'Transfer Manual',
                                    $rawMetode === 'OFFLINE' => 'Tunai / Kasir',
                                    !empty($trx->metode_pembayaran) => ucwords(str_replace('_', ' ', strtolower($trx->metode_pembayaran))),
                                    default => 'Transfer Bank',
                                };

                                $metodeClass = match(true) {
                                    str_contains($metodeLabel, 'VA') || str_contains($metodeLabel, 'Virtual') => 'bg-blue-50 text-[#0b3d82] border border-blue-200',
                                    $metodeLabel === 'QRIS' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                    $metodeLabel === 'GoPay' => 'bg-cyan-50 text-cyan-700 border border-cyan-200',
                                    str_contains($metodeLabel, 'Transfer') => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
                                    str_contains($metodeLabel, 'Manual') => 'bg-amber-50 text-amber-700 border border-amber-200',
                                    str_contains($metodeLabel, 'Kasir') || str_contains($metodeLabel, 'Tunai') => 'bg-slate-100 text-slate-700 border border-slate-200',
                                    default => 'bg-slate-100 text-slate-700 border border-slate-200',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <!-- No -->
                                <td class="py-4 px-4 text-center font-bold text-slate-400">
                                    {{ $transaksiList->firstItem() + $index }}
                                </td>

                                <!-- Order ID -->
                                <td class="py-4 px-4">
                                    <span class="font-mono font-bold text-[#0b3d82]">
                                        {{ $trx->order_id ?: ('TRX-' . $trx->id) }}
                                    </span>
                                </td>

                                <!-- Nama Pendaftar -->
                                <td class="py-4 px-4">
                                    @if($trx->prospek)
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 font-bold flex items-center justify-center shrink-0 text-xs border border-blue-200">
                                                {{ strtoupper(substr($trx->prospek->name, 0, 2)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <a href="{{ route('pmb.pendaftar.show', $trx->prospek->id) }}" class="font-bold text-slate-900 hover:text-blue-600 transition block">
                                                    {{ $trx->prospek->name }}
                                                </a>
                                                <span class="text-slate-400 text-[11px] block truncate">
                                                    {{ $trx->prospek->email ?: ($trx->prospek->whatsapp . '@pmb.cic.ac.id') }}
                                                </span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="font-bold text-slate-600">Pendaftar #{{ $trx->prospek_id }}</span>
                                    @endif
                                </td>

                                <!-- Nominal -->
                                <td class="py-4 px-4">
                                    <span class="font-extrabold text-slate-900 font-display">
                                        Rp {{ number_format($trx->nominal, 0, ',', '.') }}
                                    </span>
                                </td>

                                <!-- Detail Pembayaran -->
                                <td class="py-4 px-4 text-slate-600">
                                    <span class="font-medium">{{ $trx->detail_pembayaran ?: ($trx->jenis ?? '• Biaya Pendaftaran PMB') }}</span>
                                </td>

                                <!-- Metode Pembayaran -->
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-bold tracking-wide {{ $metodeClass }}">
                                        {{ $metodeLabel }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-4 px-4">
                                    @if($isVerified)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Berhasil
                                        </span>
                                    @elseif($isPending)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Pending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            Gagal
                                        </span>
                                    @endif
                                </td>

                                <!-- Tgl Transaksi -->
                                <td class="py-4 px-4 text-slate-500 text-[11px] font-medium">
                                    {{ $trx->tanggal ? \Carbon\Carbon::parse($trx->tanggal)->format('d M Y, H:i') : ($trx->created_at ? $trx->created_at->format('d M Y, H:i') : '-') }}
                                </td>

                                <!-- Aksi -->
                                <td class="py-4 px-4 text-center">
                                    <div class="inline-flex items-center justify-center gap-1.5">
                                        <!-- Cek Status Midtrans (Pending with Order ID) -->
                                        @if($isPending && !empty($trx->order_id))
                                            <form action="{{ route('pmb.pembayaran.sync', $trx->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button 
                                                    type="submit" 
                                                    class="w-7 h-7 rounded-lg border border-blue-200 bg-blue-50 text-[#0b3d82] hover:bg-[#0b3d82] hover:text-white flex items-center justify-center transition shadow-2xs cursor-pointer"
                                                    title="Cek Status dari Server Midtrans"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Verify Button (Green Check) -->
                                        @if(!$isVerified)
                                            <button 
                                                type="button" 
                                                @click="openVerify({{ json_encode([
                                                    'id' => $trx->id,
                                                    'order_id' => $trx->order_id ?: ('TRX-' . $trx->id),
                                                    'name' => $trx->prospek?->name ?? 'Pendaftar #' . $trx->prospek_id,
                                                    'nominal' => number_format($trx->nominal, 0, ',', '.'),
                                                    'metode' => $trx->metode_pembayaran ?? 'OFFLINE'
                                                ]) }})"
                                                class="w-7 h-7 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white flex items-center justify-center transition shadow-2xs cursor-pointer"
                                                title="Verifikasi Pembayaran"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        @endif

                                        <!-- Reject Button (Red X) -->
                                        @if(!$isRejected)
                                            <button 
                                                type="button" 
                                                @click="openReject({{ json_encode([
                                                    'id' => $trx->id,
                                                    'order_id' => $trx->order_id ?: ('TRX-' . $trx->id),
                                                    'name' => $trx->prospek?->name ?? 'Pendaftar #' . $trx->prospek_id,
                                                    'nominal' => number_format($trx->nominal, 0, ',', '.')
                                                ]) }})"
                                                class="w-7 h-7 rounded-lg border border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white flex items-center justify-center transition shadow-2xs cursor-pointer"
                                                title="Tolak / Batalkan Transaksi"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        @endif

                                        <!-- Detail Button (Eye) -->
                                        <button 
                                            type="button" 
                                            @click="openDetail({{ json_encode([
                                                'id' => $trx->id,
                                                'order_id' => $trx->order_id ?: ('TRX-' . $trx->id),
                                                'name' => $trx->prospek?->name ?? 'Pendaftar #' . $trx->prospek_id,
                                                'email' => $trx->prospek?->email ?? '-',
                                                'nominal' => number_format($trx->nominal, 0, ',', '.'),
                                                'detail' => $trx->detail_pembayaran ?: ($trx->jenis ?? '-'),
                                                'metode' => $metodeLabel,
                                                'status' => $statusLabelIndo,
                                                'tanggal' => $trx->tanggal ? \Carbon\Carbon::parse($trx->tanggal)->format('d M Y, H:i') : '-',
                                                'rejection_reason' => $trx->rejection_reason ?? null
                                            ]) }})"
                                            class="w-7 h-7 rounded-lg border border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-800 hover:text-white flex items-center justify-center transition shadow-2xs cursor-pointer"
                                            title="Lihat Detail Transaksi"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-slate-400">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    <p class="font-bold text-slate-700 text-sm">Tidak ada data transaksi pembayaran</p>
                                    <p class="text-xs text-slate-400 mt-1">Gunakan kata kunci pencarian atau filter status yang berbeda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            @if($transaksiList->hasPages())
                <div class="p-4 sm:px-6 border-t border-slate-100 bg-slate-50/50">
                    {{ $transaksiList->links() }}
                </div>
            @endif

        </div>

        <!-- Modal: Verifikasi Pembayaran -->
        <div 
            x-show="verifyModalOpen" 
            x-cloak
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true"
        >
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="verifyModalOpen = false"></div>

                <div 
                    x-show="verifyModalOpen" 
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all max-w-md w-full border border-slate-100"
                >
                    <form :action="verifyUrl" method="POST">
                        @csrf
                        <div class="p-6">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4 border border-emerald-100 shadow-2xs">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>

                            <h3 class="text-lg font-bold text-slate-900 font-display">Verifikasi Pembayaran PMB?</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Anda akan mengonfirmasi bahwa pembayaran berikut telah sah diterima:
                            </p>

                            <div class="mt-4 bg-slate-50 rounded-2xl p-4 border border-slate-100 space-y-2 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Order ID:</span>
                                    <span class="font-mono font-bold text-slate-800" x-text="selectedTransaksi.order_id"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Nama Pendaftar:</span>
                                    <span class="font-semibold text-slate-800" x-text="selectedTransaksi.name"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Nominal:</span>
                                    <span class="font-extrabold text-emerald-600 font-display" x-text="'Rp ' + selectedTransaksi.nominal"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Metode:</span>
                                    <span class="font-bold text-slate-700" x-text="selectedTransaksi.metode"></span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-50/80 px-6 py-4 flex items-center justify-end gap-2.5 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="verifyModalOpen = false" 
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs hover:shadow-md transition cursor-pointer"
                            >
                                Ya, Verifikasi Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal: Tolak / Batalkan Pembayaran -->
        <div 
            x-show="rejectModalOpen" 
            x-cloak
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true"
        >
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="rejectModalOpen = false"></div>

                <div 
                    x-show="rejectModalOpen" 
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all max-w-md w-full border border-slate-100"
                >
                    <form :action="rejectUrl" method="POST">
                        @csrf
                        <div class="p-6">
                            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-4 border border-rose-100 shadow-2xs">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>

                            <h3 class="text-lg font-bold text-slate-900 font-display">Tolak Pembayaran PMB?</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Transaksi <strong class="font-mono text-slate-700" x-text="selectedTransaksi.order_id"></strong> akan ditolak/dibatalkan. Mohon cantumkan alasan penolakan:
                            </p>

                            <div class="mt-4">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Alasan Penolakan <span class="text-rose-500">*</span></label>
                                <textarea 
                                    name="rejection_reason" 
                                    rows="3" 
                                    required 
                                    placeholder="Contoh: Bukti transfer tidak terbaca / nominal tidak sesuai / transaksi duplikat..." 
                                    class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-rose-500 focus:ring-2 focus:ring-rose-100"
                                ></textarea>
                            </div>
                        </div>

                        <div class="bg-slate-50/80 px-6 py-4 flex items-center justify-end gap-2.5 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="rejectModalOpen = false" 
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs hover:shadow-md transition cursor-pointer"
                            >
                                Ya, Tolak Transaksi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal: Detail Lengkap Transaksi -->
        <div 
            x-show="detailModalOpen" 
            x-cloak
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true"
        >
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="detailModalOpen = false"></div>

                <div 
                    x-show="detailModalOpen" 
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all max-w-md w-full border border-slate-100"
                >
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#0b3d82] flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 font-display">Detail Transaksi Pembayaran</h3>
                        </div>
                        <button type="button" @click="detailModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-3 text-xs">
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Order ID:</span>
                            <span class="font-mono font-bold text-[#0b3d82]" x-text="selectedTransaksi.order_id"></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Nama Pendaftar:</span>
                            <span class="font-bold text-slate-900" x-text="selectedTransaksi.name"></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Email:</span>
                            <span class="text-slate-700" x-text="selectedTransaksi.email"></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Nominal:</span>
                            <span class="font-extrabold text-slate-900 font-display text-sm" x-text="'Rp ' + selectedTransaksi.nominal"></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Detail Item:</span>
                            <span class="font-medium text-slate-800" x-text="selectedTransaksi.detail"></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Metode:</span>
                            <span class="font-semibold text-slate-800" x-text="selectedTransaksi.metode"></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Status:</span>
                            <span class="font-bold" x-text="selectedTransaksi.status"></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Tgl. Transaksi:</span>
                            <span class="text-slate-700" x-text="selectedTransaksi.tanggal"></span>
                        </div>
                        <template x-if="selectedTransaksi.rejection_reason">
                            <div class="p-3 bg-rose-50 rounded-xl border border-rose-100 text-rose-800">
                                <span class="font-bold block text-[11px] mb-0.5">Alasan Penolakan:</span>
                                <span x-text="selectedTransaksi.rejection_reason"></span>
                            </div>
                        </template>

                        <template x-if="selectedTransaksi.metode === 'MIDTRANS' && selectedTransaksi.order_id">
                            <div class="p-3.5 bg-blue-50/80 rounded-2xl border border-blue-200/80 flex items-center justify-between gap-3 text-xs">
                                <div>
                                    <span class="font-bold text-[#0b3d82] block">Portal Bayar Midtrans:</span>
                                    <span class="text-blue-600 text-[11px]">Buka halaman pembayaran online CAMABA</span>
                                </div>
                                <a :href="'{{ url('pmb/bayar') }}/' + selectedTransaksi.order_id" target="_blank" class="px-3 py-1.5 rounded-lg bg-[#0b3d82] hover:bg-[#082a5c] text-white font-bold text-[11px] shadow-2xs transition inline-flex items-center gap-1 shrink-0">
                                    <span>Buka Portal</span>
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </div>
                        </template>

                        <template x-if="selectedTransaksi.order_id && selectedTransaksi.status !== 'verified'">
                            <form :action="'{{ url('pmb/pembayaran') }}/' + selectedTransaksi.id + '/sync'" method="POST" class="mt-2">
                                @csrf
                                <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-[#0b3d82] hover:bg-[#082a5c] text-white font-bold text-xs flex items-center justify-center gap-1.5 transition shadow-xs cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>Cek & Sinkronkan Status Midtrans</span>
                                </button>
                            </form>
                        </template>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 flex items-center justify-end border-t border-slate-100">
                        <button type="button" @click="detailModalOpen = false" class="px-5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition cursor-pointer">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
