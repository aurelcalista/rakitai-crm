@php
    $pageTitle = 'Data Pembayaran';
    $pageSubtitle = 'Rekapitulasi dan Verifikasi Pembayaran PMB UCIC per User';
    $userRole = strtolower(auth()->user()->role ?? '');
@endphp

<x-app-layout :title="'Data Pembayaran PMB - CRM UCIC'">
    <div class="space-y-6" x-data="{
        verifyModalOpen: false,
        rejectModalOpen: false,
        detailModalOpen: false,
        expandedRow: null,
        advancedFilterOpen: {{ request()->hasAny(['metode', 'start_date', 'end_date']) ? 'true' : 'false' }},
        voucherManageOpen: {{ ($errors->has('name') || $errors->has('code') || $errors->has('discount_percentage') || $errors->has('is_active')) ? 'true' : 'false' }},
        voucherEditModalOpen: false,
        voucherUsersModalOpen: false,
        editingVoucher: { id: null, code: '', name: '', discount_percentage: 0, is_active: '1' },
        editingVoucherUrl: '',
        selectedVoucherForUsers: { code: '', name: '', percentage_formatted: '', is_active: true, usages: [] },
        openEditVoucher(v) {
            this.editingVoucher = {
                id: v.id,
                code: v.code,
                name: v.name || '',
                discount_percentage: v.discount_percentage,
                is_active: v.is_active ? '1' : '0'
            };
            this.editingVoucherUrl = '{{ url('pmb/pembayaran/vouchers') }}/' + v.id;
            this.voucherEditModalOpen = true;
        },
        openVoucherUsers(v) {
            this.selectedVoucherForUsers = v;
            this.voucherUsersModalOpen = true;
        },
        selectedCamaba: {
            id: null,
            name: '',
            email: '',
            whatsapp: '',
            no_registrasi: '',
            prodi: '',
            rekap: {
                total_kewajiban: 5000000,
                total_terbayar: 0,
                total_sisa: 5000000,
                is_lunas: false,
                status_keseluruhan: 'Belum Lunas',
                rincian: {
                    perlengkapan: { kewajiban: 600000, terbayar: 0, sisa: 600000, is_lunas: false, status: 'Belum Lunas' },
                    pendaftaran:  { kewajiban: 300000, terbayar: 0, sisa: 300000, is_lunas: false, status: 'Belum Lunas' },
                    dpp:          { kewajiban: 500000, terbayar: 0, sisa: 500000, is_lunas: false, status: 'Belum Lunas' },
                    dp:           { kewajiban: 500000, terbayar: 0, sisa: 500000, is_lunas: false, status: 'Belum Lunas' },
                    pelunasan:    { kewajiban: 3100000, terbayar: 0, sisa: 3100000, is_lunas: false, status: 'Belum Lunas' }
                }
            },
            transaksis: []
        },
        selectedTransaksi: {},
        verifyUrl: '',
        rejectUrl: '',
        openDetail(camaba) {
            this.selectedCamaba = camaba;
            this.detailModalOpen = true;
        },
        openVerify(t, camabaName) {
            this.selectedTransaksi = t;
            this.selectedTransaksi.camaba_name = camabaName || this.selectedCamaba?.name || 'Calon Mahasiswa';
            this.verifyUrl = '{{ url('pmb/pembayaran') }}/' + t.id + '/verify';
            this.verifyModalOpen = true;
        },
        openReject(t, camabaName) {
            this.selectedTransaksi = t;
            this.selectedTransaksi.camaba_name = camabaName || this.selectedCamaba?.name || 'Calon Mahasiswa';
            this.rejectUrl = '{{ url('pmb/pembayaran') }}/' + t.id + '/reject';
            this.rejectModalOpen = true;
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

        @if(session('info'))
            <div class="bg-blue-50 border border-blue-200 text-[#0b3d82] px-4 py-3 rounded-2xl flex items-center justify-between text-sm shadow-xs transition-all animate-fade-in">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#0b3d82] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="font-bold text-xs">{{ session('info') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-blue-600 hover:text-blue-800 p-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl flex items-start justify-between text-xs shadow-xs">
                <div class="space-y-1">
                    <span class="font-bold block">Periksa kembali input Anda:</span>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-800 p-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <!-- Top Breadcrumbs & Page Header (Matching Image 4) -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-[#0b3d82] text-white flex items-center justify-center shadow-md shadow-blue-900/20 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-display">Data Rekap Pembayaran PMB</h1>
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 mt-0.5">
                        <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition">Dashboard</a>
                        <span>&gt;</span>
                        <span>Data Rekapitulasi Keuangan</span>
                        <span>&gt;</span>
                        <span class="font-semibold text-slate-700">Pembayaran Pendaftaran PMB</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- Tahun Akademik Selector -->
                <div class="relative">
                    <select class="text-xs font-bold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-700 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 shadow-2xs cursor-pointer">
                        <option value="2025/2026" selected>2025/2026</option>
                        <option value="2026/2027">2026/2027</option>
                    </select>
                </div>

                <!-- Export CSV Button -->
                <a 
                    href="{{ route('pmb.pembayaran.export', request()->query()) }}" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs hover:shadow-md active:scale-95 transition-all cursor-pointer"
                    title="Export CSV Rekap Pembayaran Lengkap"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Export CSV</span>
                </a>
            </div>
        </div>

        @if($userRole === 'admin')
            <!-- Menu Tab Data Pembayaran Aktif vs Trash (Khusus Admin) -->
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200/80 pb-3">
                <a
                    href="{{ route('pmb.pembayaran.index') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-[#0b3d82] text-white shadow-xs transition"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>Data Pembayaran Aktif</span>
                </a>
                <a
                    href="{{ route('pmb.pembayaran.trash') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-white hover:bg-rose-50 text-slate-700 hover:text-rose-700 border border-slate-200 hover:border-rose-200 shadow-2xs transition"
                >
                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Trash (Tempat Sampah)</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ ($trashCount ?? 0) > 0 ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ $trashCount ?? 0 }}
                    </span>
                </a>
            </div>
        @endif

        <!-- Filter Data Card (Matching Image 4) -->
        <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs space-y-4">
            <!-- Header Filter -->
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0b3d82] flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 font-display">Filter Data</h3>
                    <p class="text-xs text-slate-500">Atur filter untuk melihat rekap pembayaran sesuai kebutuhan</p>
                </div>
            </div>

            <form action="{{ route('pmb.pembayaran.index') }}" method="GET" id="mainFilterForm" class="space-y-4">
                <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                <input type="hidden" name="tahap" value="{{ $activeTahap ?? request('tahap', 'all') }}">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 sm:gap-4">
                    <!-- 1. Periode Pembayaran -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Periode Pembayaran</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <select name="periode" class="w-full text-xs font-semibold rounded-xl border border-slate-200 pl-10 pr-4 py-2.5 bg-slate-50/70 text-slate-800 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                                <option value="all" {{ request('periode', 'all') === 'all' ? 'selected' : '' }}>Semua Periode</option>
                                <option value="today" {{ request('periode') === 'today' ? 'selected' : '' }}>Hari Ini</option>
                                <option value="this_week" {{ request('periode') === 'this_week' ? 'selected' : '' }}>Minggu Ini</option>
                                <option value="this_month" {{ request('periode') === 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                                <option value="this_year" {{ request('periode') === 'this_year' ? 'selected' : '' }}>Tahun Ini</option>
                            </select>
                        </div>
                    </div>

                    <!-- 2. Data Transaksi (Metode) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Data Transaksi</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                            </div>
                            <select name="metode" class="w-full text-xs font-semibold rounded-xl border border-slate-200 pl-10 pr-4 py-2.5 bg-slate-50/70 text-slate-800 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                                <option value="all" {{ request('metode', 'all') === 'all' ? 'selected' : '' }}>Semua Metode Pembayaran</option>
                                <optgroup label="Midtrans Payment Gateway">
                                    <option value="MIDTRANS_QRIS" {{ in_array(request('metode'), ['MIDTRANS_QRIS', 'QRIS']) ? 'selected' : '' }}>📱 QRIS (Midtrans)</option>
                                    <option value="MIDTRANS_VA" {{ in_array(request('metode'), ['MIDTRANS_VA', 'VA']) ? 'selected' : '' }}>🏦 Virtual Account (BCA, Mandiri, BNI, BRI)</option>
                                    <option value="MIDTRANS_EWALLET" {{ in_array(request('metode'), ['MIDTRANS_EWALLET', 'EWALLET']) ? 'selected' : '' }}>👛 E-Wallet (GoPay, ShopeePay)</option>
                                    <option value="MIDTRANS" {{ request('metode') === 'MIDTRANS' ? 'selected' : '' }}>🌐 Semua Pembayaran Midtrans</option>
                                </optgroup>
                                <optgroup label="Metode Lainnya">
                                    <option value="TRANSFER_MANUAL" {{ request('metode') === 'TRANSFER_MANUAL' ? 'selected' : '' }}>🏛️ Transfer Manual ke Rekening Kampus</option>
                                    <option value="OFFLINE" {{ in_array(request('metode'), ['OFFLINE', 'TUNAI', 'KASIR']) ? 'selected' : '' }}>💵 Kasir PMB / Tunai di Kampus</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <!-- 3. Status Transaksi -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Status Transaksi</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <select name="status" class="w-full text-xs font-semibold rounded-xl border border-slate-200 pl-10 pr-4 py-2.5 bg-slate-50/70 text-slate-800 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs">
                                <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>Semua Status</option>
                                <option value="lunas" {{ request('status') === 'lunas' ? 'selected' : '' }}>Berhasil / Lunas</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                                <option value="belum_lunas" {{ request('status') === 'belum_lunas' ? 'selected' : '' }}>Belum Lunas</option>
                                <option value="refund" {{ request('status') === 'refund' ? 'selected' : '' }}>Refund / Gagal</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Search Input & Reset / Terapkan Filter -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-1">
                    <!-- Search Input -->
                    <div class="relative flex-1 max-w-lg">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}" 
                            placeholder="Cari nama camaba, nomor registrasi, atau Order ID..." 
                            class="w-full text-xs rounded-xl border border-slate-200 pl-10 pr-4 py-2.5 bg-slate-50/70 text-slate-800 placeholder-slate-400 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs"
                        >
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2.5 self-end sm:self-auto">
                        <a 
                            href="{{ route('pmb.pembayaran.index') }}" 
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 transition cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Reset</span>
                        </a>

                        <button 
                            type="submit" 
                            class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl text-xs font-bold bg-[#0b3d82] hover:bg-[#082a5c] text-white shadow-xs hover:shadow-md active:scale-95 transition cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                            <span>Terapkan Filter</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- 5 Stage Metric Cards Row (Semua, Pendaftaran, Perlengkapan, DPP, Uang Gedung) -->
        @php
            $currentTahap = $activeTahap ?? request('tahap', 'all');
            $stageCards = [
                [
                    'key'                => 'all',
                    'label'              => 'Semua',
                    'sublabel'           => 'Semua Camaba',
                    'count'              => $countSemua ?? $rekapList->total(),
                    'activeClass'        => 'bg-[#0b3d82] border-[#0b3d82] text-white shadow-md shadow-blue-950/20',
                    'inactiveClass'      => 'bg-white border-slate-200/90 text-slate-800 hover:border-blue-300 hover:shadow-xs',
                    'iconActiveClass'    => 'bg-white/20 text-white',
                    'iconInactiveClass'  => 'bg-blue-50 text-[#0b3d82]',
                    'labelActiveClass'   => 'text-blue-100',
                    'countInactiveClass' => 'text-slate-900',
                    'iconPath'           => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                ],
                [
                    'key'                => 'pendaftaran',
                    'label'              => 'Pendaftaran',
                    'sublabel'           => 'Sampai Pendaftaran',
                    'count'              => $countStagePendaftaran ?? 0,
                    'activeClass'        => 'bg-sky-600 border-sky-600 text-white shadow-md shadow-sky-950/20',
                    'inactiveClass'      => 'bg-white border-slate-200/90 text-slate-800 hover:border-sky-300 hover:shadow-xs',
                    'iconActiveClass'    => 'bg-white/20 text-white',
                    'iconInactiveClass'  => 'bg-sky-50 text-sky-600',
                    'labelActiveClass'   => 'text-sky-100',
                    'countInactiveClass' => 'text-sky-600',
                    'iconPath'           => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
                ],
                [
                    'key'                => 'perlengkapan',
                    'label'              => 'Perlengkapan',
                    'sublabel'           => 'Sampai Perlengkapan',
                    'count'              => $countStagePerlengkapan ?? 0,
                    'activeClass'        => 'bg-amber-500 border-amber-500 text-white shadow-md shadow-amber-950/20',
                    'inactiveClass'      => 'bg-white border-slate-200/90 text-slate-800 hover:border-amber-300 hover:shadow-xs',
                    'iconActiveClass'    => 'bg-white/20 text-white',
                    'iconInactiveClass'  => 'bg-amber-50 text-amber-600',
                    'labelActiveClass'   => 'text-amber-100',
                    'countInactiveClass' => 'text-amber-600',
                    'iconPath'           => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
                ],
                [
                    'key'                => 'dpp',
                    'label'              => 'DPP',
                    'sublabel'           => 'Sampai DPP',
                    'count'              => $countStageDpp ?? 0,
                    'activeClass'        => 'bg-emerald-600 border-emerald-600 text-white shadow-md shadow-emerald-950/20',
                    'inactiveClass'      => 'bg-white border-slate-200/90 text-slate-800 hover:border-emerald-300 hover:shadow-xs',
                    'iconActiveClass'    => 'bg-white/20 text-white',
                    'iconInactiveClass'  => 'bg-emerald-50 text-emerald-600',
                    'labelActiveClass'   => 'text-emerald-100',
                    'countInactiveClass' => 'text-emerald-600',
                    'iconPath'           => 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z',
                ],
                [
                    'key'                => 'uang_gedung',
                    'label'              => 'Uang Gedung',
                    'sublabel'           => 'Sampai Uang Gedung',
                    'count'              => $countStageUangGedung ?? 0,
                    'activeClass'        => 'bg-violet-600 border-violet-600 text-white shadow-md shadow-violet-950/20',
                    'inactiveClass'      => 'bg-white border-slate-200/90 text-slate-800 hover:border-violet-300 hover:shadow-xs',
                    'iconActiveClass'    => 'bg-white/20 text-white',
                    'iconInactiveClass'  => 'bg-violet-50 text-violet-600',
                    'labelActiveClass'   => 'text-violet-100',
                    'countInactiveClass' => 'text-violet-600',
                    'iconPath'           => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                ],
            ];
        @endphp

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
            @foreach($stageCards as $card)
                @php $isActive = $currentTahap === $card['key']; @endphp
                <a
                    href="{{ route('pmb.pembayaran.index', array_merge(request()->except(['tahap', 'page']), ['tahap' => $card['key']])) }}"
                    class="group p-4 rounded-2xl border transition-all duration-200 flex items-center gap-3.5 {{ $isActive ? $card['activeClass'] : $card['inactiveClass'] }}"
                >
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $isActive ? $card['iconActiveClass'] : $card['iconInactiveClass'] }}">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['iconPath'] }}" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-wider {{ $isActive ? $card['labelActiveClass'] : 'text-slate-500' }}">{{ $card['label'] }}</p>
                        <div class="flex items-baseline gap-1.5">
                            <h4 class="text-xl font-black font-display tracking-tight {{ $isActive ? 'text-white' : $card['countInactiveClass'] }}">{{ number_format($card['count'], 0, ',', '.') }}</h4>
                            <span class="text-[11px] font-bold {{ $isActive ? 'text-white/80' : 'text-slate-400' }}">Org</span>
                        </div>
                        <p class="text-[10px] font-medium truncate mt-0.5 {{ $isActive ? 'text-white/80' : 'text-slate-400' }}">{{ $card['sublabel'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Ringkasan & Pengaturan Voucher Diskon DPP (Ringkas & Responsif) -->
        @php
            $vouchersList = $vouchers ?? collect();
            $voucherUsagesList = $voucherSummaryUsages ?? collect();
            $canManageVoucher = in_array($userRole, ['admin', 'hm']);
        @endphp
        <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2.5">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0b3d82] flex items-center justify-center shrink-0 border border-blue-100">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 font-display">Voucher Diskon DPP CAMABA</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                {{ $vouchersList->where('is_active', true)->count() }} Voucher Aktif
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-[#0b3d82] border border-blue-200">
                                {{ $voucherUsagesList->count() }} Pemakaian
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 truncate">Ringkasan pemakaian voucher diskon DPP oleh CAMABA &amp; pengaturan kode voucher.</p>
                    </div>
                </div>

                @if($canManageVoucher)
                    <button
                        type="button"
                        @click="voucherManageOpen = !voucherManageOpen"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition cursor-pointer"
                        :class="voucherManageOpen ? 'bg-[#0b3d82] text-white border-[#0b3d82]' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span x-text="voucherManageOpen ? 'Tutup Pengaturan Voucher' : 'Kelola &amp; Buat Voucher'">Kelola &amp; Buat Voucher</span>
                    </button>
                @endif
            </div>

            <!-- A. Card Ringkasan Voucher Digunakan CAMABA -->
            @if($voucherUsagesList->isEmpty())
                <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-200/80 flex items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-2 text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-slate-400 shrink-0"></span>
                        <span class="font-semibold">Ringkasan Voucher CAMABA:</span>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                        Belum Menggunakan Voucher
                    </span>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 max-h-52 overflow-y-auto pr-0.5">
                    @foreach($voucherUsagesList as $vu)
                        <div class="p-3 rounded-xl border border-slate-200/90 bg-slate-50/70 hover:bg-white transition space-y-1.5 text-[11px]">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="px-1.5 py-0.5 rounded font-mono font-extrabold text-[10px] bg-blue-50 text-[#0b3d82] border border-blue-200 shrink-0">{{ $vu->voucher_code }}</span>
                                    <span class="font-bold text-emerald-700 shrink-0">-{{ $vu->formatted_percentage }}%</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 {{ $vu->status === \App\Models\VoucherUsage::STATUS_USED ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                    {{ $vu->status_label }}
                                </span>
                            </div>
                            <div class="font-bold text-slate-900 truncate">{{ $vu->prospek?->name ?? '-' }}</div>
                            @php $vuVoucherName = $vu->voucher_name ?? $vu->voucher?->name; @endphp
                            @if(!empty($vuVoucherName))
                                <div class="text-[10px] font-semibold text-slate-500 truncate">{{ $vuVoucherName }}</div>
                            @endif
                            <div class="grid grid-cols-3 gap-1.5 pt-1 border-t border-slate-200/70 text-[10px]">
                                <div>
                                    <span class="text-slate-400 block">DPP Awal</span>
                                    <strong class="text-slate-700">Rp {{ number_format($vu->dpp_before_discount, 0, ',', '.') }}</strong>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Potongan</span>
                                    <strong class="text-emerald-700">-Rp {{ number_format($vu->discount_amount, 0, ',', '.') }}</strong>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">DPP Akhir</span>
                                    <strong class="text-[#0b3d82]">Rp {{ number_format($vu->dpp_after_discount, 0, ',', '.') }}</strong>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- B. Input & Pengaturan Voucher (Hanya Admin / HM) -->
            @if($canManageVoucher)
                <div x-show="voucherManageOpen" x-cloak class="pt-3 border-t border-slate-100 space-y-3">
                    <form action="{{ route('pmb.pembayaran.vouchers.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-end bg-slate-50/80 p-3 rounded-xl border border-slate-200/80">
                        @csrf
                        <div class="sm:col-span-3">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Nama Voucher <span class="text-rose-500">*</span></label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name') }}"
                                   required
                                   maxlength="150"
                                   placeholder="Contoh: Beasiswa Gelombang 1"
                                   class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3 py-2 bg-white text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Kode Voucher <span class="text-rose-500">*</span></label>
                            <input type="text"
                                   name="code"
                                   value="{{ old('code') }}"
                                   required
                                   placeholder="Contoh: UCIC20"
                                   class="w-full text-xs font-bold uppercase rounded-xl border border-slate-200 px-3 py-2 bg-white text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Persentase Diskon (%) <span class="text-rose-500">*</span></label>
                            <input type="number"
                                   name="discount_percentage"
                                   value="{{ old('discount_percentage') }}"
                                   min="1"
                                   max="100"
                                   step="0.01"
                                   required
                                   placeholder="1 - 100"
                                   class="w-full text-xs font-bold rounded-xl border border-slate-200 px-3 py-2 bg-white text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Status Voucher</label>
                            <select name="is_active" class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3 py-2 bg-white text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                                <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="w-full py-2 px-3 rounded-xl text-xs font-bold bg-[#0b3d82] hover:bg-[#082a5c] text-white shadow-2xs transition cursor-pointer">
                                Tambah Voucher
                            </button>
                        </div>
                    </form>

                    @if($vouchersList->isNotEmpty())
                        <div class="overflow-x-auto rounded-xl border border-slate-200/80">
                            <table class="w-full text-left text-xs whitespace-nowrap">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200/80">
                                        <th class="py-2.5 px-3">Nama Voucher</th>
                                        <th class="py-2.5 px-3">Kode Voucher</th>
                                        <th class="py-2.5 px-3">Diskon</th>
                                        <th class="py-2.5 px-3">Status</th>
                                        <th class="py-2.5 px-3">Pengguna CAMABA</th>
                                        <th class="py-2.5 px-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach($vouchersList as $v)
                                        @php
                                            $vPayload = [
                                                'id'                   => $v->id,
                                                'code'                 => $v->code,
                                                'name'                 => $v->name ?? '',
                                                'discount_percentage'  => (float) $v->discount_percentage,
                                                'percentage_formatted' => $v->formatted_percentage,
                                                'is_active'            => (bool) $v->is_active,
                                                'usages'               => $v->usages->map(fn ($u) => [
                                                    'camaba_name'          => $u->prospek?->name ?? '-',
                                                    'no_registrasi'        => $u->prospek?->no_registrasi ?? '-',
                                                    'voucher_code'         => $u->voucher_code,
                                                    'voucher_name'         => $u->voucher_name ?? $v->name ?? '',
                                                    'percentage_formatted' => $u->formatted_percentage,
                                                    'dpp_before'           => 'Rp ' . number_format($u->dpp_before_discount, 0, ',', '.'),
                                                    'discount_amount'      => 'Rp ' . number_format($u->discount_amount, 0, ',', '.'),
                                                    'dpp_after'            => 'Rp ' . number_format($u->dpp_after_discount, 0, ',', '.'),
                                                    'status_label'         => $u->status_label,
                                                    'is_used'              => $u->status === \App\Models\VoucherUsage::STATUS_USED,
                                                    'time'                 => ($u->used_at ?? $u->created_at)?->format('d M Y, H:i'),
                                                ])->values()->all(),
                                            ];
                                        @endphp
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3 font-bold text-slate-800">{{ $v->name ?: '-' }}</td>
                                            <td class="py-2.5 px-3 font-mono font-extrabold text-[#0b3d82]">{{ $v->code }}</td>
                                            <td class="py-2.5 px-3 font-bold text-emerald-700">{{ $v->formatted_percentage }}%</td>
                                            <td class="py-2.5 px-3">
                                                @if($v->is_active)
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">Nonaktif</span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <button type="button"
                                                        @click="openVoucherUsers({{ json_encode($vPayload) }})"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-50 hover:bg-blue-100 text-[#0b3d82] border border-blue-200 cursor-pointer">
                                                    <span>Lihat CAMABA ({{ $v->usages->count() }})</span>
                                                </button>
                                            </td>
                                            <td class="py-2.5 px-3 text-right">
                                                <div class="inline-flex items-center gap-1.5">
                                                    <button type="button"
                                                            @click="openEditVoucher({{ json_encode($vPayload) }})"
                                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 cursor-pointer">
                                                        Ubah
                                                    </button>
                                                    <form action="{{ route('pmb.pembayaran.vouchers.toggle', $v->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit"
                                                                class="px-2.5 py-1 rounded-lg text-[11px] font-bold border cursor-pointer {{ $v->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200' }}">
                                                            {{ $v->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-[11px] text-slate-500 text-center py-2">Belum ada kode voucher yang dibuat.</p>
                    @endif
                </div>
            @endif
        </div>

        <!-- Table Container Card (Matching Image 4) -->
        <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden transition-all duration-300">
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs whitespace-nowrap min-w-[900px]">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider text-[11px] border-b border-slate-100">
                            <th class="py-4 px-4 text-center w-12">No</th>
                            <th class="py-4 px-4">Calon Mahasiswa Baru</th>
                            <th class="py-4 px-4">Total Kewajiban</th>
                            <th class="py-4 px-4">Terbayar</th>
                            <th class="py-4 px-4">Sisa Tagihan</th>
                            <th class="py-4 px-4">Status</th>
                            <th class="py-4 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($rekapList as $index => $p)
                            @php
                                $r = $p->rekap ?? \App\Http\Controllers\Pmb\PmbPembayaranController::calculateRekap($p);
                                $latestTrx = $p->transaksis->first();
                                $orderIdText = $latestTrx?->order_id ?: ('TRX-' . str_pad($p->id, 5, '0', STR_PAD_LEFT));
                                $trxCount = $p->transaksis->count();
                                $regNo = $p->no_registrasi ?: ('PMB-2026-' . str_pad($p->id, 4, '0', STR_PAD_LEFT));
                                $formattedDate = $latestTrx?->created_at ? $latestTrx->created_at->format('d M Y • H:i') . ' WIB' : ($p->created_at ? $p->created_at->format('d M Y • H:i') . ' WIB' : '-');

                                // Siapkan payload data untuk modal riwayat & detail
                                $camabaPayload = [
                                    'id'            => $p->id,
                                    'name'          => $p->name,
                                    'email'         => $p->email ?: '-',
                                    'whatsapp'      => $p->whatsapp ?: '-',
                                    'no_registrasi' => $regNo,
                                    'prodi'         => $p->prodi?->nama_prodi ?: '-',
                                    'rekap'         => $r,
                                    'transaksis'    => $p->transaksis->map(function ($tx) {
                                        $sRaw = strtolower($tx->payment_status ?? 'pending');
                                        $v = in_array($sRaw, ['verified', 'paid', 'success', 'settlement', 'capture']);
                                        $rj = in_array($sRaw, ['rejected', 'failed', 'batal', 'expire', 'deny', 'cancel']);
                                        $pd = !$v && !$rj;

                                        return [
                                            'id'               => $tx->id,
                                            'order_id'         => $tx->order_id ?: ('TRX-' . $tx->id),
                                            'nominal'          => number_format($tx->nominal, 0, ',', '.'),
                                            'nominal_raw'      => (float) $tx->nominal,
                                            'detail'           => $tx->detail_pembayaran ?: ($tx->jenis ?? 'Biaya PMB'),
                                            'metode'           => $tx->metode_detail,
                                            'metode_raw'       => $tx->metode_pembayaran,
                                            'status'           => $v ? 'Berhasil' : ($rj ? 'Gagal' : 'Pending'),
                                            'status_raw'       => $sRaw,
                                            'is_verified'      => $v,
                                            'is_pending'       => $pd,
                                            'is_rejected'      => $rj,
                                            'tanggal'          => $tx->tanggal ? \Carbon\Carbon::parse($tx->tanggal)->format('d M Y, H:i') : ($tx->created_at ? $tx->created_at->format('d M Y, H:i') : '-'),
                                            'verified_at'      => $tx->verified_at ? \Carbon\Carbon::parse($tx->verified_at)->format('d M Y, H:i') : null,
                                            'rejection_reason' => $tx->rejection_reason,
                                        ];
                                    })->values(),
                                ];
                            @endphp

                            <!-- Main Row -->
                            <tr class="hover:bg-slate-50/70 transition-colors {{ $index % 2 === 0 ? 'bg-white' : 'bg-slate-50/30' }}">
                                <!-- 1. No -->
                                <td class="py-4 px-4 text-center font-bold text-slate-500">
                                    {{ $rekapList->firstItem() + $index }}
                                </td>

                                <!-- 2. Calon Mahasiswa Baru (No Reg, Nama & Tanggal) -->
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#0b3d82] to-blue-700 text-white font-black flex items-center justify-center shrink-0 text-xs shadow-2xs">
                                            {{ strtoupper(substr($p->name, 0, 2)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('pmb.pendaftar.show', $p->id) }}" class="font-extrabold text-slate-900 hover:text-blue-600 transition block truncate text-xs sm:text-sm">
                                                {{ $regNo }}
                                            </a>
                                            <div class="flex items-center gap-1.5 text-slate-500 text-[11px] mt-0.5">
                                                <span class="font-bold text-slate-700">{{ $p->name }}</span>
                                                <span class="text-slate-300">•</span>
                                                <span class="text-slate-400">{{ $formattedDate }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- 3. Total Kewajiban -->
                                <td class="py-4 px-4">
                                    <span class="font-bold text-slate-900 font-display text-xs sm:text-sm">
                                        Rp {{ number_format($r['total_kewajiban'], 0, ',', '.') }}
                                    </span>
                                </td>

                                <!-- 4. Terbayar -->
                                <td class="py-4 px-4">
                                    <span class="font-extrabold text-emerald-600 font-display text-xs sm:text-sm">
                                        Rp {{ number_format($r['total_terbayar'], 0, ',', '.') }}
                                    </span>
                                </td>

                                <!-- 5. Sisa Tagihan -->
                                <td class="py-4 px-4">
                                    @if($r['total_sisa'] <= 0)
                                        <span class="font-bold text-slate-400 text-xs">Rp 0</span>
                                    @else
                                        <span class="font-extrabold text-rose-600 font-display text-xs sm:text-sm">
                                            Rp {{ number_format($r['total_sisa'], 0, ',', '.') }}
                                        </span>
                                    @endif
                                </td>

                                <!-- 6. Status -->
                                <td class="py-4 px-4">
                                    <div class="flex flex-col gap-1 items-start">
                                        @if($r['is_lunas'])
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Lunas</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 shadow-2xs">
                                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                                <span>Belum Lunas</span>
                                            </span>
                                        @endif

                                        @if($r['pending_count'] > 0)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                                <span>{{ $r['pending_count'] }} Menunggu Verifikasi</span>
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- 7. Aksi (Expandable Detail Toggle + Hapus Soft Delete Khusus Admin) -->
                                <td class="py-4 px-4 text-center">
                                    <div class="inline-flex items-center justify-center gap-1.5">
                                        <button 
                                            type="button" 
                                            @click="expandedRow = (expandedRow === {{ $p->id }} ? null : {{ $p->id }})"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 hover:text-[#0b3d82] hover:border-blue-300 shadow-2xs transition-all cursor-pointer"
                                        >
                                            <span x-text="expandedRow === {{ $p->id }} ? 'Tutup Detail' : 'Lihat Detail'">Lihat Detail</span>
                                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="expandedRow === {{ $p->id }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        @if($userRole === 'admin')
                                            @php
                                                $deletePayload = [
                                                    'name'     => $p->name ?? '-',
                                                    'reg_no'   => $regNo,
                                                    'email'    => $p->email ?? '-',
                                                    'prodi'    => $p->prodi?->nama ?? '-',
                                                    'terbayar' => 'Rp ' . number_format($r['total_terbayar'], 0, ',', '.'),
                                                ];
                                            @endphp
                                            <form
                                                action="{{ route('pmb.pembayaran.destroy', array_merge(['id' => $p->id], request()->only(['search', 'periode', 'metode', 'status', 'tahap', 'per_page']))) }}"
                                                method="POST"
                                                class="inline"
                                                onsubmit="return window.confirmMoveToTrash(event, {{ json_encode($deletePayload) }})"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    title="Hapus Data Pembayaran ke Trash"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold border border-rose-200 bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white hover:border-rose-600 shadow-2xs transition-all cursor-pointer"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    <span>Hapus</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <!-- Expandable Payment Detail Row (Matching Image 4) -->
                            <tr x-show="expandedRow === {{ $p->id }}" x-cloak class="bg-slate-50/70 border-b border-slate-100 transition-all">
                                <td colspan="7" class="p-3.5 sm:p-5">
                                    <div class="bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-xs space-y-4">
                                        
                                        <!-- Header: Rincian Pembayaran -->
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-lg bg-blue-50 text-[#0b3d82] flex items-center justify-center">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                                    </svg>
                                                </div>
                                                <span class="text-xs font-black text-slate-800 uppercase tracking-wider">Rincian Pembayaran</span>
                                            </div>
                                        </div>

                                        <!-- Grid Komponen Biaya (Perlengkapan, Pendaftaran, DPP, DP, Pelunasan, Uang Gedung) + Riwayat Pembayaran (Matching Image 4) -->
                                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
                                            
                                            <!-- 1. Perlengkapan (Matching Image 4) -->
                                            <div class="p-3 rounded-xl bg-slate-50/90 border border-slate-100 hover:border-slate-200 transition flex flex-col justify-between">
                                                <div>
                                                    <div class="flex items-center gap-1.5 text-slate-500 mb-1">
                                                        <svg class="w-3.5 h-3.5 text-[#0b3d82]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                                        </svg>
                                                        <span class="text-[11px] font-bold text-slate-600 truncate">Perlengkapan</span>
                                                    </div>
                                                    <div class="flex items-baseline justify-between gap-1 mt-1">
                                                        <span class="text-xs font-extrabold text-slate-900">
                                                            Rp {{ number_format($r['rincian']['perlengkapan']['terbayar'] ?? 0, 0, ',', '.') }}
                                                        </span>
                                                        @if(!empty($r['rincian']['perlengkapan']['is_lunas']))
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Lunas</span>
                                                        @else
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">Belum</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <!-- Metode Pembayaran Kategori -->
                                                <div class="mt-2.5 pt-2 border-t border-slate-200/70">
                                                    <div class="text-[9.5px] font-medium text-slate-400 mb-0.5">Metode Bayar:</div>
                                                    @if(!empty($r['rincian']['perlengkapan']['metode_badge']))
                                                        @php $mb = $r['rincian']['perlengkapan']['metode_badge']; @endphp
                                                        <span class="inline-flex items-center gap-1 font-bold {{ $mb['badge_class'] }} px-1.5 py-0.5 rounded text-[9.5px] shadow-2xs" title="{{ $r['rincian']['perlengkapan']['metode'] }}">
                                                            @if($mb['icon'] === 'qris')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                                                            @elseif($mb['icon'] === 'va')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                                            @elseif($mb['icon'] === 'ewallet')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                                            @elseif($mb['icon'] === 'manual')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
                                                            @elseif($mb['icon'] === 'cash')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                                            @endif
                                                            <span class="truncate max-w-[75px]">{{ $mb['name'] }}</span>
                                                        </span>
                                                    @elseif(!empty($r['rincian']['perlengkapan']['is_lunas']) && ($r['rincian']['perlengkapan']['kewajiban'] == 0))
                                                        <span class="text-[9.5px] font-semibold text-slate-400">Bebas Biaya</span>
                                                    @else
                                                        <span class="text-[9.5px] italic text-slate-400">Belum Ada</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- 2. Pendaftaran (Matching Image 4) -->
                                            <div class="p-3 rounded-xl bg-slate-50/90 border border-slate-100 hover:border-slate-200 transition flex flex-col justify-between">
                                                <div>
                                                    <div class="flex items-center gap-1.5 text-slate-500 mb-1">
                                                        <svg class="w-3.5 h-3.5 text-[#0b3d82]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        <span class="text-[11px] font-bold text-slate-600 truncate">Pendaftaran</span>
                                                    </div>
                                                    <div class="flex items-baseline justify-between gap-1 mt-1">
                                                        <span class="text-xs font-extrabold text-slate-900">
                                                            Rp {{ number_format($r['rincian']['pendaftaran']['terbayar'] ?? 0, 0, ',', '.') }}
                                                        </span>
                                                        @if(!empty($r['rincian']['pendaftaran']['is_lunas']))
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Lunas</span>
                                                        @else
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">Belum</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <!-- Metode Pembayaran Kategori -->
                                                <div class="mt-2.5 pt-2 border-t border-slate-200/70">
                                                    <div class="text-[9.5px] font-medium text-slate-400 mb-0.5">Metode Bayar:</div>
                                                    @if(!empty($r['rincian']['pendaftaran']['metode_badge']))
                                                        @php $mb = $r['rincian']['pendaftaran']['metode_badge']; @endphp
                                                        <span class="inline-flex items-center gap-1 font-bold {{ $mb['badge_class'] }} px-1.5 py-0.5 rounded text-[9.5px] shadow-2xs" title="{{ $r['rincian']['pendaftaran']['metode'] }}">
                                                            @if($mb['icon'] === 'qris')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                                                            @elseif($mb['icon'] === 'va')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                                            @elseif($mb['icon'] === 'ewallet')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                                            @elseif($mb['icon'] === 'manual')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
                                                            @elseif($mb['icon'] === 'cash')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                                            @endif
                                                            <span class="truncate max-w-[75px]">{{ $mb['name'] }}</span>
                                                        </span>
                                                    @elseif(!empty($r['rincian']['pendaftaran']['is_lunas']) && ($r['rincian']['pendaftaran']['kewajiban'] == 0))
                                                        <span class="text-[9.5px] font-semibold text-slate-400">Bebas Biaya</span>
                                                    @else
                                                        <span class="text-[9.5px] italic text-slate-400">Belum Ada</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- 3. DPP (Matching Image 4) -->
                                            <div class="p-3 rounded-xl bg-slate-50/90 border border-slate-100 hover:border-slate-200 transition flex flex-col justify-between">
                                                <div>
                                                    <div class="flex items-center gap-1.5 text-slate-500 mb-1">
                                                        <svg class="w-3.5 h-3.5 text-[#0b3d82]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                                        </svg>
                                                        <span class="text-[11px] font-bold text-slate-600 truncate">DPP</span>
                                                    </div>
                                                    <div class="flex items-baseline justify-between gap-1 mt-1">
                                                        <span class="text-xs font-extrabold text-slate-900">
                                                            Rp {{ number_format($r['rincian']['dpp']['terbayar'] ?? 0, 0, ',', '.') }}
                                                        </span>
                                                        @if(!empty($r['rincian']['dpp']['is_lunas']))
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Lunas</span>
                                                        @else
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">Belum</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <!-- Metode Pembayaran Kategori -->
                                                <div class="mt-2.5 pt-2 border-t border-slate-200/70">
                                                    <div class="text-[9.5px] font-medium text-slate-400 mb-0.5">Metode Bayar:</div>
                                                    @if(!empty($r['rincian']['dpp']['metode_badge']))
                                                        @php $mb = $r['rincian']['dpp']['metode_badge']; @endphp
                                                        <span class="inline-flex items-center gap-1 font-bold {{ $mb['badge_class'] }} px-1.5 py-0.5 rounded text-[9.5px] shadow-2xs" title="{{ $r['rincian']['dpp']['metode'] }}">
                                                            @if($mb['icon'] === 'qris')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                                                            @elseif($mb['icon'] === 'va')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                                            @elseif($mb['icon'] === 'ewallet')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                                            @elseif($mb['icon'] === 'manual')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
                                                            @elseif($mb['icon'] === 'cash')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                                            @endif
                                                            <span class="truncate max-w-[75px]">{{ $mb['name'] }}</span>
                                                        </span>
                                                    @elseif(!empty($r['rincian']['dpp']['is_lunas']) && ($r['rincian']['dpp']['kewajiban'] == 0))
                                                        <span class="text-[9.5px] font-semibold text-slate-400">Bebas Biaya</span>
                                                    @else
                                                        <span class="text-[9.5px] italic text-slate-400">Belum Ada</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- 4. Down Payment (Matching Image 4) -->
                                            <div class="p-3 rounded-xl bg-slate-50/90 border border-slate-100 hover:border-slate-200 transition flex flex-col justify-between">
                                                <div>
                                                    <div class="flex items-center gap-1.5 text-slate-500 mb-1">
                                                        <svg class="w-3.5 h-3.5 text-[#0b3d82]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                                        </svg>
                                                        <span class="text-[11px] font-bold text-slate-600 truncate">Down Payment</span>
                                                    </div>
                                                    <div class="flex items-baseline justify-between gap-1 mt-1">
                                                        <span class="text-xs font-extrabold text-slate-900">
                                                            Rp {{ number_format($r['rincian']['dp']['terbayar'] ?? 0, 0, ',', '.') }}
                                                        </span>
                                                        @if(!empty($r['rincian']['dp']['is_lunas']))
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Lunas</span>
                                                        @else
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">Belum</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <!-- Metode Pembayaran Kategori -->
                                                <div class="mt-2.5 pt-2 border-t border-slate-200/70">
                                                    <div class="text-[9.5px] font-medium text-slate-400 mb-0.5">Metode Bayar:</div>
                                                    @if(!empty($r['rincian']['dp']['metode_badge']))
                                                        @php $mb = $r['rincian']['dp']['metode_badge']; @endphp
                                                        <span class="inline-flex items-center gap-1 font-bold {{ $mb['badge_class'] }} px-1.5 py-0.5 rounded text-[9.5px] shadow-2xs" title="{{ $r['rincian']['dp']['metode'] }}">
                                                            @if($mb['icon'] === 'qris')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                                                            @elseif($mb['icon'] === 'va')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                                            @elseif($mb['icon'] === 'ewallet')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                                            @elseif($mb['icon'] === 'manual')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
                                                            @elseif($mb['icon'] === 'cash')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                                            @endif
                                                            <span class="truncate max-w-[75px]">{{ $mb['name'] }}</span>
                                                        </span>
                                                    @elseif(!empty($r['rincian']['dp']['is_lunas']) && ($r['rincian']['dp']['kewajiban'] == 0))
                                                        <span class="text-[9.5px] font-semibold text-slate-400">Bebas Biaya</span>
                                                    @else
                                                        <span class="text-[9.5px] italic text-slate-400">Belum Ada</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- 5. Pelunasan (Matching Image 4) -->
                                            <div class="p-3 rounded-xl bg-slate-50/90 border border-slate-100 hover:border-slate-200 transition flex flex-col justify-between">
                                                <div>
                                                    <div class="flex items-center gap-1.5 text-slate-500 mb-1">
                                                        <svg class="w-3.5 h-3.5 text-[#0b3d82]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span class="text-[11px] font-bold text-slate-600 truncate">Pelunasan</span>
                                                    </div>
                                                    <div class="flex items-baseline justify-between gap-1 mt-1">
                                                        <span class="text-xs font-extrabold text-slate-900">
                                                            Rp {{ number_format($r['rincian']['pelunasan']['terbayar'] ?? 0, 0, ',', '.') }}
                                                        </span>
                                                        @if(!empty($r['rincian']['pelunasan']['is_lunas']))
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Lunas</span>
                                                        @else
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">Belum</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <!-- Metode Pembayaran Kategori -->
                                                <div class="mt-2.5 pt-2 border-t border-slate-200/70">
                                                    <div class="text-[9.5px] font-medium text-slate-400 mb-0.5">Metode Bayar:</div>
                                                    @if(!empty($r['rincian']['pelunasan']['metode_badge']))
                                                        @php $mb = $r['rincian']['pelunasan']['metode_badge']; @endphp
                                                        <span class="inline-flex items-center gap-1 font-bold {{ $mb['badge_class'] }} px-1.5 py-0.5 rounded text-[9.5px] shadow-2xs" title="{{ $r['rincian']['pelunasan']['metode'] }}">
                                                            @if($mb['icon'] === 'qris')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                                                            @elseif($mb['icon'] === 'va')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                                            @elseif($mb['icon'] === 'ewallet')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                                            @elseif($mb['icon'] === 'manual')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
                                                            @elseif($mb['icon'] === 'cash')
                                                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                                            @endif
                                                            <span class="truncate max-w-[75px]">{{ $mb['name'] }}</span>
                                                        </span>
                                                    @elseif(!empty($r['rincian']['pelunasan']['is_lunas']) && ($r['rincian']['pelunasan']['kewajiban'] == 0))
                                                        <span class="text-[9.5px] font-semibold text-slate-400">Bebas Biaya</span>
                                                    @else
                                                        <span class="text-[9.5px] italic text-slate-400">Belum Ada</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- 6. Riwayat Pembayaran Card (Matching Image 4) -->
                                            <div class="p-3 rounded-xl bg-blue-50/50 border border-blue-100 flex flex-col justify-between">
                                                <div class="flex items-center gap-1.5 text-[#0b3d82] mb-1">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <span class="text-[11px] font-bold text-[#0b3d82] truncate">Riwayat Pembayaran</span>
                                                </div>
                                                <button 
                                                    type="button" 
                                                    @click="openDetail({{ json_encode($camabaPayload) }})"
                                                    class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-white hover:bg-blue-50 text-[#0b3d82] border border-blue-200 shadow-2xs hover:shadow-xs active:scale-95 transition-all cursor-pointer mt-1"
                                                >
                                                    <span>Lihat Riwayat</span>
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Card Ringkasan Voucher CAMABA (Per Baris) -->
                                        @php $rowVoucher = $r['dpp_voucher'] ?? ($r['rincian']['dpp']['voucher'] ?? null); @endphp
                                        <div class="p-3 rounded-xl border text-[11px] {{ $rowVoucher ? 'bg-emerald-50/40 border-emerald-200' : 'bg-slate-50/70 border-slate-200/80' }}">
                                            @if($rowVoucher)
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="font-bold text-slate-700">Voucher DPP ({{ $p->name }}):</span>
                                                        <span class="px-2 py-0.5 rounded font-mono font-extrabold bg-blue-50 text-[#0b3d82] border border-blue-200">{{ $rowVoucher['voucher_code'] }}</span>
                                                        <span class="font-bold text-emerald-700">Diskon {{ $rowVoucher['percentage_formatted'] }}% (-Rp {{ number_format($rowVoucher['discount_amount'], 0, ',', '.') }})</span>
                                                        <span class="text-slate-500">DPP Awal: <strong class="text-slate-700">Rp {{ number_format($rowVoucher['dpp_before_discount'], 0, ',', '.') }}</strong> &rarr; Setelah Diskon: <strong class="text-[#0b3d82]">Rp {{ number_format($rowVoucher['dpp_after_discount'], 0, ',', '.') }}</strong></span>
                                                    </div>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $rowVoucher['status'] === 'used' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                        {{ $rowVoucher['status_label'] }}
                                                    </span>
                                                </div>
                                            @else
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="font-semibold text-slate-600">Voucher DPP ({{ $p->name }}):</span>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                                        Belum Menggunakan Voucher
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    <p class="font-bold text-slate-700 text-sm">Tidak ada data rekap pembayaran PMB</p>
                                    <p class="text-xs text-slate-400 mt-1">Gunakan kata kunci pencarian atau filter status yang berbeda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar (Matching Image 4) -->
            <div class="p-4 sm:px-6 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white">
                <div class="text-xs text-slate-500">
                    @if($rekapList->total() > 0)
                        Menampilkan <span class="font-bold text-slate-800">{{ $rekapList->firstItem() }} - {{ $rekapList->lastItem() }}</span> dari <span class="font-bold text-slate-800">{{ number_format($rekapList->total(), 0, ',', '.') }}</span> data
                    @else
                        Menampilkan 0 data
                    @endif
                </div>

                @if($transaksis->hasPages())
                    <div>
                        {{ $transaksis->links() }}
                    </div>
                @endif
            </div>

        </div>
        <!-- ============================================================== -->
        <!-- MODAL: Detail Rekap & Riwayat Transaksi Individual Calon Mahasiswa -->
        <!-- ============================================================== -->
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
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-6">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="detailModalOpen = false"></div>

                <div 
                    x-show="detailModalOpen" 
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all max-w-5xl w-full border border-slate-100 my-8"
                >
                    <!-- Header Modal: Profil Camaba & Status Utama -->
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-slate-50 via-white to-blue-50/30">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-[#0b3d82] to-blue-600 text-white font-black text-lg flex items-center justify-center shadow-md shadow-blue-900/20 shrink-0 ring-4 ring-blue-50">
                                <span x-text="selectedCamaba.name ? selectedCamaba.name.substring(0, 2).toUpperCase() : 'PMB'"></span>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 font-display tracking-tight" x-text="selectedCamaba.name"></h3>
                                    <template x-if="selectedCamaba.rekap?.is_lunas">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            LUNAS LENGKAP
                                        </span>
                                    </template>
                                    <template x-if="!selectedCamaba.rekap?.is_lunas">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 shadow-2xs">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            BELUM LUNAS
                                        </span>
                                    </template>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-slate-500 mt-1.5 flex-wrap">
                                    <span class="inline-flex items-center gap-1.5 text-slate-600 font-medium">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        <span x-text="selectedCamaba.email"></span>
                                    </span>
                                    <span class="text-slate-300">•</span>
                                    <span class="inline-flex items-center gap-1.5 text-slate-600 font-medium">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <span x-text="selectedCamaba.whatsapp"></span>
                                    </span>
                                    <template x-if="selectedCamaba.no_registrasi">
                                        <span class="flex items-center gap-2">
                                            <span class="text-slate-300">•</span>
                                            <span class="px-2 py-0.5 rounded-md text-[11px] font-mono font-bold bg-slate-100 text-slate-600 border border-slate-200" x-text="selectedCamaba.no_registrasi"></span>
                                        </span>
                                    </template>
                                    <template x-if="selectedCamaba.prodi && selectedCamaba.prodi !== '-'">
                                        <span class="flex items-center gap-2">
                                            <span class="text-slate-300">•</span>
                                            <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-blue-50 text-[#0b3d82] border border-blue-200" x-text="selectedCamaba.prodi"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="detailModalOpen = false" class="text-slate-400 hover:text-slate-700 p-2 rounded-2xl hover:bg-slate-100 transition-all duration-200 cursor-pointer" title="Tutup Modal">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 sm:p-7 space-y-6 max-h-[75vh] overflow-y-auto">

                        <!-- Section: Rekap Ringkasan Eksekutif Bar -->
                        <div class="relative overflow-hidden bg-gradient-to-r from-slate-950 via-slate-900 to-[#0b3d82] text-white p-5 sm:p-6 rounded-2xl sm:rounded-3xl shadow-xl border border-slate-800/80">
                            <div class="relative z-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 items-center">
                                <!-- 1. Total Kewajiban -->
                                <div class="border-b sm:border-b-0 sm:border-r border-slate-800/80 pb-3 sm:pb-0 sm:pr-4">
                                    <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Seluruh Kewajiban</span>
                                    <span class="text-xl sm:text-2xl font-black text-white font-display tracking-tight block" x-text="'Rp ' + (selectedCamaba.rekap?.total_kewajiban || 0).toLocaleString('id-ID')"></span>
                                    <span class="text-[10px] text-slate-400 block mt-0.5">Biaya Pendaftaran + Kuliah</span>
                                </div>

                                <!-- 2. Total Berhasil Dibayar -->
                                <div class="border-b sm:border-b-0 lg:border-r border-slate-800/80 pb-3 sm:pb-0 sm:pr-4">
                                    <span class="text-[10px] sm:text-[11px] font-bold text-emerald-400 uppercase tracking-wider block mb-1">Total Berhasil Dibayar</span>
                                    <span class="text-xl sm:text-2xl font-black text-emerald-400 font-display tracking-tight block" x-text="'Rp ' + (selectedCamaba.rekap?.total_terbayar || 0).toLocaleString('id-ID')"></span>
                                    <span class="text-[10px] text-slate-400 block mt-0.5">Pembayaran Terverifikasi Sah</span>
                                </div>

                                <!-- 3. Sisa Tagihan -->
                                <div class="border-b sm:border-b-0 sm:border-r border-slate-800/80 pb-3 sm:pb-0 sm:pr-4">
                                    <span class="text-[10px] sm:text-[11px] font-bold text-rose-300 uppercase tracking-wider block mb-1">Sisa Tagihan</span>
                                    <span class="text-xl sm:text-2xl font-black font-display tracking-tight block" :class="selectedCamaba.rekap?.total_sisa > 0 ? 'text-rose-400' : 'text-slate-400'" x-text="'Rp ' + (selectedCamaba.rekap?.total_sisa || 0).toLocaleString('id-ID')"></span>
                                    <span class="text-[10px] text-slate-400 block mt-0.5" x-text="selectedCamaba.rekap?.total_sisa > 0 ? 'Wajib Dilunasi' : 'Seluruh Kewajiban Lunas'"></span>
                                </div>

                                <!-- 4. Status Keseluruhan -->
                                <div class="flex flex-col justify-center">
                                    <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Status Keseluruhan</span>
                                    <template x-if="selectedCamaba.rekap?.is_lunas">
                                        <div class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 shadow-xs font-black text-xs tracking-wider uppercase">
                                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                            <span>LUNAS LENGKAP</span>
                                        </div>
                                    </template>
                                    <template x-if="!selectedCamaba.rekap?.is_lunas">
                                        <div class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500/20 text-amber-300 border border-amber-500/40 shadow-xs font-black text-xs tracking-wider uppercase">
                                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                                            <span>BELUM LUNAS</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>


                        <!-- Section: Rincian Pembayaran Per Komponen Biaya -->
                        <div class="space-y-5">
                            
                            <!-- Sub-Group 1: 4 Komponen Biaya 1x Bayar (Pendaftaran, Perlengkapan, DPP, Uang Gedung) -->
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#0b3d82]"></span>
                                        <h4 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">1. Komponen Biaya 1x Bayar (Bisa Rp 0)</h4>
                                    </div>
                                    <span class="text-[11px] font-bold text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200">
                                        Total: Rp <span x-text="(((selectedCamaba.rekap?.rincian?.pendaftaran?.kewajiban || 0) + (selectedCamaba.rekap?.rincian?.perlengkapan?.kewajiban || 0) + (selectedCamaba.rekap?.rincian?.dpp?.kewajiban || 0) + (selectedCamaba.rekap?.rincian?.uang_gedung?.kewajiban || 0))).toLocaleString('id-ID')"></span>
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                                    <!-- 1. Biaya Pendaftaran -->
                                    <div 
                                        class="p-4 rounded-2xl border transition-all duration-200 flex flex-col justify-between"
                                        :class="selectedCamaba.rekap?.rincian?.pendaftaran?.is_lunas ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200 bg-slate-50/40'"
                                    >
                                        <div>
                                            <div class="flex items-center justify-between gap-2 mb-2">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0" :class="selectedCamaba.rekap?.rincian?.pendaftaran?.is_lunas ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200/80 text-slate-600'">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    </div>
                                                    <span class="text-xs font-bold text-slate-800 truncate">Pendaftaran</span>
                                                </div>
                                                <span 
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                                                    :class="selectedCamaba.rekap?.rincian?.pendaftaran?.is_lunas ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-200/60 text-slate-600 border border-slate-200'"
                                                    x-text="selectedCamaba.rekap?.rincian?.pendaftaran?.is_lunas ? '✓ Lunas' : 'Belum Lunas'"
                                                ></span>
                                            </div>

                                            <div class="mt-2">
                                                <span class="text-[11px] font-medium text-slate-400 block">Sudah Dibayar:</span>
                                                <span 
                                                    class="text-base sm:text-lg font-black font-display tracking-tight"
                                                    :class="selectedCamaba.rekap?.rincian?.pendaftaran?.is_lunas ? 'text-emerald-700' : 'text-slate-900'"
                                                    x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.pendaftaran?.terbayar || 0).toLocaleString('id-ID')"
                                                ></span>
                                            </div>
                                        </div>

                                        <div class="pt-2.5 mt-2.5 border-t border-slate-200/70 flex items-center justify-between text-[11px]">
                                            <span class="text-slate-500">Kewajiban: <strong class="text-slate-700 font-semibold" x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.pendaftaran?.kewajiban || 0).toLocaleString('id-ID')"></strong></span>
                                            <span class="text-slate-500">Sisa: <strong :class="selectedCamaba.rekap?.rincian?.pendaftaran?.sisa > 0 ? 'text-rose-600 font-bold' : 'text-slate-400'" x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.pendaftaran?.sisa || 0).toLocaleString('id-ID')"></strong></span>
                                        </div>
                                        <div class="mt-1 pt-1 border-t border-slate-100 flex items-center justify-between text-[10.5px]">
                                            <span class="text-slate-400">Metode Bayar:</span>
                                            <span class="font-bold text-[#0b3d82]" x-text="selectedCamaba.rekap?.rincian?.pendaftaran?.metode || (selectedCamaba.rekap?.rincian?.pendaftaran?.is_lunas && selectedCamaba.rekap?.rincian?.pendaftaran?.kewajiban == 0 ? 'Bebas Biaya' : '-')"></span>
                                        </div>
                                    </div>

                                    <!-- 2. Perlengkapan Kuliah -->
                                    <div 
                                        class="p-4 rounded-2xl border transition-all duration-200 flex flex-col justify-between"
                                        :class="selectedCamaba.rekap?.rincian?.perlengkapan?.is_lunas ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200 bg-slate-50/40'"
                                    >
                                        <div>
                                            <div class="flex items-center justify-between gap-2 mb-2">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0" :class="selectedCamaba.rekap?.rincian?.perlengkapan?.is_lunas ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200/80 text-slate-600'">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                    </div>
                                                    <span class="text-xs font-bold text-slate-800 truncate">Perlengkapan</span>
                                                </div>
                                                <span 
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                                                    :class="selectedCamaba.rekap?.rincian?.perlengkapan?.is_lunas ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-200/60 text-slate-600 border border-slate-200'"
                                                    x-text="selectedCamaba.rekap?.rincian?.perlengkapan?.is_lunas ? '✓ Lunas' : 'Belum Lunas'"
                                                ></span>
                                            </div>

                                            <div class="mt-2">
                                                <span class="text-[11px] font-medium text-slate-400 block">Sudah Dibayar:</span>
                                                <span 
                                                    class="text-base sm:text-lg font-black font-display tracking-tight"
                                                    :class="selectedCamaba.rekap?.rincian?.perlengkapan?.is_lunas ? 'text-emerald-700' : 'text-slate-900'"
                                                    x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.perlengkapan?.terbayar || 0).toLocaleString('id-ID')"
                                                ></span>
                                            </div>
                                        </div>

                                        <div class="pt-2.5 mt-2.5 border-t border-slate-200/70 flex items-center justify-between text-[11px]">
                                            <span class="text-slate-500">Kewajiban: <strong class="text-slate-700 font-semibold" x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.perlengkapan?.kewajiban || 0).toLocaleString('id-ID')"></strong></span>
                                            <span class="text-slate-500">Sisa: <strong :class="selectedCamaba.rekap?.rincian?.perlengkapan?.sisa > 0 ? 'text-rose-600 font-bold' : 'text-slate-400'" x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.perlengkapan?.sisa || 0).toLocaleString('id-ID')"></strong></span>
                                        </div>
                                        <div class="mt-1 pt-1 border-t border-slate-100 flex items-center justify-between text-[10.5px]">
                                            <span class="text-slate-400">Metode Bayar:</span>
                                            <span class="font-bold text-[#0b3d82]" x-text="selectedCamaba.rekap?.rincian?.perlengkapan?.metode || (selectedCamaba.rekap?.rincian?.perlengkapan?.is_lunas && selectedCamaba.rekap?.rincian?.perlengkapan?.kewajiban == 0 ? 'Bebas Biaya' : '-')"></span>
                                        </div>
                                    </div>

                                    <!-- 3. DPP -->
                                    <div 
                                        class="p-4 rounded-2xl border transition-all duration-200 flex flex-col justify-between"
                                        :class="selectedCamaba.rekap?.rincian?.dpp?.is_lunas ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200 bg-slate-50/40'"
                                    >
                                        <div>
                                            <div class="flex items-center justify-between gap-2 mb-2">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0" :class="selectedCamaba.rekap?.rincian?.dpp?.is_lunas ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200/80 text-slate-600'">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                                                    </div>
                                                    <span class="text-xs font-bold text-slate-800 truncate">DPP PMB</span>
                                                </div>
                                                <span 
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                                                    :class="selectedCamaba.rekap?.rincian?.dpp?.is_lunas ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-200/60 text-slate-600 border border-slate-200'"
                                                    x-text="selectedCamaba.rekap?.rincian?.dpp?.is_lunas ? '✓ Lunas' : 'Belum Lunas'"
                                                ></span>
                                            </div>

                                            <div class="mt-2">
                                                <span class="text-[11px] font-medium text-slate-400 block">Sudah Dibayar:</span>
                                                <span 
                                                    class="text-base sm:text-lg font-black font-display tracking-tight"
                                                    :class="selectedCamaba.rekap?.rincian?.dpp?.is_lunas ? 'text-emerald-700' : 'text-slate-900'"
                                                    x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.dpp?.terbayar || 0).toLocaleString('id-ID')"
                                                ></span>
                                            </div>
                                        </div>

                                        <div class="pt-2.5 mt-2.5 border-t border-slate-200/70 flex items-center justify-between text-[11px]">
                                            <span class="text-slate-500">Kewajiban: <strong class="text-slate-700 font-semibold" x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.dpp?.kewajiban || 0).toLocaleString('id-ID')"></strong></span>
                                            <span class="text-slate-500">Sisa: <strong :class="selectedCamaba.rekap?.rincian?.dpp?.sisa > 0 ? 'text-rose-600 font-bold' : 'text-slate-400'" x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.dpp?.sisa || 0).toLocaleString('id-ID')"></strong></span>
                                        </div>
                                        <div class="mt-1 pt-1 border-t border-slate-100 flex items-center justify-between text-[10.5px]">
                                            <span class="text-slate-400">Metode Bayar:</span>
                                            <span class="font-bold text-[#0b3d82]" x-text="selectedCamaba.rekap?.rincian?.dpp?.metode || (selectedCamaba.rekap?.rincian?.dpp?.is_lunas && selectedCamaba.rekap?.rincian?.dpp?.kewajiban == 0 ? 'Bebas Biaya' : '-')"></span>
                                        </div>
                                        <div class="mt-1 pt-1 border-t border-slate-100 flex items-center justify-between text-[10.5px]">
                                            <span class="text-slate-400">Voucher DPP:</span>
                                            <template x-if="selectedCamaba.rekap?.dpp_voucher">
                                                <span class="font-bold text-emerald-700" x-text="selectedCamaba.rekap.dpp_voucher.voucher_code + ' (-' + selectedCamaba.rekap.dpp_voucher.percentage_formatted + '% / Rp ' + (selectedCamaba.rekap.dpp_voucher.discount_amount || 0).toLocaleString('id-ID') + ')'"></span>
                                            </template>
                                            <template x-if="!selectedCamaba.rekap?.dpp_voucher">
                                                <span class="text-slate-400 font-medium">Belum Menggunakan Voucher</span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- 4. Uang Gedung -->
                                    <div 
                                        class="p-4 rounded-2xl border transition-all duration-200 flex flex-col justify-between"
                                        :class="selectedCamaba.rekap?.rincian?.uang_gedung?.is_lunas ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200 bg-slate-50/40'"
                                    >
                                        <div>
                                            <div class="flex items-center justify-between gap-2 mb-2">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0" :class="selectedCamaba.rekap?.rincian?.uang_gedung?.is_lunas ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200/80 text-slate-600'">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                    </div>
                                                    <span class="text-xs font-bold text-slate-800 truncate">Uang Gedung</span>
                                                </div>
                                                <span 
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                                                    :class="selectedCamaba.rekap?.rincian?.uang_gedung?.is_lunas ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-200/60 text-slate-600 border border-slate-200'"
                                                    x-text="selectedCamaba.rekap?.rincian?.uang_gedung?.is_lunas ? '✓ Lunas' : 'Belum Lunas'"
                                                ></span>
                                            </div>

                                            <div class="mt-2">
                                                <span class="text-[11px] font-medium text-slate-400 block">Sudah Dibayar:</span>
                                                <span 
                                                    class="text-base sm:text-lg font-black font-display tracking-tight"
                                                    :class="selectedCamaba.rekap?.rincian?.uang_gedung?.is_lunas ? 'text-emerald-700' : 'text-slate-900'"
                                                    x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.uang_gedung?.terbayar || 0).toLocaleString('id-ID')"
                                                ></span>
                                            </div>
                                        </div>

                                        <div class="pt-2.5 mt-2.5 border-t border-slate-200/70 flex items-center justify-between text-[11px]">
                                            <span class="text-slate-500">Kewajiban: <strong class="text-slate-700 font-semibold" x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.uang_gedung?.kewajiban || 0).toLocaleString('id-ID')"></strong></span>
                                            <span class="text-slate-500">Sisa: <strong :class="selectedCamaba.rekap?.rincian?.uang_gedung?.sisa > 0 ? 'text-rose-600 font-bold' : 'text-slate-400'" x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.uang_gedung?.sisa || 0).toLocaleString('id-ID')"></strong></span>
                                        </div>
                                        <div class="mt-1 pt-1 border-t border-slate-100 flex items-center justify-between text-[10.5px]">
                                            <span class="text-slate-400">Metode Bayar:</span>
                                            <span class="font-bold text-[#0b3d82]" x-text="selectedCamaba.rekap?.rincian?.uang_gedung?.metode || (selectedCamaba.rekap?.rincian?.uang_gedung?.is_lunas && selectedCamaba.rekap?.rincian?.uang_gedung?.kewajiban == 0 ? 'Bebas Biaya' : '-')"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sub-Group 2: 2 Komponen Biaya Kuliah & Uang Muka (Simetris 2 Kolom 50/50) -->
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                        <h4 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">2. Uang Muka & Biaya Kuliah Semester 1</h4>
                                    </div>
                                    <span class="text-[11px] font-bold text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-full border border-blue-200">Konfirmasi PMB & SPP</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- 4. Down Payment (DP) -->
                                    <div 
                                        class="p-4 rounded-2xl border transition-all duration-200 flex flex-col justify-between"
                                        :class="selectedCamaba.rekap?.rincian?.dp?.is_lunas ? 'border-emerald-200 bg-emerald-50/30' : 'border-blue-200 bg-blue-50/30'"
                                    >
                                        <div>
                                            <div class="flex items-center justify-between gap-2 mb-2">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0" :class="selectedCamaba.rekap?.rincian?.dp?.is_lunas ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-[#0b3d82]'">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                                    </div>
                                                    <span class="text-xs font-bold text-[#0b3d82] truncate">Down Payment (DP Konfirmasi)</span>
                                                </div>
                                                <span 
                                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                                                    :class="selectedCamaba.rekap?.rincian?.dp?.is_lunas ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-blue-100 text-blue-800 border border-blue-200'"
                                                    x-text="selectedCamaba.rekap?.rincian?.dp?.is_lunas ? '✓ Lunas' : 'Belum Lunas'"
                                                ></span>
                                            </div>

                                            <div class="mt-2.5">
                                                <span class="text-[11px] font-medium text-slate-400 block">Sudah Dibayar:</span>
                                                <span 
                                                    class="text-lg font-black font-display tracking-tight"
                                                    :class="selectedCamaba.rekap?.rincian?.dp?.is_lunas ? 'text-emerald-700' : 'text-slate-900'"
                                                    x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.dp?.terbayar || 0).toLocaleString('id-ID')"
                                                ></span>
                                            </div>
                                        </div>

                                        <div class="pt-3 mt-3 border-t border-slate-200/70 flex items-center justify-between text-[11px]">
                                            <span class="text-slate-500">Kewajiban DP: <strong class="text-slate-700 font-semibold">Rp 500.000</strong></span>
                                            <span class="text-slate-500">Sisa: <strong :class="selectedCamaba.rekap?.rincian?.dp?.sisa > 0 ? 'text-rose-600 font-bold' : 'text-slate-400'" x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.dp?.sisa || 0).toLocaleString('id-ID')"></strong></span>
                                        </div>
                                        <div class="mt-1 pt-1 border-t border-blue-100 flex items-center justify-between text-[10.5px]">
                                            <span class="text-slate-400">Metode Bayar:</span>
                                            <span class="font-bold text-[#0b3d82]" x-text="selectedCamaba.rekap?.rincian?.dp?.metode || (selectedCamaba.rekap?.rincian?.dp?.is_lunas && selectedCamaba.rekap?.rincian?.dp?.kewajiban == 0 ? 'Bebas Biaya' : '-')"></span>
                                        </div>
                                    </div>

                                    <!-- 5. Pelunasan Biaya Kuliah -->
                                    <div 
                                        class="p-4 rounded-2xl border transition-all duration-200 flex flex-col justify-between"
                                        :class="selectedCamaba.rekap?.rincian?.pelunasan?.is_lunas ? 'border-emerald-200 bg-emerald-50/30' : 'border-indigo-200 bg-indigo-50/30'"
                                    >
                                        <div>
                                            <div class="flex items-center justify-between gap-2 mb-2">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0" :class="selectedCamaba.rekap?.rincian?.pelunasan?.is_lunas ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700'">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/></svg>
                                                    </div>
                                                    <span class="text-xs font-bold text-indigo-900 truncate">Pelunasan Biaya Kuliah (Semester 1)</span>
                                                </div>
                                                <span 
                                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                                                    :class="selectedCamaba.rekap?.rincian?.pelunasan?.is_lunas ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-indigo-100 text-indigo-800 border border-indigo-200'"
                                                    x-text="selectedCamaba.rekap?.rincian?.pelunasan?.is_lunas ? '✓ Lunas' : 'Belum Lunas'"
                                                ></span>
                                            </div>

                                            <div class="mt-2.5">
                                                <span class="text-[11px] font-medium text-slate-400 block">Sudah Dibayar:</span>
                                                <span 
                                                    class="text-lg font-black font-display tracking-tight"
                                                    :class="selectedCamaba.rekap?.rincian?.pelunasan?.is_lunas ? 'text-emerald-700' : 'text-slate-900'"
                                                    x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.pelunasan?.terbayar || 0).toLocaleString('id-ID')"
                                                ></span>
                                            </div>
                                        </div>

                                        <div class="pt-3 mt-3 border-t border-slate-200/70 flex items-center justify-between text-[11px]">
                                            <span class="text-slate-500">Kewajiban: <strong class="text-slate-700 font-semibold" x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.pelunasan?.kewajiban || 0).toLocaleString('id-ID')"></strong></span>
                                            <span class="text-slate-500">Sisa: <strong :class="selectedCamaba.rekap?.rincian?.pelunasan?.sisa > 0 ? 'text-rose-600 font-bold' : 'text-slate-400'" x-text="'Rp ' + (selectedCamaba.rekap?.rincian?.pelunasan?.sisa || 0).toLocaleString('id-ID')"></strong></span>
                                        </div>
                                        <div class="mt-1 pt-1 border-t border-indigo-100 flex items-center justify-between text-[10.5px]">
                                            <span class="text-slate-400">Metode Bayar:</span>
                                            <span class="font-bold text-[#0b3d82]" x-text="selectedCamaba.rekap?.rincian?.pelunasan?.metode || (selectedCamaba.rekap?.rincian?.pelunasan?.is_lunas && selectedCamaba.rekap?.rincian?.pelunasan?.kewajiban == 0 ? 'Bebas Biaya' : '-')"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Riwayat Transaksi Individual -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                    <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Riwayat Transaksi Individual (<span x-text="selectedCamaba.transaksis?.length || 0"></span> Transaksi Tercatat)
                                    </h4>
                                </div>
                                <span class="text-[11px] text-slate-400">Semua riwayat transaksi disimpan utuh tanpa digabung</span>
                            </div>

                            <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-2xs">
                                <table class="w-full text-left text-xs whitespace-nowrap">
                                    <thead>
                                        <tr class="bg-slate-50/90 text-slate-500 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                                            <th class="py-3 px-3.5">ORDER ID</th>
                                            <th class="py-3 px-3.5">TANGGAL</th>
                                            <th class="py-3 px-3.5">KOMPONEN / DETAIL</th>
                                            <th class="py-3 px-3.5">NOMINAL</th>
                                            <th class="py-3 px-3.5">METODE</th>
                                            <th class="py-3 px-3.5">STATUS</th>
                                            <th class="py-3 px-3.5 text-center">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <template x-for="trx in selectedCamaba.transaksis" :key="trx.id">
                                            <tr class="hover:bg-slate-50/80 transition-colors">
                                                <!-- Order ID -->
                                                <td class="py-3 px-3.5 font-mono font-bold text-[#0b3d82]" x-text="trx.order_id"></td>

                                                <!-- Tanggal -->
                                                <td class="py-3 px-3.5 text-slate-500 text-[11px]" x-text="trx.tanggal"></td>

                                                <!-- Detail Pembayaran -->
                                                <td class="py-3 px-3.5 text-slate-700 font-medium max-w-xs truncate" x-text="trx.detail"></td>

                                                <!-- Nominal -->
                                                <td class="py-3 px-3.5 font-extrabold text-slate-900" x-text="'Rp ' + trx.nominal"></td>

                                                <!-- Metode -->
                                                <td class="py-3 px-3.5">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200" x-text="trx.metode"></span>
                                                </td>

                                                <!-- Status -->
                                                <td class="py-3 px-3.5">
                                                    <template x-if="trx.is_verified">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                            Berhasil
                                                        </span>
                                                    </template>
                                                    <template x-if="trx.is_pending">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                            Pending
                                                        </span>
                                                    </template>
                                                    <template x-if="trx.is_rejected">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                            Gagal / Ditolak
                                                        </span>
                                                    </template>
                                                </td>

                                                <!-- Aksi per Transaksi -->
                                                <td class="py-3 px-3.5 text-center">
                                                    <div class="inline-flex items-center justify-center gap-1.5">
                                                        <!-- Sync Midtrans jika ada order_id dan belum verified -->
                                                        <template x-if="trx.is_pending && trx.order_id && !trx.metode_raw?.includes('MANUAL')">
                                                            <form :action="'{{ url('pmb/pembayaran') }}/' + trx.id + '/sync'" method="POST" class="inline">
                                                                @csrf
                                                                <button type="submit" class="p-1 rounded-lg border border-blue-200 bg-blue-50 text-[#0b3d82] hover:bg-[#082a5c] hover:text-white transition shadow-2xs cursor-pointer" title="Cek status Midtrans">
                                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                                </button>
                                                            </form>
                                                        </template>

                                                        <!-- Verifikasi manual jika pending -->
                                                        <template x-if="!trx.is_verified">
                                                            <button 
                                                                type="button" 
                                                                @click="openVerify(trx, selectedCamaba.name)"
                                                                class="p-1 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white transition shadow-2xs cursor-pointer"
                                                                title="Verifikasi Transaksi Ini"
                                                            >
                                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                            </button>
                                                        </template>

                                                        <!-- Tolak transaksi jika belum ditolak -->
                                                        <template x-if="!trx.is_rejected">
                                                            <button 
                                                                type="button" 
                                                                @click="openReject(trx, selectedCamaba.name)"
                                                                class="p-1 rounded-lg border border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white transition shadow-2xs cursor-pointer"
                                                                title="Tolak Transaksi Ini"
                                                            >
                                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>

                                        <template x-if="!selectedCamaba.transaksis || selectedCamaba.transaksis.length === 0">
                                            <tr>
                                                <td colspan="7" class="py-6 text-center text-slate-400">
                                                    Tidak ada riwayat transaksi yang tercatat untuk calon mahasiswa ini.
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-slate-50 px-6 py-4 flex items-center justify-between border-t border-slate-100">
                        <span class="text-xs text-slate-400 font-mono">ID Camaba: #<span x-text="selectedCamaba.id"></span></span>
                        <button type="button" @click="detailModalOpen = false" class="px-5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-100 rounded-xl transition cursor-pointer shadow-2xs">
                            Tutup Detail
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL: Verifikasi Pembayaran Transaksi -->
        <!-- ============================================================== -->
        <div 
            x-show="verifyModalOpen" 
            x-cloak
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-60 overflow-y-auto"
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
                                    <span class="text-slate-500">Nama Camaba:</span>
                                    <span class="font-semibold text-slate-800" x-text="selectedTransaksi.camaba_name"></span>
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

        <!-- ============================================================== -->
        <!-- MODAL: Tolak / Batalkan Pembayaran Transaksi -->
        <!-- ============================================================== -->
        <div 
            x-show="rejectModalOpen" 
            x-cloak
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-60 overflow-y-auto"
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
                                Transaksi <strong class="font-mono text-slate-700" x-text="selectedTransaksi.order_id"></strong> milik <span class="font-semibold" x-text="selectedTransaksi.camaba_name"></span> akan ditolak/dibatalkan. Mohon cantumkan alasan penolakan:
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

        <!-- ============================================================== -->
        <!-- MODAL: Ubah Persentase & Status Voucher Diskon DPP -->
        <!-- ============================================================== -->
        <div x-show="voucherEditModalOpen" x-cloak class="fixed inset-0 z-60 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="voucherEditModalOpen = false"></div>
                <div class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl max-w-md w-full border border-slate-100">
                    <form :action="editingVoucherUrl" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 font-display">Pengaturan Voucher</h3>
                                    <p class="text-xs text-slate-500">Kode: <strong class="font-mono text-[#0b3d82]" x-text="editingVoucher.code"></strong></p>
                                </div>
                                <button type="button" @click="voucherEditModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Voucher</label>
                                <input type="text" name="name" x-model="editingVoucher.name" maxlength="150" placeholder="Contoh: Beasiswa Gelombang 1"
                                       class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Persentase Diskon (%)</label>
                                <input type="number" name="discount_percentage" x-model="editingVoucher.discount_percentage" min="1" max="100" step="0.01" required
                                       class="w-full text-xs font-bold rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Status Voucher</label>
                                <select name="is_active" x-model="editingVoucher.is_active"
                                        class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="bg-slate-50/80 px-6 py-3.5 flex items-center justify-end gap-2 border-t border-slate-100">
                            <button type="button" @click="voucherEditModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl cursor-pointer">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-[#0b3d82] hover:bg-[#082a5c] rounded-xl cursor-pointer">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL: Daftar CAMABA Pengguna Voucher Tertentu -->
        <!-- ============================================================== -->
        <div x-show="voucherUsersModalOpen" x-cloak class="fixed inset-0 z-60 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="voucherUsersModalOpen = false"></div>
                <div class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl max-w-2xl w-full border border-slate-100">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 font-display">
                                Pengguna Voucher <span class="font-mono text-[#0b3d82]" x-text="selectedVoucherForUsers.code"></span>
                            </h3>
                            <p class="text-xs text-slate-500">
                                <span x-show="selectedVoucherForUsers.name" class="font-semibold text-slate-700" x-text="selectedVoucherForUsers.name + ' • '"></span>
                                Diskon saat ini: <strong x-text="selectedVoucherForUsers.percentage_formatted + '%'"></strong>
                            </p>
                        </div>
                        <button type="button" @click="voucherUsersModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-5 max-h-[60vh] overflow-y-auto space-y-2.5">
                        <template x-if="!selectedVoucherForUsers.usages || selectedVoucherForUsers.usages.length === 0">
                            <p class="text-xs text-slate-500 text-center py-6">Belum ada CAMABA yang menggunakan voucher ini.</p>
                        </template>
                        <template x-for="(u, i) in (selectedVoucherForUsers.usages || [])" :key="i">
                            <div class="p-3 rounded-xl border border-slate-200/80 bg-slate-50/60 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <div class="font-bold text-slate-900" x-text="u.camaba_name"></div>
                                    <div class="text-[11px] text-slate-500">
                                        <span class="font-mono" x-text="u.no_registrasi"></span> •
                                        Diskon <strong class="text-emerald-700" x-text="u.percentage_formatted + '%'"></strong> •
                                        Potongan <strong class="text-emerald-700" x-text="u.discount_amount"></strong>
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        DPP Awal: <strong x-text="u.dpp_before"></strong> &rarr; Setelah Diskon: <strong class="text-[#0b3d82]" x-text="u.dpp_after"></strong>
                                    </div>
                                </div>
                                <div class="sm:text-right shrink-0">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                          :class="u.is_used ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                          x-text="u.status_label"></span>
                                    <div class="text-[10px] text-slate-400 mt-1" x-text="u.time"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        window.confirmMoveToTrash = function(event, camaba) {
            const form = event.target;
            if (form.dataset.confirmed === 'true') {
                return true;
            }
            event.preventDefault();

            const esc = (val) => {
                const div = document.createElement('div');
                div.textContent = val ?? '-';
                return div.innerHTML;
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Pindahkan Data ke Trash?',
                    html: `
                        <div class="text-left text-xs space-y-2 mt-2">
                            <p class="text-slate-600">Pastikan data CAMABA berikut benar-benar ingin dipindahkan ke <strong>Trash</strong>:</p>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-1">
                                <div><span class="text-slate-400">Nama CAMABA:</span> <strong class="text-slate-900">${esc(camaba.name)}</strong></div>
                                <div><span class="text-slate-400">No. Pendaftaran:</span> <strong class="font-mono text-[#0b3d82]">${esc(camaba.reg_no)}</strong></div>
                                <div><span class="text-slate-400">Email:</span> <strong class="text-slate-800">${esc(camaba.email)}</strong></div>
                                <div><span class="text-slate-400">Program Studi:</span> <strong class="text-slate-800">${esc(camaba.prodi)}</strong></div>
                                <div><span class="text-slate-400">Total Terbayar:</span> <strong class="text-emerald-700">${esc(camaba.terbayar)}</strong></div>
                            </div>
                            <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1.5">
                                Data akan dipindahkan via <strong>Soft Delete</strong> ke Trash. Riwayat transaksi, bukti pembayaran, dan voucher tetap aman dan dapat dipulihkan kembali.
                            </p>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Pindahkan ke Trash',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-2xl border border-slate-100 p-6',
                        title: 'text-slate-900 font-bold text-lg',
                        confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2.5 shadow-xs',
                        cancelButton: 'rounded-xl text-xs font-semibold px-4 py-2.5'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.dataset.confirmed = 'true';
                        form.submit();
                    }
                });
            } else if (confirm(`Pindahkan data ${camaba.name} (${camaba.reg_no}) ke Trash?`)) {
                form.dataset.confirmed = 'true';
                form.submit();
            }

            return false;
        };
    </script>
</x-app-layout>
